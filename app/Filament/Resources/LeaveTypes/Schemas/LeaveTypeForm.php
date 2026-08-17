<?php

namespace App\Filament\Resources\LeaveTypes\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LeaveTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('Όνομα'))
                    ->required()
                    ->maxLength(255),
                ColorPicker::make('color')
                    ->label(__('Χρώμα'))
                    ->required()
                    ->default('#6366f1'),
                Toggle::make('requires_note')
                    ->label(__('Απαιτεί σημείωση/αιτιολογία'))
                    ->default(false),
                Toggle::make('auto_calculate')
                    ->label(__('Αυτόματος υπολογισμός βάσει προϋπηρεσίας'))
                    ->live()
                    ->default(true),
                Toggle::make('use_greek_law_formula')
                    ->label(__('Χρήση ελληνικής νομοθεσίας (Α.Ν. 539/1945)'))
                    ->helperText(__('Αυτόματος υπολογισμός 20/21/22/25/26 ημερών βάσει έτους απασχόλησης και συνολικής προϋπηρεσίας (σε οποιονδήποτε εργοδότη). Αν ενεργό, αγνοούνται οι παρακάτω Κανόνες Υπολογισμού.'))
                    ->live()
                    ->default(false)
                    ->visible(fn ($get) => $get('auto_calculate')),
                TextInput::make('fixed_days_per_year')
                    ->label(__('Σταθερές μέρες/έτος'))
                    ->numeric()
                    ->minValue(0)
                    ->visible(fn ($get) => ! $get('auto_calculate'))
                    ->required(fn ($get) => ! $get('auto_calculate')),
                Toggle::make('is_active')
                    ->label(__('Ενεργό'))
                    ->default(true),
            ]);
    }
}
