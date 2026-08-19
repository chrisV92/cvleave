<?php

namespace App\Notifications;

use App\Support\PanelUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The Monday picture of where things stand.
 *
 * Built by the command rather than here, because what counts as "yours"
 * differs between an employee and an administrator, and the notification
 * should not have to know which it is talking to.
 */
class WeeklyTaskDigest extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<int, array{label: string, color?: string, tasks: array<int, array{title: string, meta: string}>}>  $sections
     */
    public function __construct(
        public array $sections,
        public string $summary,
        public bool $companyWide = false,
    ) {}

    public function via(object $notifiable): array
    {
        // A digest is the one thing that is purely email — the bell already
        // shows everything it summarises, item by item.
        return $notifiable->notify_by_email ? ['mail'] : [];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->companyWide
                ? __('Εβδομαδιαία σύνοψη εργασιών — η εταιρεία')
                : __('Εβδομαδιαία σύνοψη εργασιών'))
            ->view('emails.task-digest', [
                'title' => __('Εβδομαδιαία σύνοψη'),
                'accent' => '#4f46e5',
                'accentDark' => '#3730a3',
                'badgeBg' => '#e0e7ff',
                'badgeText' => '#3730a3',
                'badgeLabel' => __('Εβδομαδιαία'),
                'heading' => $this->companyWide
                    ? __('Πού βρίσκονται τα έργα 📊')
                    : __('Οι εργασίες σου αυτή την εβδομάδα 📋'),
                'intro' => $this->summary,
                'sections' => $this->sections,
                'ctaLabel' => __('Άνοιξε τα έργα'),
                'ctaUrl' => PanelUrl::forUser($notifiable, 'projects'),
            ]);
    }
}
