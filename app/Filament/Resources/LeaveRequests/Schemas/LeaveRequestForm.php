<?php

namespace App\Filament\Resources\LeaveRequests\Schemas;

use App\Models\LeaveType;
use App\Services\LeaveBalanceService;
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
                    ->relationship('user', 'name')
                    ->required()
                    ->visible($isAdmin)
                    ->default(fn () => auth()->id()),

                Select::make('leave_type_id')
                    ->label(__('Τύπος άδειας'))
                    ->relationship('leaveType', 'name', fn ($query) => $query->where('is_active', true))
                    ->required()
                    ->live(),

                DatePicker::make('start_date')
                    ->label(__('Από'))
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn ($state, $get, $set) => self::recalculateDays($get, $set)),

                DatePicker::make('end_date')
                    ->label(__('Έως'))
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn ($state, $get, $set) => self::recalculateDays($get, $set))
                    ->afterOrEqual('start_date'),

                TextInput::make('days_count')
                    ->label(__('Εργάσιμες μέρες'))
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
        $end = $get('end_date');

        if (! $start || ! $end) {
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
