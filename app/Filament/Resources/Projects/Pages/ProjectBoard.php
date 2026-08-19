<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Models\CustomField;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Services\TaskPosition;
use App\Support\Permissions;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The drag-and-drop board for one project.
 *
 * Deliberately hand-rolled rather than taken from a package: every Filament
 * kanban plugin renders through the host application's Tailwind build, and
 * this project ships no compiled assets at all. SortableJS is vendored as a
 * plain file instead, so a board costs no build step.
 */
class ProjectBoard extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.resources.projects.pages.project-board';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        abort_unless(auth()->user()?->can(Permissions::TASKS_VIEW) ?? false, 403);
    }

    public function getTitle(): string|Htmlable
    {
        return $this->getRecord()->name;
    }

    public static function getNavigationLabel(): string
    {
        return __('Πίνακας');
    }

    /** @return Collection<int, TaskStatus> */
    public function getColumns(): Collection
    {
        return $this->getRecord()
            ->statuses()
            ->withCount('tasks')
            ->get();
    }

    /** @return Collection<int, Task> */
    public function tasksFor(TaskStatus $status): Collection
    {
        return $status->tasks()
            ->with(['assignee', 'customFieldValues.customField'])
            ->whereNull('archived_at')
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    /** Up to two field values, to keep a card readable. */
    public function cardFields(Task $task): array
    {
        return $task->customFieldValues
            ->filter(fn ($value) => $value->customField !== null)
            ->take(2)
            ->map(fn ($value) => [
                'label' => $value->customField->name,
                'value' => $value->value(),
            ])
            ->filter(fn (array $field) => $field['value'] !== null && $field['value'] !== [])
            ->map(fn (array $field) => [
                'label' => $field['label'],
                'value' => is_array($field['value']) ? implode(', ', $field['value']) : (string) $field['value'],
            ])
            ->values()
            ->all();
    }

    public function canMove(): bool
    {
        return auth()->user()?->can(Permissions::TASKS_MANAGE) ?? false;
    }

    /**
     * Persist a drop.
     *
     * Everything here arrives from the browser, so nothing is trusted. The
     * task and the target column are both looked up *through this project*,
     * which is itself already scoped to the current company — an id belonging
     * to another board, or another company, simply fails to resolve.
     */
    public function moveTask(int $taskId, int $statusId, ?int $afterId = null, ?int $beforeId = null): void
    {
        abort_unless($this->canMove(), 403);

        $project = $this->getRecord();

        $task = $project->tasks()->whereKey($taskId)->first();
        $status = $project->statuses()->whereKey($statusId)->first();

        abort_unless($task && $status, 404);

        DB::transaction(function () use ($task, $status, $afterId, $beforeId) {
            $position = $this->positionBetween($status, $afterId, $beforeId);

            if ($position === null) {
                // The neighbours had run out of room between them.
                TaskPosition::rebalance($status);
                $position = $this->positionBetween($status, $afterId, $beforeId) ?? TaskPosition::endOf($status);
            }

            $task->task_status_id = $status->id;
            $task->position = $position;
            $task->save();
        });
    }

    protected function positionBetween(TaskStatus $status, ?int $afterId, ?int $beforeId): ?float
    {
        // Neighbours are read from the column being dropped into, so a stale
        // browser cannot point at a card that has since moved elsewhere.
        $above = $afterId
            ? $status->tasks()->whereKey($afterId)->value('position')
            : null;

        $below = $beforeId
            ? $status->tasks()->whereKey($beforeId)->value('position')
            : null;

        return TaskPosition::between(
            $above === null ? null : (float) $above,
            $below === null ? null : (float) $below,
        );
    }

    /** Fields defined for this board, shown as a hint under the column heading. */
    public function projectFieldCount(): int
    {
        return CustomField::forProject($this->getRecord())->count();
    }
}
