<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use App\Support\PanelUrl;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveEndingSoon extends Notification implements ShouldQueue
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
            __('Ξεκίνησε') => $lr->start_date->format('d/m/Y'),
            __('Λήγει') => $lr->end_date->format('d/m/Y'),
        ];

        if ($this->forAdmin) {
            $details = [__('Υπάλληλος') => $lr->user->name] + $details;

            return (new MailMessage)
                ->subject(__('Λήγει η άδεια του/της :name', ['name' => $lr->user->name]))
                ->view('emails.notification', [
                    'title' => __('Λήγει η άδεια του/της :name', ['name' => $lr->user->name]),
                    'accent' => '#0ea5e9',
                    'accentDark' => '#0369a1',
                    'badgeBg' => '#e0f2fe',
                    'badgeText' => '#0369a1',
                    'badgeLabel' => __('Υπενθύμιση'),
                    'heading' => __('Υπενθύμιση άδειας 📅'),
                    'intro' => __('Η άδεια του/της <strong>:name</strong> λήγει αύριο.', ['name' => $lr->user->name]),
                    'details' => $details,
                    'ctaLabel' => __('Δες το ημερολόγιο'),
                    'ctaUrl' => PanelUrl::dashboardForUser($notifiable),
                ]);
        }

        return (new MailMessage)
            ->subject(__('Η άδειά σου λήγει σύντομα'))
            ->view('emails.notification', [
                'title' => __('Η άδειά σου λήγει σύντομα'),
                'accent' => '#0ea5e9',
                'accentDark' => '#0369a1',
                'badgeBg' => '#e0f2fe',
                'badgeText' => '#0369a1',
                'badgeLabel' => __('Υπενθύμιση'),
                'heading' => __('Υπενθύμιση άδειας 📅'),
                'intro' => __('Η άδειά σου λήγει <strong>αύριο</strong> — τα λέμε πίσω στη δουλειά!'),
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
            ->title(__('Άδεια λήγει: :date', ['date' => $lr->end_date->format('d/m/Y')]))
            ->body("{$who}{$lr->leaveType->name}")
            ->warning()
            ->getDatabaseMessage();
    }
}
