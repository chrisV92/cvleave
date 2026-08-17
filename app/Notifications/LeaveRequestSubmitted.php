<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveRequestSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public LeaveRequest $leaveRequest)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $lr = $this->leaveRequest;

        return (new MailMessage)
            ->subject(__('Νέα αίτηση άδειας: :name', ['name' => $lr->user->name]))
            ->greeting(__('Νέα αίτηση άδειας'))
            ->line(__('Ο/Η :name υπέβαλε αίτηση για :type.', ['name' => $lr->user->name, 'type' => $lr->leaveType->name]))
            ->line(__('Από :start έως :end (:days μέρες).', [
                'start' => $lr->start_date->format('d/m/Y'),
                'end' => $lr->end_date->format('d/m/Y'),
                'days' => $lr->days_count,
            ]))
            ->when($lr->note, fn ($mail) => $mail->line(__('Σημείωση: :note', ['note' => $lr->note])))
            ->action(__('Δες την αίτηση'), url('/admin/leave-requests?tableFilters[status][value]=pending'));
    }

    public function toDatabase(object $notifiable): array
    {
        $lr = $this->leaveRequest;

        return FilamentNotification::make()
            ->title(__('Νέα αίτηση άδειας'))
            ->body("{$lr->user->name} — {$lr->leaveType->name} ({$lr->start_date->format('d/m')} - {$lr->end_date->format('d/m')})")
            ->info()
            ->actions([
                Action::make('view')
                    ->label(__('Δες την αίτηση'))
                    ->url('/admin/leave-requests?tableFilters[status][value]=pending')
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
