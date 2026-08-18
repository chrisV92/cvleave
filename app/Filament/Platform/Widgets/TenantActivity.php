<?php

namespace App\Filament\Platform\Widgets;

use App\Models\Tenant;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Which companies are actually using the product. Counts alone do not say that —
 * a company with ten users and no leave request since March is a churn risk that
 * a headline number hides.
 */
class TenantActivity extends BaseWidget
{
    protected static ?int $sort = -1;

    protected int|string|array $columnSpan = 'full';

    public function getTableHeading(): string
    {
        return __('Δραστηριότητα ανά εταιρεία');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Tenant::query()
                    ->withCount('users')
                    ->withCount(['users as leave_requests_count' => fn (Builder $query) => $query
                        ->join('leave_requests', 'leave_requests.user_id', '=', 'users.id')])
                    ->withMax(['users as last_request_at' => fn (Builder $query) => $query
                        ->join('leave_requests', 'leave_requests.user_id', '=', 'users.id')], 'leave_requests.created_at')
            )
            ->defaultSort('users_count', 'desc')
            ->paginated([5, 10, 25])
            ->columns([
                TextColumn::make('name')
                    ->label(__('Εταιρεία'))
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('users_count')
                    ->label(__('Χρήστες'))
                    ->sortable(),
                TextColumn::make('leave_requests_count')
                    ->label(__('Αιτήσεις άδειας'))
                    ->sortable(),
                TextColumn::make('last_request_at')
                    ->label(__('Τελευταία δραστηριότητα'))
                    ->dateTime('d/m/Y')
                    ->placeholder(__('— καμία —'))
                    ->badge()
                    ->color(fn (?string $state) => match (true) {
                        $state === null => 'danger',
                        Carbon::parse($state)->lt(now()->subDays(60)) => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('created_at')
                    ->label(__('Εγγραφή'))
                    ->date('d/m/Y')
                    ->sortable(),
            ]);
    }
}
