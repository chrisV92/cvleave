<?php

namespace App\Filament\Resources\LeaveRequests\Schemas;

use App\Models\LeaveType;
use App\Services\LeaveBalanceService;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

class LeaveRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        $isAdmin = auth()->user()?->isAdmin() ?? false;

        return $schema
            ->components([
                Select::make('user_id')
                    ->label(__('Υπάλληλος'))
                    ->relationship('user', 'name', fn ($query) => $query->where('tenant_id', Filament::getTenant()?->id))
                    ->required()
                    ->visible($isAdmin)
                    ->default(fn () => auth()->id()),

                Select::make('leave_type_id')
                    ->label(__('Τύπος άδειας'))
                    ->relationship('leaveType', 'name', fn ($query) => $query->where('is_active', true)->where('tenant_id', Filament::getTenant()?->id))
                    ->required()
                    ->live(),

                Select::make('duration_type')
                    ->label(__('Τύπος Διάρκειας'))
                    ->options([
                        'full_day' => __('Ολόκληρη Μέρα'),
                        'half_day' => __('Μισή Μέρα'),
                        'hours' => __('Ώρες'),
                    ])
                    ->default('full_day')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, $get, $set) {
                        if ($state !== 'full_day' && $get('start_date')) {
                            $set('end_date', $get('start_date'));
                        }
                        self::recalculateDays($get, $set);
                    }),

                DatePicker::make('start_date')
                    ->label(__('Από'))
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, $get, $set) {
                        if ($get('duration_type') !== 'full_day') {
                            $set('end_date', $state);
                        }
                        self::recalculateDays($get, $set);
                    }),

                DatePicker::make('end_date')
                    ->label(__('Έως'))
                    ->required(fn ($get) => ($get('duration_type') ?? 'full_day') === 'full_day')
                    ->live()
                    ->visible(fn ($get) => ($get('duration_type') ?? 'full_day') === 'full_day')
                    ->dehydrated()
                    ->afterStateUpdated(fn ($state, $get, $set) => self::recalculateDays($get, $set))
                    ->afterOrEqual('start_date'),

                TextInput::make('hours')
                    ->label(__('Ώρες'))
                    ->numeric()
                    ->minValue(0.5)
                    ->maxValue(7.5)
                    ->step(0.5)
                    ->visible(fn ($get) => $get('duration_type') === 'hours')
                    ->required(fn ($get) => $get('duration_type') === 'hours')
                    ->live()
                    ->afterStateUpdated(fn ($get, $set) => self::recalculateDays($get, $set)),

                TextInput::make('days_count')
                    ->label(__('Μέρες (ισοδύναμο)'))
                    ->numeric()
                    ->required()
                    ->disabled()
                    ->dehydrated(),

                Textarea::make('note')
                    ->label(__('Σημείωση / Αιτιολογία'))
                    ->columnSpanFull()
                    ->visible(function ($get) {
                        $leaveType = $get('leave_type_id') ? LeaveType::find($get('leave_type_id')) : null;

                        return $leaveType?->requires_note ?? false;
                    })
                    ->required(function ($get) {
                        $leaveType = $get('leave_type_id') ? LeaveType::find($get('leave_type_id')) : null;

                        return $leaveType?->requires_note ?? false;
                    }),

                Select::make('status')
                    ->label(__('Κατάσταση'))
                    ->options([
                        'pending' => __('Εκκρεμεί'),
                        'approved' => __('Εγκρίθηκε'),
                        'rejected' => __('Απορρίφθηκε'),
                        'cancelled' => __('Ακυρώθηκε'),
                    ])
                    ->default('pending')
                    ->visible($isAdmin)
                    ->required($isAdmin),

                Textarea::make('rejection_reason')
                    ->label(__('Αιτία απόρριψης'))
                    ->columnSpanFull()
                    ->visible(fn ($get) => $isAdmin && $get('status') === 'rejected'),
            ]);
    }

    protected static function recalculateDays($get, $set): void
    {
        $start = $get('start_date');

        if (! $start) {
            return;
        }

        $unit = $get('duration_type') ?? 'full_day';

        if ($unit === 'hours') {
            $hours = (float) ($get('hours') ?? 0);
            $set('days_count', round($hours / 8, 3));

            return;
        }

        if ($unit === 'half_day') {
            $set('days_count', 0.5);

            return;
        }

        $end = $get('end_date');

        if (! $end) {
            return;
        }

        $start = Carbon::parse($start);
        $end = Carbon::parse($end);

        if ($end->lessThan($start)) {
            return;
        }

        $service = app(LeaveBalanceService::class);
        $set('days_count', $service->countBusinessDays($start, $end));
    }
}
