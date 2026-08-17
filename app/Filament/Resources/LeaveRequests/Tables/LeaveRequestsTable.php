<?php

namespace App\Filament\Resources\LeaveRequests\Tables;

use App\Filament\Exports\LeaveRequestExporter;
use App\Filament\Resources\LeaveRequests\LeaveRequestResource;
use App\Models\LeaveRequest;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportBulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LeaveRequestsTable
{
    public static function configure(Table $table): Table
    {
        $isAdmin = auth()->user()?->isAdmin() ?? false;

        return $table
            ->recordUrl(fn (LeaveRequest $record) => LeaveRequestResource::canEdit($record)
                ? LeaveRequestResource::getUrl('edit', ['record' => $record])
                : null)
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('Υπάλληλος'))
                    ->searchable()
                    ->visible($isAdmin),
                TextColumn::make('leaveType.name')
                    ->label(__('Τύπος'))
                    ->badge()
                    ->color(fn ($record) => $record->leaveType?->color),
                TextColumn::make('start_date')
                    ->label(__('Από'))
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label(__('Έως'))
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('days_count')
                    ->label(__('Μέρες')),
                BadgeColumn::make('status')
                    ->label(__('Κατάσταση'))
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'gray' => 'cancelled',
                    ])
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'pending' => __('Εκκρεμεί'),
                        'approved' => __('Εγκρίθηκε'),
                        'rejected' => __('Απορρίφθηκε'),
                        'cancelled' => __('Ακυρώθηκε'),
                        default => $state,
                    }),
                TextColumn::make('reviewer.name')
                    ->label(__('Έλεγχος από'))
                    ->placeholder('—')
                    ->visible($isAdmin),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('Κατάσταση'))
                    ->options([
                        'pending' => __('Εκκρεμεί'),
                        'approved' => __('Εγκρίθηκε'),
                        'rejected' => __('Απορρίφθηκε'),
                        'cancelled' => __('Ακυρώθηκε'),
                    ]),
            ])
            ->defaultSort('start_date', 'desc')
            ->recordActions([
                Action::make('approve')
                    ->label(__('Έγκριση'))
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (LeaveRequest $record) => $isAdmin && $record->status === LeaveRequest::STATUS_PENDING)
                    ->requiresConfirmation()
                    ->action(function (LeaveRequest $record) {
                        $record->update(['status' => LeaveRequest::STATUS_APPROVED]);
                        Notification::make()->title(__('Η αίτηση εγκρίθηκε'))->success()->send();
                    }),
                Action::make('reject')
                    ->label(__('Απόρριψη'))
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (LeaveRequest $record) => $isAdmin && $record->status === LeaveRequest::STATUS_PENDING)
                    ->requiresConfirmation()
                    ->schema([
                        Textarea::make('rejection_reason')
                            ->label(__('Αιτία απόρριψης'))
                            ->required(),
                    ])
                    ->action(function (LeaveRequest $record, array $data) {
                        $record->update([
                            'status' => LeaveRequest::STATUS_REJECTED,
                            'rejection_reason' => $data['rejection_reason'],
                        ]);
                        Notification::make()->title(__('Η αίτηση απορρίφθηκε'))->danger()->send();
                    }),
                EditAction::make()
                    ->visible(fn (LeaveRequest $record) => LeaveRequestResource::canEdit($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->label(__('Εξαγωγή Excel'))
                        ->exporter(LeaveRequestExporter::class),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
