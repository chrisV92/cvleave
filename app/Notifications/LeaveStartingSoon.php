<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use App\Support\PanelUrl;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveStartingSoon extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public LeaveRequest $leaveRequest, public bool $forAdmin = false) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $lr = $this->leaveRequest;

        $details = [
            __('Τύπος άδειας') => $lr->leaveType->name,
            __('Ξεκινάει') => $lr->start_date->format('d/m/Y'),
            __('Λήγει') => $lr->end_date->format('d/m/Y'),
        ];

        if ($this->forAdmin) {
            $details = [__('Υπάλληλος') => $lr->user->name] + $details;

            return (new MailMessage)
                ->subject(__('Ξεκινάει η άδεια του/της :name', ['name' => $lr->user->name]))
                ->view('emails.leave-notification', [
                    'title' => __('Ξεκινάει η άδεια του/της :name', ['name' => $lr->user->name]),
                    'accent' => '#d97706',
                    'accentDark' => '#92400e',
                    'badgeBg' => '#fef3c7',
                    'badgeText' => '#92400e',
                    'badgeLabel' => __('Υπενθύμιση'),
                    'heading' => __('Υπενθύμιση άδειας 📅'),
                    'intro' => __('Η άδεια του/της <strong>:name</strong> ξεκινάει αύριο.', ['name' => $lr->user->name]),
                    'details' => $details,
                    'ctaLabel' => __('Δες το ημερολόγιο'),
                    'ctaUrl' => PanelUrl::dashboardForUser($notifiable),
                ]);
        }

        return (new MailMessage)
            ->subject(__('Η άδειά σου ξεκινάει σύντομα'))
            ->view('emails.leave-notification', [
                'title' => __('Η άδειά σου ξεκινάει σύντομα'),
                'accent' => '#d97706',
                'accentDark' => '#92400e',
                'badgeBg' => '#fef3c7',
                'badgeText' => '#92400e',
                'badgeLabel' => __('Υπενθύμιση'),
                'heading' => __('Υπενθύμιση άδειας 📅'),
                'intro' => __('Η άδειά σου ξεκινάει <strong>αύριο</strong> — καλά ξεκούραστα!'),
                'details' => $details,
                'ctaLabel' => __('Δες το ημερολόγιο'),
                'ctaUrl' => PanelUrl::dashboardForUser($notifiable),
            ]);
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
