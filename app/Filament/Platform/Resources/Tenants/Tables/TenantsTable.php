<?php

namespace App\Filament\Platform\Resources\Tenants\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TenantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Όνομα'))
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('slug')
                    ->label(__('Slug'))
                    ->searchable(),
                TextColumn::make('users_count')
                    ->label(__('Χρήστες'))
                    ->counts('users'),
                TextColumn::make('leave_types_count')
                    ->label(__('Τύποι Αδειών'))
                    ->counts('leaveTypes'),
                TextColumn::make('created_at')
                    ->label(__('Δημιουργήθηκε'))
                    ->date('d/m/Y')
                    ->sortable(),
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
