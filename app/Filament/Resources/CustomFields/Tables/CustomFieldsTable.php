<?php

namespace App\Filament\Resources\CustomFields\Tables;

use App\Models\CustomField;
use App\Support\CustomFieldType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomFieldsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                TextColumn::make('name')
                    ->label(__('Όνομα'))
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (CustomField $record) => $record->key),

                TextColumn::make('type')
                    ->label(__('Τύπος'))
                    ->badge()
                    ->formatStateUsing(fn (CustomFieldType $state) => $state->label()),

                TextColumn::make('values_count')
                    ->label(__('Συμπληρωμένες'))
                    ->counts('values')
                    ->alignRight(),

                IconColumn::make('is_required')
                    ->label(__('Υποχρεωτικό'))
                    ->boolean(),

                IconColumn::make('is_active')
                    ->label(__('Ενεργό'))
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription(__('Θα διαγραφούν και οι τιμές που έχουν καταχωρηθεί σε αυτό το πεδίο.')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
