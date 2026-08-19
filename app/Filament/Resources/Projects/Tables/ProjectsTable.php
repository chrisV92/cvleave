<?php

namespace App\Filament\Resources\Projects\Tables;

use App\Models\Project;
use App\Support\Permissions;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        $canManage = auth()->user()?->can(Permissions::PROJECTS_MANAGE) ?? false;

        return $table
            ->defaultSort('position')
            ->columns([
                ColorColumn::make('color')
                    ->label(''),

                TextColumn::make('name')
                    ->label(__('Όνομα'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Project $record) => $record->description),

                TextColumn::make('owner.name')
                    ->label(__('Υπεύθυνος'))
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('tasks_count')
                    ->label(__('Εργασίες'))
                    ->counts('tasks')
                    ->alignRight(),

                TextColumn::make('open_tasks_count')
                    ->label(__('Ανοιχτές'))
                    ->state(fn (Project $record) => $record->tasks()->whereNull('completed_at')->count())
                    ->alignRight(),

                TextColumn::make('archived_at')
                    ->label(__('Κατάσταση'))
                    ->badge()
                    ->color(fn (Project $record) => $record->isArchived() ? 'gray' : 'success')
                    ->formatStateUsing(fn (Project $record) => $record->isArchived()
                        ? __('Στο αρχείο')
                        : __('Ενεργό')),
            ])
            ->filters([
                TernaryFilter::make('archived')
                    ->label(__('Αρχειοθετημένα'))
                    ->placeholder(__('Μόνο ενεργά'))
                    ->trueLabel(__('Μόνο αρχειοθετημένα'))
                    ->falseLabel(__('Όλα'))
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('archived_at'),
                        false: fn (Builder $query) => $query,
                        blank: fn (Builder $query) => $query->whereNull('archived_at'),
                    ),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible($canManage),

                // Archiving is the safe counterpart to deletion: the board
                // disappears from the list without taking its history with it.
                Action::make('archive')
                    ->label(fn (Project $record) => $record->isArchived() ? __('Επαναφορά') : __('Αρχειοθέτηση'))
                    ->icon(fn (Project $record) => $record->isArchived()
                        ? 'heroicon-o-arrow-uturn-left'
                        : 'heroicon-o-archive-box')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible($canManage)
                    ->action(fn (Project $record) => $record->update([
                        'archived_at' => $record->isArchived() ? null : now(),
                    ])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible($canManage),
                ]),
            ]);
    }
}
