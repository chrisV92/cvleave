<?php

namespace App\Filament\Resources\Projects;

use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Resources\Projects\Pages\ProjectBoard;
use App\Filament\Resources\Projects\RelationManagers\CustomFieldsRelationManager;
use App\Filament\Resources\Projects\RelationManagers\StatusesRelationManager;
use App\Filament\Resources\Projects\Schemas\ProjectForm;
use App\Filament\Resources\Projects\Tables\ProjectsTable;
use App\Models\Project;
use App\Support\Permissions;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

use function Filament\Support\original_request;

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

    /**
     * The sidebar entry, with each active project hanging beneath it.
     *
     * A board is what somebody actually wants when they think about a project,
     * so each child goes straight to it rather than to the settings form.
     *
     * The parent carries no URL of its own, which is what makes Filament keep
     * the children visible — it only expands an item that has a URL once that
     * item or one of its children is active. "Όλα τα έργα" is the first child
     * so the full list, including archived projects, stays one click away.
     */
    public static function getNavigationItems(): array
    {
        $items = parent::getNavigationItems();

        if ($items === [] || ! (auth()->user()?->can(Permissions::TASKS_VIEW) ?? false)) {
            return $items;
        }

        $tenant = Filament::getTenant();

        if (! $tenant) {
            return $items;
        }

        $listUrl = static::getUrl('index', tenant: $tenant);

        $children = [
            NavigationItem::make(__('Όλα τα έργα'))
                ->url($listUrl)
                ->isActiveWhen(fn (): bool => original_request()->url() === $listUrl),

            ...static::boardNavigationItems($tenant),
        ];

        return [
            $items[0]
                ->url(null)
                ->childItems($children),
        ];
    }

    /** @return array<NavigationItem> */
    protected static function boardNavigationItems(Model $tenant): array
    {
        return Project::query()
            ->where('tenant_id', $tenant->getKey())
            ->active()
            ->orderBy('position')
            ->orderBy('name')
            ->get()
            ->map(function (Project $project) use ($tenant) {
                $url = static::getUrl('board', ['record' => $project], tenant: $tenant);

                return NavigationItem::make($project->name)
                    ->url($url)
                    ->isActiveWhen(fn (): bool => original_request()->url() === $url);
            })
            ->all();
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
            'board' => ProjectBoard::route('/{record}/board'),
        ];
    }
}
