<?php

namespace App\Filament\Resources\Users\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class LeaveBalancesRelationManager extends RelationManager
{
    protected static string $relationship = 'leaveBalances';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Χειροκίνητες Ρυθμίσεις Υπολοίπου');
    }

    protected static function getModelLabel(): ?string
    {
        return __('ρύθμιση υπολοίπου');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('leave_type_id')
                    ->label(__('Τύπος άδειας'))
                    ->relationship('leaveType', 'name')
                    ->required(),
                TextInput::make('year')
                    ->label(__('Έτος'))
                    ->numeric()
                    ->default(now()->year)
                    ->minValue(2020)
                    ->required()
                    ->unique(
                        table: 'leave_balances',
                        ignoreRecord: true,
                        modifyRuleUsing: fn ($rule, $get) => $rule
                            ->where('user_id', $this->getOwnerRecord()->id)
                            ->where('leave_type_id', $get('leave_type_id')),
                    )
                    ->validationMessages([
                        'unique' => __('Υπάρχει ήδη ρύθμιση για αυτόν τον τύπο άδειας/έτος.'),
                    ]),
                TextInput::make('manual_override_days')
                    ->label(__('Μέρες (override)'))
                    ->helperText(__('Αντικαθιστά εντελώς τον αυτόματο υπολογισμό για αυτόν τον χρήστη/τύπο/έτος.'))
                    ->numeric()
                    ->step(0.5)
                    ->minValue(0)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('year')
            ->columns([
                TextColumn::make('leaveType.name')
                    ->label(__('Τύπος άδειας')),
                TextColumn::make('year')
                    ->label(__('Έτος')),
                TextColumn::make('manual_override_days')
                    ->label(__('Μέρες (override)')),
            ])
            ->defaultSort('year', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label(__('Προσθήκη Ρύθμισης')),
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
