<?php

namespace App\Filament\Resources\LeaveTypes;

use App\Filament\Resources\LeaveTypes\Pages\CreateLeaveType;
use App\Filament\Resources\LeaveTypes\Pages\EditLeaveType;
use App\Filament\Resources\LeaveTypes\Pages\ListLeaveTypes;
use App\Filament\Resources\LeaveTypes\RelationManagers\AccrualRulesRelationManager;
use App\Filament\Resources\LeaveTypes\Schemas\LeaveTypeForm;
use App\Filament\Resources\LeaveTypes\Tables\LeaveTypesTable;
use App\Models\LeaveType;
use App\Support\Permissions;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class LeaveTypeResource extends Resource
{
    protected static ?string $model = LeaveType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Άδειες';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('Τύποι Αδειών');
    }

    public static function getModelLabel(): string
    {
        return __('Τύπος Άδειας');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Τύποι Αδειών');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can(Permissions::LEAVE_TYPES_MANAGE) ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(Permissions::LEAVE_TYPES_MANAGE) ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return LeaveTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeaveTypesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AccrualRulesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeaveTypes::route('/'),
            'create' => CreateLeaveType::route('/create'),
            'edit' => EditLeaveType::route('/{record}/edit'),
        ];
    }
}
