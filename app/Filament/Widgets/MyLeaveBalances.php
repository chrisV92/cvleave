<?php

namespace App\Filament\Widgets;

use App\Services\LeaveBalanceService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MyLeaveBalances extends BaseWidget
{
    protected static ?int $sort = -1;

    public function getStats(): array
    {
        $service = app(LeaveBalanceService::class);
        $year = now()->year;
        $stats = [];

        foreach ($service->summaryForUser(auth()->user(), $year) as $summary) {
            // Days carried over from last year get their own card, ahead of the
            // current-year one, because they expire and should be used first.
            if ($summary->carryoverRemaining > 0) {
                $stats[] = Stat::make(
                    $summary->leaveType->name.' — '.__('από :year', ['year' => $year - 1]),
                    __(':remaining / :entitled μέρες', [
                        'remaining' => $summary->carryoverRemaining,
                        'entitled' => $summary->carryoverEntitled,
                    ])
                )
                    ->description(__('Λήγουν :date — χρησιμοποιούνται πρώτες', [
                        'date' => $summary->carryoverExpiresAt?->format('d/m/Y'),
                    ]))
                    ->color('warning');
            }

            $stats[] = Stat::make(
                $summary->leaveType->name,
                __(':remaining / :entitled μέρες', [
                    'remaining' => $summary->remaining,
                    'entitled' => $summary->entitled,
                ])
            )
                ->description(__('Χρησιμοποιήθηκαν :used μέρες το :year', [
                    'used' => $summary->used,
                    'year' => $year,
                ]))
                ->color($summary->remaining > 0 ? 'success' : 'danger');
        }

        return $stats;
    }
}
