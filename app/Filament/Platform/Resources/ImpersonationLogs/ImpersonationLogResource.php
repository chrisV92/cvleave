<?php

namespace App\Filament\Platform\Resources\ImpersonationLogs;

use App\Filament\Platform\Resources\ImpersonationLogs\Pages\ListImpersonationLogs;
use App\Filament\Platform\Resources\ImpersonationLogs\Tables\ImpersonationLogsTable;
use App\Models\ImpersonationLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ImpersonationLogResource extends Resource
{
    protected static ?string $model = ImpersonationLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEye;

    public static function getNavigationLabel(): string
    {
        return __('Ιστορικό Impersonation');
    }

    public static function getModelLabel(): string
    {
        return __('Impersonation');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Ιστορικό Impersonation');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return ImpersonationLogsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListImpersonationLogs::route('/'),
        ];
    }
}
