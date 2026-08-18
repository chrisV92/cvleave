<?php

namespace App\Filament\Platform\Resources\Users\Tables;

use App\Models\User;
use App\Notifications\UserInvitation;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Spatie\Permission\PermissionRegistrar;
use STS\FilamentImpersonate\Actions\Impersonate;

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
                TextColumn::make('invitation_sent_at')
                    ->label(__('Πρόσκληση'))
                    ->badge()
                    ->color('warning')
                    ->placeholder('—')
                    ->getStateUsing(fn (User $record) => match (true) {
                        $record->hasPendingInvitation() => __('Εκκρεμεί'),
                        $record->invitation_token !== null => __('Έληξε'),
                        default => null,
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
                Action::make('resendInvitation')
                    ->label(__('Επαναποστολή πρόσκλησης'))
                    ->icon('heroicon-o-envelope')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (User $record) => $record->invitation_token !== null)
                    ->action(function (User $record) {
                        // A fresh token invalidates the previous link.
                        $record->notify(new UserInvitation($record->generateInvitationToken()));

                        Notification::make()
                            ->title(__('Η πρόσκληση στάλθηκε ξανά'))
                            ->success()
                            ->send();
                    }),
                Impersonate::make()
                    ->label(__('Είσοδος ως'))
                    ->redirectTo(fn (User $record) => "/admin/{$record->tenant->slug}"),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
