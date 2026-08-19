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

class TaskCompleted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Task $task, public ?User $completedBy = null) {}

    public function via(object $notifiable): array
    {
        return $notifiable->notificationChannels();
    }

    public function toMail(object $notifiable): MailMessage
    {
        $details = array_filter([
            __('Έργο') => $this->task->project?->name,
            __('Στήλη') => $this->task->status?->name,
            __('Ανάθεση') => $this->task->assignee?->name,
        ]);

        return (new MailMessage)
            ->subject(__('Ολοκληρώθηκε: :title', ['title' => $this->task->title]))
            ->view('emails.notification', [
                'tagline' => __('Διαχείριση Εργασιών'),
                'footerNote' => __('Διαχείριση έργων και εργασιών'),
                'title' => __('Ολοκληρωμένη εργασία'),
                'accent' => '#16a34a',
                'accentDark' => '#15803d',
                'badgeBg' => '#dcfce7',
                'badgeText' => '#166534',
                'badgeLabel' => __('Ολοκληρώθηκε'),
                'heading' => __('Μια εργασία ολοκληρώθηκε ✅'),
                'intro' => $this->completedBy
                    ? __('Ο/Η <strong>:who</strong> ολοκλήρωσε την εργασία <strong>:title</strong>.', [
                        'who' => $this->completedBy->name,
                        'title' => e($this->task->title),
                    ])
                    : __('Η εργασία <strong>:title</strong> ολοκληρώθηκε.', ['title' => e($this->task->title)]),
                'details' => $details,
                'ctaLabel' => __('Δες τον πίνακα'),
                'ctaUrl' => $this->task->project
                    ? PanelUrl::boardForUser($notifiable, $this->task->project)
                    : PanelUrl::dashboardForUser($notifiable),
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title(__('Ολοκληρώθηκε εργασία'))
            ->body($this->task->title.($this->completedBy ? ' — '.$this->completedBy->name : ''))
            ->success()
            ->actions([
                Action::make('view')
                    ->label(__('Δες τον πίνακα'))
                    ->url($this->task->project
                        ? PanelUrl::boardForUser($notifiable, $this->task->project)
                        : PanelUrl::dashboardForUser($notifiable))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
