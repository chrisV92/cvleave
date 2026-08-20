<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Mail only — the recipient cannot sign in yet, so an in-app database
 * notification would have nowhere to be seen.
 */
class UserInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        /** @var User $notifiable */
        $company = $notifiable->tenant?->name;

        $details = array_filter([
            __('Εταιρεία') => $company,
            __('Διεύθυνση Email') => $notifiable->email,
            __('Ισχύει έως') => now()
                ->addDays(User::INVITATION_VALID_FOR_DAYS)
                ->format('d/m/Y'),
        ]);

        return (new MailMessage)
            ->subject(__('Πρόσκληση στο CVCorp'))
            ->view('emails.notification', [
                'title' => __('Πρόσκληση στο CVCorp'),
                'accent' => '#4f46e5',
                'accentDark' => '#4338ca',
                'badgeBg' => '#eef2ff',
                'badgeText' => '#4338ca',
                'badgeLabel' => __('Πρόσκληση'),
                'heading' => __('Καλώς ήρθες στο CVCorp 👋'),
                'intro' => $company
                    ? __('Ο διαχειριστής της <strong>:company</strong> σου δημιούργησε λογαριασμό. Όρισε τον κωδικό σου για να ξεκινήσεις.', ['company' => $company])
                    : __('Σου δημιουργήθηκε λογαριασμός. Όρισε τον κωδικό σου για να ξεκινήσεις.'),
                'details' => $details,
                'ctaLabel' => __('Όρισε τον κωδικό μου'),
                'ctaUrl' => route('invitation.show', ['token' => $this->token]),
            ]);
    }
}
