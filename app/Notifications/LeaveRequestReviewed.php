<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveRequestReviewed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public LeaveRequest $leaveRequest)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    protected function isApproved(): bool
    {
        return $this->leaveRequest->status === LeaveRequest::STATUS_APPROVED;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $lr = $this->leaveRequest;
        $approved = $this->isApproved();
        $verb = $approved ? __('εγκρίθηκε') : __('απορρίφθηκε');

        $details = [
            __('Τύπος άδειας') => $lr->leaveType->name,
            __('Ημερομηνίες') => "{$lr->start_date->format('d/m/Y')} – {$lr->end_date->format('d/m/Y')}",
            __('Μέρες') => __(':days εργάσιμες μέρες', ['days' => $lr->days_count]),
        ];

        if (! $approved && $lr->rejection_reason) {
            $details[__('Αιτία απόρριψης')] = $lr->rejection_reason;
        }

        return (new MailMessage)
            ->subject(__('Η αίτηση άδειάς σου :verb', ['verb' => $verb]))
            ->view('emails.leave-notification', [
                'title' => __('Η αίτηση άδειάς σου :verb', ['verb' => $verb]),
                'accent' => $approved ? '#16a34a' : '#dc2626',
                'accentDark' => $approved ? '#166534' : '#991b1b',
                'badgeBg' => $approved ? '#dcfce7' : '#fee2e2',
                'badgeText' => $approved ? '#166534' : '#991b1b',
                'badgeLabel' => $approved ? __('Εγκρίθηκε') : __('Απορρίφθηκε'),
                'heading' => $approved ? __('Η αίτηση άδειάς σου εγκρίθηκε ✅') : __('Η αίτηση άδειάς σου απορρίφθηκε'),
                'intro' => $approved
                    ? __('Καλά νέα! Η αίτηση άδειάς σου εγκρίθηκε από τον διαχειριστή.')
                    : __('Δυστυχώς η αίτηση άδειάς σου απορρίφθηκε — δες την αιτία παρακάτω.'),
                'details' => $details,
                'ctaLabel' => __('Δες την αίτηση'),
                'ctaUrl' => url('/admin/leave-requests'),
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        $lr = $this->leaveRequest;
        $verb = $this->isApproved() ? __('εγκρίθηκε') : __('απορρίφθηκε');

        return FilamentNotification::make()
            ->title(__('Η αίτηση άδειάς σου :verb', ['verb' => $verb]))
            ->body("{$lr->leaveType->name}: {$lr->start_date->format('d/m')} - {$lr->end_date->format('d/m')}")
            ->when($this->isApproved(), fn ($n) => $n->success(), fn ($n) => $n->danger())
            ->actions([
                Action::make('view')
                    ->label(__('Δες την αίτηση'))
                    ->url('/admin/leave-requests')
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
