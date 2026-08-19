<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Filament\Resources\CustomFields\Schemas\CustomFieldForm;
use App\Models\CustomField;
use App\Support\CustomFieldType;
use App\Support\Permissions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CustomFieldsRelationManager extends RelationManager
{
    protected static string $relationship = 'customFields';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Πεδία Έργου');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()?->can(Permissions::PROJECTS_MANAGE) ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return CustomFieldForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                TextColumn::make('name')
                    ->label(__('Όνομα'))
                    ->weight('bold')
                    ->description(fn (CustomField $record) => $record->key),

                TextColumn::make('type')
                    ->label(__('Τύπος'))
                    ->badge()
                    ->formatStateUsing(fn (CustomFieldType $state) => $state->label()),

                IconColumn::make('is_required')
                    ->label(__('Υποχρεωτικό'))
                    ->boolean(),

                IconColumn::make('is_active')
                    ->label(__('Ενεργό'))
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('Προσθήκη Πεδίου'))
                    ->mutateDataUsing(function (array $data): array {
                        $data['tenant_id'] = $this->getOwnerRecord()->tenant_id;
                        $data['position'] = ($this->getOwnerRecord()->customFields()->max('position') ?? -1) + 1;

                        return $data;
                    }),
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
