<?php

namespace App\Filament\Platform\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Spatie\Permission\PermissionRegistrar;

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
                TextColumn::make('tenant.name')
                    ->label(__('Εταιρεία'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('role')
                    ->label(__('Ρόλος'))
                    ->badge()
                    ->getStateUsing(function (User $record) {
                        app(PermissionRegistrar::class)->setPermissionsTeamId($record->tenant_id);

                        return $record->getRoleNames()->first();
                    })
                    ->color(fn (?string $state) => $state === 'admin' ? 'primary' : 'gray')
                    ->formatStateUsing(fn (?string $state) => match ($state) {
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
                SelectFilter::make('tenant_id')
                    ->label(__('Εταιρεία'))
                    ->relationship('tenant', 'name'),
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
