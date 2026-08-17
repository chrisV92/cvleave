<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Όνομα'))
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('email')
                    ->label(__('Διεύθυνση Email'))
                    ->searchable(),
                BadgeColumn::make('role')
                    ->label(__('Ρόλος'))
                    ->colors([
                        'primary' => 'admin',
                        'gray' => 'employee',
                    ])
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'admin' => __('Admin'),
                        'employee' => __('Υπάλληλος'),
                        default => $state,
                    }),
                TextColumn::make('hire_date')
                    ->label(__('Πρόσληψη'))
                    ->date('d/m/Y')
                    ->sortable(),
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
