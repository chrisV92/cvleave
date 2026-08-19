<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Models\TaskStatus;
use App\Support\Permissions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class StatusesRelationManager extends RelationManager
{
    protected static string $relationship = 'statuses';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Στήλες Πίνακα');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()?->can(Permissions::PROJECTS_MANAGE) ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('Όνομα'))
                    ->required()
                    ->maxLength(255),

                ColorPicker::make('color')
                    ->label(__('Χρώμα'))
                    ->default('#94a3b8'),

                TextInput::make('position')
                    ->label(__('Σειρά'))
                    ->numeric()
                    ->default(fn () => ($this->getOwnerRecord()->statuses()->max('position') ?? -1) + 1)
                    ->required(),

                Toggle::make('is_default')
                    ->label(__('Εδώ μπαίνουν οι νέες εργασίες'))
                    ->helperText(__('Μόνο μία στήλη μπορεί να είναι η αρχική.')),

                Toggle::make('is_completed')
                    ->label(__('Θεωρείται ολοκληρωμένο'))
                    ->helperText(__('Οι εργασίες που φτάνουν εδώ σημειώνονται ως ολοκληρωμένες.')),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                ColorColumn::make('color')->label(''),

                TextColumn::make('name')
                    ->label(__('Όνομα'))
                    ->weight('bold'),

                TextColumn::make('tasks_count')
                    ->label(__('Εργασίες'))
                    ->counts('tasks')
                    ->alignRight(),

                IconColumn::make('is_default')
                    ->label(__('Αρχική'))
                    ->boolean(),

                IconColumn::make('is_completed')
                    ->label(__('Ολοκληρωμένη'))
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('Προσθήκη Στήλης'))
                    ->mutateDataUsing(function (array $data): array {
                        $data['tenant_id'] = $this->getOwnerRecord()->tenant_id;

                        return $data;
                    })
                    ->after(fn (TaskStatus $record) => $this->enforceSingleDefault($record)),
            ])
            ->recordActions([
                EditAction::make()
                    ->after(fn (TaskStatus $record) => $this->enforceSingleDefault($record)),

                DeleteAction::make()
                    // The database refuses this anyway (ON DELETE RESTRICT);
                    // catching it here turns a 500 into an explanation.
                    ->before(function (TaskStatus $record, DeleteAction $action) {
                        if ($record->tasks()->exists()) {
                            Notification::make()
                                ->title(__('Η στήλη δεν είναι άδεια'))
                                ->body(__('Μετακίνησε πρώτα τις εργασίες της σε άλλη στήλη.'))
                                ->danger()
                                ->send();

                            $action->cancel();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * "New tasks land here" only means something if exactly one column claims
     * it, so setting it on one clears it everywhere else on the board.
     */
    protected function enforceSingleDefault(TaskStatus $record): void
    {
        if (! $record->is_default) {
            return;
        }

        $this->getOwnerRecord()
            ->statuses()
            ->whereKeyNot($record->getKey())
            ->update(['is_default' => false]);
    }
}
