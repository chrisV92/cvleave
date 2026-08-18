<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use App\Notifications\UserInvitation;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
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
                BadgeColumn::make('roles.name')
                    ->label(__('Ρόλος'))
                    ->colors([
                        'primary' => 'admin',
                        'gray' => 'employee',
                    ])
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
                //
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
                Action::make('pdfReport')
                    ->label(__('Αναφορά PDF'))
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->url(fn (User $record) => route('reports.employee-leave', $record))
                    ->openUrlInNewTab(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
