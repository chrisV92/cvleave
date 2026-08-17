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

        return $service->summaryForUser(auth()->user(), now()->year)
            ->map(function ($summary) {
                return Stat::make(
                    $summary->leaveType->name,
                    __(':remaining / :entitled μέρες', ['remaining' => $summary->remaining, 'entitled' => $summary->entitled])
                )
                    ->description(__('Χρησιμοποιήθηκαν :used μέρες το :year', ['used' => $summary->used, 'year' => now()->year]))
                    ->color($summary->remaining > 0 ? 'success' : 'danger');
            })
            ->toArray();
    }
}
