<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Models\Project;
use App\Models\Task;
use App\Services\CustomFieldSchema;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;

/**
 * The fields shown when a card is opened from the board.
 *
 * Deliberately not the full task form: the project is not offered, because the
 * board *is* the project and moving work between boards from inside one is a
 * different intention than editing a card. The status stays, since changing it
 * from the panel is the same act as dragging the card.
 */
class BoardTaskForm
{
    /** @return array<mixed> */
    public static function components(Project $project, ?Task $task): array
    {
        return [
            TextInput::make('title')
                ->label(__('Τίτλος'))
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            Select::make('task_status_id')
                ->label(__('Στήλη'))
                ->options($project->statuses()->ordered()->pluck('name', 'id'))
                ->required()
                ->native(false),

            Select::make('assignee_id')
                ->label(__('Ανάθεση σε'))
                ->options(fn () => $project->tenant?->users()->orderBy('name')->pluck('name', 'id') ?? [])
                ->searchable()
                ->preload(),

            Select::make('priority')
                ->label(__('Προτεραιότητα'))
                ->options(Task::priorities())
                ->native(false),

            DatePicker::make('start_date')
                ->label(__('Έναρξη'))
                ->native(false),

            DatePicker::make('due_date')
                ->label(__('Προθεσμία'))
                ->native(false)
                ->afterOrEqual('start_date'),

            Textarea::make('description')
                ->label(__('Περιγραφή'))
                ->rows(4)
                ->columnSpanFull(),

            Section::make(__('Πρόσθετα Πεδία'))
                ->schema(CustomFieldSchema::formComponents($project))
                ->visible(fn () => CustomFieldSchema::formComponents($project) !== [])
                ->columns(1)
                ->columnSpanFull(),
        ];
    }
}
