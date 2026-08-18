<?php

namespace App\Filament\Platform\Resources\Tenants\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('Όνομα'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->label(__('Slug'))
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText(__('Χρησιμοποιείται στο URL του panel της εταιρείας, π.χ. /admin/η-εταιρεία-μου')),
                Section::make(__('Πρώτος Admin'))
                    ->description(__('Δημιουργείται αυτόματα ως ο πρώτος διαχειριστής της εταιρείας.'))
                    ->visible(fn (string $operation) => $operation === 'create')
                    ->schema([
                        TextInput::make('admin_name')
                            ->label(__('Όνομα'))
                            ->required()
                            ->dehydrated(),
                        TextInput::make('admin_email')
                            ->label(__('Διεύθυνση Email'))
                            ->email()
                            ->required()
                            ->dehydrated(),
                        TextInput::make('admin_password')
                            ->label(__('Password'))
                            ->password()
                            ->required()
                            ->dehydrated(),
                    ]),
            ]);
    }
}
