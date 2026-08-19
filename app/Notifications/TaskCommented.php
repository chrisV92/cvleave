<?php

namespace App\Notifications;

use App\Models\TaskComment;
use App\Support\PanelUrl;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class TaskCommented extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public TaskComment $comment) {}

    public function via(object $notifiable): array
    {
        return $notifiable->notificationChannels();
    }

    public function toMail(object $notifiable): MailMessage
    {
        $task = $this->comment->task;

        return (new MailMessage)
            ->subject(__('Νέο σχόλιο: :title', ['title' => $task->title]))
            ->view('emails.notification', [
                'tagline' => __('Διαχείριση Εργασιών'),
                'footerNote' => __('Διαχείριση έργων και εργασιών'),
                'title' => __('Νέο σχόλιο'),
                'accent' => '#0891b2',
                'accentDark' => '#155e75',
                'badgeBg' => '#cffafe',
                'badgeText' => '#155e75',
                'badgeLabel' => __('Σχόλιο'),
                'heading' => __('Νέο σχόλιο σε εργασία 💬'),
                'intro' => __('Ο/Η <strong>:who</strong> σχολίασε στην εργασία <strong>:title</strong>.', [
                    'who' => $this->comment->author?->name ?? __('Κάποιος'),
                    'title' => e($task->title),
                ]),
                'details' => array_filter([
                    __('Έργο') => $task->project?->name,
                    __('Σχόλιο') => Str::limit($this->comment->body, 300),
                ]),
                'ctaLabel' => __('Απάντησε'),
                'ctaUrl' => PanelUrl::taskForUser($notifiable, $task),
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        $task = $this->comment->task;

        return FilamentNotification::make()
            ->title(__('Νέο σχόλιο'))
            ->body(($this->comment->author?->name ?? '').': '.Str::limit($this->comment->body, 80))
            ->info()
            ->actions([
                Action::make('view')
                    ->label(__('Απάντησε'))
                    ->url(PanelUrl::taskForUser($notifiable, $task))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
