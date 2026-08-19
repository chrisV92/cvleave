<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use App\Support\PanelUrl;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Task $task, public ?User $assignedBy = null) {}

    public function via(object $notifiable): array
    {
        return $notifiable->notificationChannels();
    }

    protected function details(): array
    {
        $details = [
            __('Έργο') => $this->task->project?->name,
            __('Στήλη') => $this->task->status?->name,
        ];

        if ($this->task->due_date) {
            $details[__('Προθεσμία')] = $this->task->due_date->format('d/m/Y');
        }

        if ($this->task->priority) {
            $details[__('Προτεραιότητα')] = Task::priorities()[$this->task->priority] ?? $this->task->priority;
        }

        return array_filter($details);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Σου ανατέθηκε: :title', ['title' => $this->task->title]))
            ->view('emails.notification', [
                'tagline' => __('Διαχείριση Εργασιών'),
                'footerNote' => __('Διαχείριση έργων και εργασιών'),
                'title' => __('Νέα ανάθεση'),
                'accent' => '#4f46e5',
                'accentDark' => '#3730a3',
                'badgeBg' => '#e0e7ff',
                'badgeText' => '#3730a3',
                'badgeLabel' => __('Ανάθεση'),
                'heading' => __('Σου ανατέθηκε μια εργασία 📋'),
                'intro' => $this->assignedBy
                    ? __('Ο/Η <strong>:who</strong> σου ανέθεσε την εργασία <strong>:title</strong>.', [
                        'who' => $this->assignedBy->name,
                        'title' => e($this->task->title),
                    ])
                    : __('Σου ανατέθηκε η εργασία <strong>:title</strong>.', ['title' => e($this->task->title)]),
                'details' => $this->details(),
                'ctaLabel' => __('Δες την εργασία'),
                'ctaUrl' => PanelUrl::taskForUser($notifiable, $this->task),
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title(__('Σου ανατέθηκε μια εργασία'))
            ->body($this->task->title.' — '.($this->task->project?->name ?? ''))
            ->info()
            ->actions([
                Action::make('view')
                    ->label(__('Δες την εργασία'))
                    ->url(PanelUrl::taskForUser($notifiable, $this->task))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
