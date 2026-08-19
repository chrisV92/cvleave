<?php

namespace App\Filament\Resources\Projects\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;

class ProjectForm
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
                        // Only while creating: the slug is in every link to the
                        // board once it exists, so renaming must not move it.
                        if ($operation === 'create') {
                            $set('slug', Str::slug($state));
                        }
                    }),

                TextInput::make('slug')
                    ->label(__('Αναγνωριστικό'))
                    ->required()
                    ->maxLength(255)
                    ->helperText(__('Χρησιμοποιείται στο URL του έργου. Δεν αλλάζει όταν μετονομάζεις το έργο.'))
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule) => $rule->where('tenant_id', Filament::getTenant()?->id),
                    ),

                Textarea::make('description')
                    ->label(__('Περιγραφή'))
                    ->rows(3)
                    ->columnSpanFull(),

                Select::make('owner_id')
                    ->label(__('Υπεύθυνος'))
                    ->relationship(
                        'owner',
                        'name',
                        fn ($query) => $query->where('tenant_id', Filament::getTenant()?->id),
                    )
                    ->searchable()
                    ->preload(),

                ColorPicker::make('color')
                    ->label(__('Χρώμα'))
                    ->default('#6366f1'),
            ]);
    }
}
