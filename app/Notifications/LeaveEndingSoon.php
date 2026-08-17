<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveEndingSoon extends Notification implements ShouldQueue
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
                ->subject(__('Λήγει η άδεια του/της :name', ['name' => $lr->user->name]))
                ->line(__('Η άδεια του/της :name (:type) λήγει στις :date.', [
                    'name' => $lr->user->name,
                    'type' => $lr->leaveType->name,
                    'date' => $lr->end_date->format('d/m/Y'),
                ]));
        }

        return (new MailMessage)
            ->subject(__('Η άδειά σου λήγει σύντομα'))
            ->greeting(__('Υπενθύμιση άδειας'))
            ->line(__('Η άδειά σου (:type) λήγει στις :date. Επιστροφή στην εργασία μετά από αυτή την ημερομηνία.', [
                'type' => $lr->leaveType->name,
                'date' => $lr->end_date->format('d/m/Y'),
            ]));
    }

    public function toDatabase(object $notifiable): array
    {
        $lr = $this->leaveRequest;
        $who = $this->forAdmin ? "{$lr->user->name} — " : '';

        return FilamentNotification::make()
            ->title(__('Άδεια λήγει: :date', ['date' => $lr->end_date->format('d/m/Y')]))
            ->body("{$who}{$lr->leaveType->name}")
            ->warning()
            ->getDatabaseMessage();
    }
}
