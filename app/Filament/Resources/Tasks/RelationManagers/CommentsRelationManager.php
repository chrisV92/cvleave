<?php

namespace App\Filament\Resources\Tasks\RelationManagers;

use App\Models\TaskComment;
use App\Support\Permissions;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Σχόλια');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()?->can(Permissions::TASKS_VIEW) ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('body')
                    ->label(__('Σχόλιο'))
                    ->required()
                    ->rows(4)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        $canWrite = auth()->user()?->can(Permissions::TASKS_MANAGE) ?? false;

        return $table
            ->defaultSort('created_at')
            ->columns([
                TextColumn::make('body')
                    ->label(__('Σχόλιο'))
                    ->wrap(),

                TextColumn::make('author.name')
                    ->label(__('Από'))
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->label(__('Ημερομηνία'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('Νέο Σχόλιο'))
                    ->visible($canWrite)
                    ->mutateDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();

                        return $data;
                    }),
            ])
            ->recordActions([
                // Editing or deleting somebody else's comment would rewrite a
                // conversation, so both stay with their author.
                EditAction::make()
                    ->visible(fn (TaskComment $record) => $record->user_id === auth()->id()),

                DeleteAction::make()
                    ->visible(fn (TaskComment $record) => $record->user_id === auth()->id()),
            ]);
    }
}
