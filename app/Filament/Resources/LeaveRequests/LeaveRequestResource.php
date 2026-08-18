<?php

namespace App\Filament\Resources\LeaveRequests;

use App\Filament\Resources\LeaveRequests\Pages\CreateLeaveRequest;
use App\Filament\Resources\LeaveRequests\Pages\EditLeaveRequest;
use App\Filament\Resources\LeaveRequests\Pages\ListLeaveRequests;
use App\Filament\Resources\LeaveRequests\Schemas\LeaveRequestForm;
use App\Filament\Resources\LeaveRequests\Tables\LeaveRequestsTable;
use App\Models\LeaveRequest;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LeaveRequestResource extends Resource
{
    protected static ?string $model = LeaveRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    /**
     * LeaveRequest has no direct tenant relationship — it's scoped to the
     * current tenant indirectly through its `user` in getEloquentQuery()
     * below, so Filament's automatic tenant-ownership scoping is disabled.
     */
    protected static bool $isScopedToTenant = false;

    public static function getNavigationLabel(): string
    {
        return __('Αιτήσεις Άδειας');
    }

    public static function getModelLabel(): string
    {
        return __('Αίτηση Άδειας');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Αιτήσεις Άδειας');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->whereHas('user', fn (Builder $query) => $query->where('tenant_id', Filament::getTenant()?->id));

        if (! (auth()->user()?->isAdmin() ?? false)) {
            $query->where('user_id', auth()->id());
        }

        return $query;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()?->isAdmin() ?? false) {
            return true;
        }

        return $record->user_id === auth()->id() && $record->status === LeaveRequest::STATUS_PENDING;
    }

    public static function canDelete(Model $record): bool
    {
        return static::canEdit($record);
    }

    public static function form(Schema $schema): Schema
    {
        return LeaveRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeaveRequestsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeaveRequests::route('/'),
            'create' => CreateLeaveRequest::route('/create'),
            'edit' => EditLeaveRequest::route('/{record}/edit'),
        ];
    }
}
