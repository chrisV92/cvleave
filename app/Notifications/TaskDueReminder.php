<?php

namespace App\Notifications;

use App\Models\Task;
use App\Support\PanelUrl;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskDueReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Task $task, public bool $overdue = false) {}

    public function via(object $notifiable): array
    {
        return $notifiable->notificationChannels();
    }

    public function toMail(object $notifiable): MailMessage
    {
        $due = $this->task->due_date->format('d/m/Y');

        return (new MailMessage)
            ->subject($this->overdue
                ? __('Εκπρόθεσμη: :title', ['title' => $this->task->title])
                : __('Λήγει αύριο: :title', ['title' => $this->task->title]))
            ->view('emails.notification', [
                'tagline' => __('Διαχείριση Εργασιών'),
                'footerNote' => __('Διαχείριση έργων και εργασιών'),
                'title' => $this->overdue ? __('Εκπρόθεσμη εργασία') : __('Προθεσμία αύριο'),
                'accent' => $this->overdue ? '#dc2626' : '#d97706',
                'accentDark' => $this->overdue ? '#991b1b' : '#92400e',
                'badgeBg' => $this->overdue ? '#fee2e2' : '#fef3c7',
                'badgeText' => $this->overdue ? '#991b1b' : '#92400e',
                'badgeLabel' => $this->overdue ? __('Εκπρόθεσμη') : __('Αύριο'),
                'heading' => $this->overdue ? __('Μια εργασία έχει ξεπεράσει την προθεσμία ⏰') : __('Μια εργασία λήγει αύριο ⏳'),
                'intro' => __('Η εργασία <strong>:title</strong> έχει προθεσμία <strong>:due</strong>.', [
                    'title' => e($this->task->title),
                    'due' => $due,
                ]),
                'details' => array_filter([
                    __('Έργο') => $this->task->project?->name,
                    __('Στήλη') => $this->task->status?->name,
                    __('Προτεραιότητα') => Task::priorities()[$this->task->priority] ?? null,
                ]),
                'ctaLabel' => __('Δες την εργασία'),
                'ctaUrl' => PanelUrl::taskForUser($notifiable, $this->task),
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title($this->overdue ? __('Εκπρόθεσμη εργασία') : __('Προθεσμία αύριο'))
            ->body($this->task->title.' — '.$this->task->due_date->format('d/m/Y'))
            ->{$this->overdue ? 'danger' : 'warning'}()
            ->actions([
                Action::make('view')
                    ->label(__('Δες την εργασία'))
                    ->url(PanelUrl::taskForUser($notifiable, $this->task))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
