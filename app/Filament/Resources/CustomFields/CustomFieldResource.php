<?php

namespace App\Filament\Resources\CustomFields;

use App\Filament\Resources\CustomFields\Pages\ListCustomFields;
use App\Filament\Resources\CustomFields\Schemas\CustomFieldForm;
use App\Filament\Resources\CustomFields\Tables\CustomFieldsTable;
use App\Models\CustomField;
use App\Support\Permissions;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * The company's own fields — the ones that apply to every board.
 *
 * Fields belonging to a single project are managed from that project instead;
 * this resource deliberately shows only the company-wide ones, so the two
 * scopes never get confused for each other.
 */
class CustomFieldResource extends Resource
{
    protected static ?string $model = CustomField::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Έργα';

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('Πεδία Εταιρείας');
    }

    public static function getModelLabel(): string
    {
        return __('Πεδίο');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Πεδία Εταιρείας');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can(Permissions::PROJECTS_MANAGE) ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(Permissions::PROJECTS_MANAGE) ?? false;
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->companyWide();
    }

    public static function form(Schema $schema): Schema
    {
        return CustomFieldForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomFieldsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomFields::route('/'),
        ];
    }
}
