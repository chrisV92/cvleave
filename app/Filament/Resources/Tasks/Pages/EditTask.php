<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Filament\Resources\Tasks\TaskResource;
use App\Models\TaskTimeEntry;
use App\Services\CustomFieldSchema;
use App\Services\TimeTracking;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditTask extends EditRecord
{
    protected static string $resource = TaskResource::class;

    /** @var array<int|string, mixed> */
    protected array $customFieldState = [];

    protected function getHeaderActions(): array
    {
        return [
            ...$this->timerActions(),

            DeleteAction::make()
                ->visible(fn () => TaskResource::canDelete($this->getRecord())),
        ];
    }

    /**
     * Start/stop for the current user's timer on this task.
     *
     * Only offered where the project asked for time tracking — a board that
     * is a to-do list rather than a timesheet should not carry the clutter.
     */
    protected function timerActions(): array
    {
        $task = $this->getRecord();
        $user = auth()->user();
        $tracking = app(TimeTracking::class);

        if (! $task->project?->time_tracking_enabled || ! $user || ! TaskResource::canEdit($task)) {
            return [];
        }

        // Visibility is a closure, not a value: the two buttons swap after the
        // action runs, and a boolean computed here would be baked in at mount
        // and never change.
        $isRunning = fn () => $tracking->isRunningOn($user, $task);

        return [
            Action::make('startTimer')
                ->label(__('Έναρξη χρονομέτρησης'))
                ->icon('heroicon-o-play')
                ->color('success')
                ->visible(fn () => ! $isRunning())
                ->action(function () use ($tracking, $user, $task) {
                    $stopped = $tracking->start($user, $task);

                    Notification::make()
                        ->title(__('Το χρονόμετρο ξεκίνησε'))
                        // Silently stopping the previous timer would look like
                        // lost time; say which one gave way.
                        ->body($stopped
                            ? __('Σταμάτησε το χρονόμετρο στο «:task».', ['task' => $stopped->task?->title])
                            : null)
                        ->success()
                        ->send();
                }),

            Action::make('stopTimer')
                ->label(__('Διακοπή χρονομέτρησης'))
                ->icon('heroicon-o-stop')
                ->color('danger')
                ->visible(fn () => $isRunning())
                ->action(function () use ($tracking, $user, $task) {
                    $entry = $tracking->stop($user, $task);

                    Notification::make()
                        ->title(__('Καταγράφηκε :time', [
                            'time' => TaskTimeEntry::humanise($entry?->duration_seconds),
                        ]))
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getSubheading(): ?string
    {
        $task = $this->getRecord();

        if (! $task->project?->time_tracking_enabled) {
            return null;
        }

        $total = $task->loadMissing('timeEntries')->trackedSeconds();

        return $total
            ? __('Συνολικός χρόνος: :time', ['time' => TaskTimeEntry::humanise($total)])
            : null;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data[CustomFieldSchema::STATE_KEY] = $this->getRecord()->customFieldState();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->customFieldState = $data[CustomFieldSchema::STATE_KEY] ?? [];
        unset($data[CustomFieldSchema::STATE_KEY]);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->saveCustomFieldState($this->customFieldState);
    }
}
