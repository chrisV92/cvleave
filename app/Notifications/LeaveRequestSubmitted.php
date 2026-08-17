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

        $details = [
            __('Υπάλληλος') => $lr->user->name,
            __('Τύπος άδειας') => $lr->leaveType->name,
            __('Ημερομηνίες') => "{$lr->start_date->format('d/m/Y')} – {$lr->end_date->format('d/m/Y')}",
            __('Μέρες') => __(':days εργάσιμες μέρες', ['days' => $lr->days_count]),
        ];

        if ($lr->note) {
            $details[__('Σημείωση')] = $lr->note;
        }

        return (new MailMessage)
            ->subject(__('Νέα αίτηση άδειας: :name', ['name' => $lr->user->name]))
            ->view('emails.leave-notification', [
                'title' => __('Νέα αίτηση άδειας'),
                'accent' => '#d97706',
                'accentDark' => '#92400e',
                'badgeBg' => '#fef3c7',
                'badgeText' => '#92400e',
                'badgeLabel' => __('Νέα Αίτηση'),
                'heading' => __('Νέα αίτηση άδειας 📝'),
                'intro' => __('Ο/Η <strong>:name</strong> υπέβαλε αίτηση για <strong>:type</strong>.', [
                    'name' => $lr->user->name,
                    'type' => $lr->leaveType->name,
                ]),
                'details' => $details,
                'ctaLabel' => __('Δες την αίτηση'),
                'ctaUrl' => url('/admin/leave-requests?tableFilters[status][value]=pending'),
            ]);
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
