<?php

namespace App\Filament\Platform\Widgets;

use App\Models\LeaveRequest;
use App\Models\Tenant;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformOverview extends BaseWidget
{
    protected static ?int $sort = -3;

    protected function getStats(): array
    {
        $startOfMonth = now()->startOfMonth();

        $companies = Tenant::count();
        $newCompanies = Tenant::where('created_at', '>=', $startOfMonth)->count();

        $users = User::count();
        $newUsers = User::where('created_at', '>=', $startOfMonth)->count();

        $pendingInvitations = User::whereNotNull('invitation_token')->count();

        $requestsThisYear = LeaveRequest::whereYear('start_date', now()->year)->count();
        $pendingRequests = LeaveRequest::where('status', LeaveRequest::STATUS_PENDING)->count();

        return [
            Stat::make(__('Εταιρείες'), $companies)
                ->description(__(':count νέες αυτόν τον μήνα', ['count' => $newCompanies]))
                ->color($newCompanies > 0 ? 'success' : 'gray'),

            Stat::make(__('Χρήστες'), $users)
                ->description(__(':count νέοι αυτόν τον μήνα', ['count' => $newUsers]))
                ->color($newUsers > 0 ? 'success' : 'gray'),

            Stat::make(__('Εκκρεμείς προσκλήσεις'), $pendingInvitations)
                ->description(__('Δεν έχουν ορίσει ακόμα κωδικό'))
                ->color($pendingInvitations > 0 ? 'warning' : 'gray'),

            Stat::make(__('Αιτήσεις άδειας :year', ['year' => now()->year]), $requestsThisYear)
                ->description(__(':count εκκρεμούν προς έγκριση', ['count' => $pendingRequests]))
                ->color($pendingRequests > 0 ? 'warning' : 'gray'),
        ];
    }
}
