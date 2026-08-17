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
        $verb = $this->isApproved() ? __('εγκρίθηκε') : __('απορρίφθηκε');

        $mail = (new MailMessage)
            ->subject(__('Η αίτηση άδειάς σου :verb', ['verb' => $verb]))
            ->greeting($this->isApproved() ? __('Η αίτηση άδειάς σου εγκρίθηκε ✅') : __('Η αίτηση άδειάς σου απορρίφθηκε'))
            ->line(__(':type: :start - :end (:days μέρες).', [
                'type' => $lr->leaveType->name,
                'start' => $lr->start_date->format('d/m/Y'),
                'end' => $lr->end_date->format('d/m/Y'),
                'days' => $lr->days_count,
            ]));

        if (! $this->isApproved() && $lr->rejection_reason) {
            $mail->line(__('Αιτία: :reason', ['reason' => $lr->rejection_reason]));
        }

        return $mail->action(__('Δες την αίτηση'), url('/admin/leave-requests'));
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
