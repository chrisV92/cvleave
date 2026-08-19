<?php

namespace App\Filament\Resources\Tasks\RelationManagers;

use App\Models\TaskTimeEntry;
use App\Support\Permissions;
use Filament\Actions\DeleteAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TimeEntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'timeEntries';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Καταγραφή Χρόνου');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return ($ownerRecord->project?->time_tracking_enabled ?? false)
            && (auth()->user()?->can(Permissions::TASKS_VIEW) ?? false);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('started_at', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('Ποιος'))
                    ->weight('bold'),

                TextColumn::make('started_at')
                    ->label(__('Έναρξη'))
                    ->dateTime('d/m/Y H:i'),

                TextColumn::make('ended_at')
                    ->label(__('Λήξη'))
                    ->dateTime('d/m/Y H:i')
                    ->placeholder(__('— σε εξέλιξη —')),

                TextColumn::make('duration')
                    ->label(__('Διάρκεια'))
                    ->getStateUsing(fn (TaskTimeEntry $record) => TaskTimeEntry::humanise($record->seconds()))
                    ->badge()
                    ->color(fn (TaskTimeEntry $record) => $record->isRunning() ? 'success' : 'gray'),
            ])
            ->recordActions([
                // Entries are a record of what happened, so they are not
                // editable — a wrong one is removed by whoever logged it.
                DeleteAction::make()
                    ->visible(fn (TaskTimeEntry $record) => $record->user_id === auth()->id()),
            ]);
    }
}
