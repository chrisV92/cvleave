<?php

namespace App\Filament\Resources\LeaveTypes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LeaveTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ColorColumn::make('color')->label(''),
                TextColumn::make('name')
                    ->label(__('Όνομα'))
                    ->searchable()
                    ->weight('bold'),
                IconColumn::make('requires_note')
                    ->label(__('Σημείωση'))
                    ->boolean(),
                IconColumn::make('auto_calculate')
                    ->label(__('Αυτόματος υπολογισμός'))
                    ->boolean(),
                TextColumn::make('fixed_days_per_year')
                    ->label(__('Σταθερές μέρες'))
                    ->numeric()
                    ->placeholder('—')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label(__('Ενεργό'))
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
