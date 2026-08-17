<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveStartingSoon extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public LeaveRequest $leaveRequest, public bool $forAdmin = false)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $lr = $this->leaveRequest;

        if ($this->forAdmin) {
            return (new MailMessage)
                ->subject(__('Ξεκινάει η άδεια του/της :name', ['name' => $lr->user->name]))
                ->line(__('Η άδεια του/της :name (:type) ξεκινάει στις :date.', [
                    'name' => $lr->user->name,
                    'type' => $lr->leaveType->name,
                    'date' => $lr->start_date->format('d/m/Y'),
                ]));
        }

        return (new MailMessage)
            ->subject(__('Η άδειά σου ξεκινάει σύντομα'))
            ->greeting(__('Υπενθύμιση άδειας'))
            ->line(__('Η άδειά σου (:type) ξεκινάει στις :start και λήγει στις :end.', [
                'type' => $lr->leaveType->name,
                'start' => $lr->start_date->format('d/m/Y'),
                'end' => $lr->end_date->format('d/m/Y'),
            ]));
    }

    public function toDatabase(object $notifiable): array
    {
        $lr = $this->leaveRequest;
        $who = $this->forAdmin ? "{$lr->user->name} — " : '';

        return FilamentNotification::make()
            ->title(__('Άδεια ξεκινάει: :date', ['date' => $lr->start_date->format('d/m/Y')]))
            ->body("{$who}{$lr->leaveType->name}")
            ->warning()
            ->getDatabaseMessage();
    }
}
