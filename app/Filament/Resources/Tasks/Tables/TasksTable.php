<?php

namespace App\Filament\Resources\Tasks\Tables;

use App\Filament\Resources\Tasks\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Services\CustomFieldSchema;
use App\Support\Permissions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            // The custom field columns read their values from the loaded
            // relation, so without this each one costs a query per row.
            ->modifyQueryUsing(fn (Builder $query) => $query->with('customFieldValues.customField'))
            ->columns([
                TextColumn::make('title')
                    ->label(__('Τίτλος'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap(),

                TextColumn::make('project.name')
                    ->label(__('Έργο'))
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('status.name')
                    ->label(__('Στήλη'))
                    ->badge()
                    ->color(fn (Task $record) => Color::hex($record->status?->color ?? '#94a3b8')),

                TextColumn::make('assignee.name')
                    ->label(__('Ανάθεση'))
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('priority')
                    ->label(__('Προτεραιότητα'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => Task::priorities()[$state] ?? '—')
                    ->color(fn (?string $state) => match ($state) {
                        Task::PRIORITY_URGENT => 'danger',
                        Task::PRIORITY_HIGH => 'warning',
                        Task::PRIORITY_LOW => 'gray',
                        default => 'info',
                    }),

                TextColumn::make('due_date')
                    ->label(__('Προθεσμία'))
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable()
                    // Overdue is only interesting while the work is unfinished.
                    ->color(fn (Task $record) => $record->isOverdue() ? 'danger' : null),

                ...CustomFieldSchema::tableColumns(Filament::getTenant()?->id ?? 0),
            ])
            ->filters([
                SelectFilter::make('project_id')
                    ->label(__('Έργο'))
                    ->options(fn () => Project::query()
                        ->where('tenant_id', Filament::getTenant()?->id)
                        ->active()
                        ->orderBy('name')
                        ->pluck('name', 'id')),

                SelectFilter::make('assignee_id')
                    ->label(__('Ανάθεση'))
                    ->relationship(
                        'assignee',
                        'name',
                        fn ($query) => $query->where('tenant_id', Filament::getTenant()?->id),
                    ),

                SelectFilter::make('priority')
                    ->label(__('Προτεραιότητα'))
                    ->options(Task::priorities()),

                TernaryFilter::make('completed')
                    ->label(__('Ολοκληρωμένες'))
                    ->placeholder(__('Όλες'))
                    ->trueLabel(__('Μόνο ολοκληρωμένες'))
                    ->falseLabel(__('Μόνο ανοιχτές'))
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('completed_at'),
                        false: fn (Builder $query) => $query->whereNull('completed_at'),
                        blank: fn (Builder $query) => $query,
                    ),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (Task $record) => TaskResource::canEdit($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->can(Permissions::TASKS_MANAGE) ?? false),
                ]),
            ]);
    }
}
