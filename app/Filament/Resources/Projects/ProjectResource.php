<?php

namespace App\Filament\Resources\Projects;

use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Resources\Projects\RelationManagers\CustomFieldsRelationManager;
use App\Filament\Resources\Projects\RelationManagers\StatusesRelationManager;
use App\Filament\Resources\Projects\Schemas\ProjectForm;
use App\Filament\Resources\Projects\Tables\ProjectsTable;
use App\Models\Project;
use App\Support\Permissions;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static string|UnitEnum|null $navigationGroup = 'Έργα';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('Έργα');
    }

    public static function getModelLabel(): string
    {
        return __('Έργο');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Έργα');
    }

    /**
     * Everyone in the company sees the projects; only the people who run it
     * create them or decide what columns a board has.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can(Permissions::TASKS_VIEW) ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(Permissions::TASKS_VIEW) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can(Permissions::PROJECTS_MANAGE) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can(Permissions::PROJECTS_MANAGE) ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can(Permissions::PROJECTS_MANAGE) ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return ProjectForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProjectsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            StatusesRelationManager::class,
            CustomFieldsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjects::route('/'),
            'create' => CreateProject::route('/create'),
            'edit' => EditProject::route('/{record}/edit'),
        ];
    }
}
