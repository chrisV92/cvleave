<?php

namespace App\Filament\Resources\LeaveTypes\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AccrualRulesRelationManager extends RelationManager
{
    protected static string $relationship = 'accrualRules';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('Κανόνες Υπολογισμού');
    }

    public static function canViewForRecord(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): bool
    {
        return ! $ownerRecord->use_greek_law_formula;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('min_years_service')
                    ->label(__('Από έτη προϋπηρεσίας'))
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                TextInput::make('max_years_service')
                    ->label(__('Έως έτη προϋπηρεσίας (κενό = απεριόριστο)'))
                    ->numeric()
                    ->minValue(0),
                TextInput::make('days_per_year')
                    ->label(__('Μέρες άδειας/έτος'))
                    ->numeric()
                    ->minValue(0)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('min_years_service')
            ->columns([
                TextColumn::make('min_years_service')
                    ->label(__('Από έτη')),
                TextColumn::make('max_years_service')
                    ->label(__('Έως έτη'))
                    ->placeholder(__('απεριόριστο')),
                TextColumn::make('days_per_year')
                    ->label(__('Μέρες/έτος')),
            ])
            ->defaultSort('min_years_service')
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
