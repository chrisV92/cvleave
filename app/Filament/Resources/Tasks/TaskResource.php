<?php

namespace App\Filament\Resources\Tasks;

use App\Filament\Resources\Tasks\Pages\CreateTask;
use App\Filament\Resources\Tasks\Pages\EditTask;
use App\Filament\Resources\Tasks\Pages\ListTasks;
use App\Filament\Resources\Tasks\RelationManagers\AttachmentsRelationManager;
use App\Filament\Resources\Tasks\RelationManagers\CommentsRelationManager;
use App\Filament\Resources\Tasks\RelationManagers\TimeEntriesRelationManager;
use App\Filament\Resources\Tasks\Schemas\TaskForm;
use App\Filament\Resources\Tasks\Tables\TasksTable;
use App\Models\Task;
use App\Support\Permissions;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Έργα';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('Εργασίες');
    }

    public static function getModelLabel(): string
    {
        return __('Εργασία');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Εργασίες');
    }

    /**
     * Kept out of the sidebar on purpose.
     *
     * Work is reached through its board, which is where it has context — a
     * flat list of every task across every project is rarely what somebody
     * actually wants. The resource keeps its routes, because the board's
     * cards and its "full page" button link straight to them.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(Permissions::TASKS_VIEW) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can(Permissions::TASKS_MANAGE) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can(Permissions::TASKS_MANAGE) ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can(Permissions::TASKS_MANAGE) ?? false;
    }

    /**
     * Work in an archived project stays readable but drops out of the default
     * list, the same way the project itself does.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNull('archived_at')
            ->whereHas('project', fn (Builder $query) => $query->whereNull('archived_at'));
    }

    public static function form(Schema $schema): Schema
    {
        return TaskForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TasksTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            CommentsRelationManager::class,
            AttachmentsRelationManager::class,
            TimeEntriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTasks::route('/'),
            'create' => CreateTask::route('/create'),
            'edit' => EditTask::route('/{record}/edit'),
        ];
    }
}
