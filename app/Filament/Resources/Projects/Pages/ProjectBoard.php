<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\Projects\Schemas\BoardTaskForm;
use App\Filament\Resources\Tasks\TaskResource;
use App\Models\CustomField;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Services\CustomFieldSchema;
use App\Services\TaskPosition;
use App\Support\Permissions;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\SlideOverPosition;
use Filament\Support\Enums\Width;
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
            // Formatted through the same helper the table uses, so an amount
            // does not read one way on a card and another in a list.
            ->map(fn ($value) => [
                'label' => $value->customField->name,
                'value' => CustomFieldSchema::format($value->customField, $value->value()),
            ])
            ->filter(fn (array $field) => filled($field['value']))
            ->values()
            ->all();
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->createTaskAction(),

            // The columns and the fields of a board are edited on the project's
            // settings page. Reaching it meant going back to the list and
            // hunting for the row, which is a poor place to hide the one screen
            // that decides what this board looks like.
            Action::make('projectSettings')
                ->label(__('Στήλες & Ρυθμίσεις'))
                ->icon('heroicon-o-cog-6-tooth')
                ->color('gray')
                ->visible(fn () => ProjectResource::canEdit($this->getRecord()))
                ->url(fn () => ProjectResource::getUrl('edit', ['record' => $this->getRecord()])),
        ];
    }

    /**
     * Adding work without leaving the board.
     *
     * Takes an optional column argument so the "+" on a column head drops the
     * task straight into that column — on a board, where something starts is
     * usually the point.
     */
    public function createTaskAction(): Action
    {
        return Action::make('createTask')
            ->label(__('Νέα Εργασία'))
            ->icon('heroicon-o-plus')
            ->visible(fn () => $this->canMove())
            ->slideOver()
            ->slideOverPosition(SlideOverPosition::End)
            ->modalWidth(Width::Medium)
            ->modalHeading(__('Νέα Εργασία'))
            ->modalSubmitActionLabel(__('Δημιουργία'))
            ->fillForm(fn (array $arguments): array => [
                'task_status_id' => $this->resolveColumn($arguments)?->id
                    ?? $this->getRecord()->defaultStatus()?->id,
                'priority' => Task::PRIORITY_NORMAL,
            ])
            ->schema(fn () => BoardTaskForm::components($this->getRecord(), null))
            ->action(function (array $data): void {
                abort_unless($this->canMove(), 403);

                $project = $this->getRecord();
                $status = $project->statuses()->whereKey($data['task_status_id'])->first();

                abort_unless($status, 404);

                $custom = $data[CustomFieldSchema::STATE_KEY] ?? [];
                $uploads = $data[BoardTaskForm::ATTACHMENTS_KEY] ?? [];
                unset($data[CustomFieldSchema::STATE_KEY], $data[BoardTaskForm::ATTACHMENTS_KEY]);

                $task = $project->tasks()->create($data + [
                    // From the project, never the session — the two must agree.
                    'tenant_id' => $project->tenant_id,
                    'created_by' => auth()->id(),
                    'position' => TaskPosition::endOf($status),
                ]);

                $task->saveCustomFieldState($custom);
                BoardTaskForm::storeAttachments($task, array_values($uploads));

                $this->appendCard($task->refresh());
            });
    }

    protected function resolveColumn(array $arguments): ?TaskStatus
    {
        return $this->getRecord()->statuses()->whereKey($arguments['column'] ?? null)->first();
    }

    /** Hand the browser a card to add to the end of its column. */
    protected function appendCard(Task $task): void
    {
        $task->load(['assignee', 'customFieldValues.customField']);

        $this->dispatch(
            'board-card-added',
            columnId: $task->task_status_id,
            html: view('filament.resources.projects.pages.partials.board-card', [
                'task' => $task,
                'fields' => $this->cardFields($task),
                'canMove' => $this->canMove(),
            ])->render(),
        );
    }

    /**
     * Editing a card without leaving the board.
     *
     * A panel rather than a page: the board is the context — which column a
     * task sits in, what is beside it — and navigating away loses it, along
     * with the scroll position on a wide board.
     *
     * The full page is still one click away, because comments, attachments and
     * the time log are relation managers and cannot live inside an action.
     */
    public function editTaskAction(): Action
    {
        return Action::make('editTask')
            ->slideOver()
            ->slideOverPosition(SlideOverPosition::End)
            ->modalWidth(Width::Medium)
            ->modalHeading(fn (array $arguments) => $this->resolveTask($arguments)?->title)
            ->modalSubmitActionLabel(__('Αποθήκευση'))
            ->fillForm(function (array $arguments): array {
                $task = $this->resolveTask($arguments);

                if (! $task) {
                    return [];
                }

                return [
                    'title' => $task->title,
                    'task_status_id' => $task->task_status_id,
                    'assignee_id' => $task->assignee_id,
                    'priority' => $task->priority,
                    'start_date' => $task->start_date,
                    'due_date' => $task->due_date,
                    'description' => $task->description,
                    CustomFieldSchema::STATE_KEY => $task->customFieldState(),
                ];
            })
            ->schema(fn (array $arguments) => BoardTaskForm::components(
                $this->getRecord(),
                $this->resolveTask($arguments),
            ))
            ->extraModalFooterActions(fn (array $arguments) => [
                Action::make('openFullPage')
                    ->label(__('Πλήρης σελίδα'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn () => ($task = $this->resolveTask($arguments))
                        ? TaskResource::getUrl('edit', ['record' => $task])
                        : null),
            ])
            ->action(function (array $arguments, array $data): void {
                $task = $this->resolveTask($arguments);

                abort_unless($task && TaskResource::canEdit($task), 403);

                $custom = $data[CustomFieldSchema::STATE_KEY] ?? [];
                $uploads = $data[BoardTaskForm::ATTACHMENTS_KEY] ?? [];
                unset($data[CustomFieldSchema::STATE_KEY], $data[BoardTaskForm::ATTACHMENTS_KEY]);

                // The column belongs to this project or it does not exist —
                // the same rule the drag handler applies.
                abort_unless(
                    $this->getRecord()->statuses()->whereKey($data['task_status_id'])->exists(),
                    404,
                );

                $task->update($data);
                $task->saveCustomFieldState($custom);
                BoardTaskForm::storeAttachments($task, array_values($uploads));

                $this->refreshCard($task->refresh());
            });
    }

    protected function resolveTask(array $arguments): ?Task
    {
        // Looked up through the project, so an id from another board — or
        // another company — resolves to nothing.
        return $this->getRecord()->tasks()->whereKey($arguments['task'] ?? null)->first();
    }

    /**
     * Hand the browser the freshly rendered card.
     *
     * The board carries wire:ignore so that dragging stays instant, which also
     * means Livewire will never repaint it. Rendering the one card that
     * changed on the server keeps the markup in one place instead of rebuilding
     * it in JavaScript.
     */
    protected function refreshCard(Task $task): void
    {
        $task->load(['assignee', 'customFieldValues.customField']);

        $this->dispatch(
            'board-card-updated',
            id: $task->id,
            columnId: $task->task_status_id,
            html: view('filament.resources.projects.pages.partials.board-card', [
                'task' => $task,
                'fields' => $this->cardFields($task),
                'canMove' => $this->canMove(),
            ])->render(),
        );
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
