<?php

namespace App\Filament\Platform\Resources\Users\Schemas;

use App\Models\Tenant;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->label(__('Εταιρεία'))
                    ->options(fn () => Tenant::query()->pluck('name', 'id'))
                    ->required()
                    ->searchable(),
                TextInput::make('name')
                    ->label(__('Όνομα'))
                    ->required(),
                TextInput::make('email')
                    ->label(__('Διεύθυνση Email'))
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),
                Select::make('role')
                    ->label(__('Ρόλος'))
                    ->options([
                        'admin' => __('Admin'),
                        'employee' => __('Υπάλληλος'),
                    ])
                    ->required()
                    ->default('employee')
                    ->dehydrated()
                    ->afterStateHydrated(function ($component, $record) {
                        if ($record) {
                            app(PermissionRegistrar::class)->setPermissionsTeamId($record->tenant_id);
                            $component->state($record->getRoleNames()->first());
                        }
                    }),
                TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->label(fn (string $operation) => $operation === 'create' ? __('Password (προαιρετικό)') : __('Νέο password (προαιρετικό)'))
                    ->helperText(fn (string $operation) => $operation === 'create'
                        ? __('Άφησέ το κενό για να σταλεί πρόσκληση με email, ώστε να ορίσει μόνος του κωδικό.')
                        : null),
            ]);
    }
}
