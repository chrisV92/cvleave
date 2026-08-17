<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('Όνομα'))
                    ->required(),
                TextInput::make('email')
                    ->label(__('Διεύθυνση Email'))
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),
                Select::make('role')
                    ->options([
                        'admin' => __('Admin'),
                        'employee' => __('Υπάλληλος'),
                    ])
                    ->required()
                    ->default('employee'),
                DatePicker::make('hire_date')
                    ->label(__('Ημερομηνία πρόσληψης')),
                TextInput::make('prior_experience_years')
                    ->label(__('Προϋπηρεσία σε άλλους εργοδότες (έτη)'))
                    ->helperText(__('Δηλωμένη προϋπηρεσία (π.χ. από βεβαιώσεις eΕΦΚΑ) πριν από αυτή τη θέση — μετράει για τα thresholds των 12/25 ετών.'))
                    ->numeric()
                    ->minValue(0)
                    ->step(0.5)
                    ->default(0)
                    ->required(),
                TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $operation) => $operation === 'create')
                    ->label(fn (string $operation) => $operation === 'create' ? __('Password') : __('Νέο password (προαιρετικό)')),
            ]);
    }
}
