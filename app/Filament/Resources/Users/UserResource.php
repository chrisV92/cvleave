<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\RelationManagers\LeaveBalancesRelationManager;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use App\Support\Permissions;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 1;

    /**
     * Resolved per request rather than set as a static property: a
     * property is evaluated when the class loads, which freezes the
     * group name into whichever language happened to be active first.
     */
    public static function getNavigationGroup(): ?string
    {
        return __('Εταιρεία');
    }

    public static function getNavigationLabel(): string
    {
        return __('Χρήστες');
    }

    public static function getModelLabel(): string
    {
        return __('Χρήστης');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Χρήστες');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can(Permissions::USERS_MANAGE) ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(Permissions::USERS_MANAGE) ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            LeaveBalancesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
