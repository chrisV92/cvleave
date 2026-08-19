<?php

namespace App\Filament\Resources\CustomFields\Schemas;

use App\Support\CustomFieldType;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

/**
 * The definition form for a custom field.
 *
 * Shared between the company-wide resource and the per-project relation
 * manager — the two differ only in which records they scope to, not in what a
 * field is.
 */
class CustomFieldForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('Όνομα'))
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $operation, $state, callable $set) {
                        if ($operation === 'create') {
                            $set('key', Str::slug((string) $state, '_'));
                        }
                    }),

                TextInput::make('key')
                    ->label(__('Αναγνωριστικό'))
                    ->required()
                    ->maxLength(255)
                    ->helperText(__('Σταθερό όνομα για αναφορές και εξαγωγές.')),

                Select::make('type')
                    ->label(__('Τύπος'))
                    ->options(CustomFieldType::labels())
                    ->required()
                    ->live()
                    // The answers live in a column chosen by the type, so
                    // changing it on a field that already holds values would
                    // strand them in a column nothing reads.
                    ->disabled(fn (string $operation) => $operation === 'edit')
                    ->dehydrated()
                    ->helperText(fn (string $operation) => $operation === 'edit'
                        ? __('Ο τύπος δεν αλλάζει μετά τη δημιουργία.')
                        : null),

                Repeater::make('options')
                    ->label(__('Επιλογές'))
                    ->simple(
                        TextInput::make('value')
                            ->label(__('Επιλογή'))
                            ->required(),
                    )
                    ->visible(fn (Get $get) => CustomFieldType::tryFrom((string) $get('type'))?->hasOptions() ?? false)
                    ->columnSpanFull(),

                TextInput::make('help_text')
                    ->label(__('Βοηθητικό κείμενο'))
                    ->maxLength(255)
                    ->columnSpanFull(),

                Toggle::make('is_required')
                    ->label(__('Υποχρεωτικό')),

                Toggle::make('is_active')
                    ->label(__('Ενεργό'))
                    ->default(true),
            ]);
    }
}
