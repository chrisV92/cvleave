<?php

namespace App\Filament\Resources\Tasks\Schemas;

use App\Models\CustomField;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Services\CustomFieldSchema;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class TaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label(__('Τίτλος'))
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Select::make('project_id')
                    ->label(__('Έργο'))
                    ->options(fn () => Project::query()
                        ->where('tenant_id', Filament::getTenant()?->id)
                        ->active()
                        ->orderBy('name')
                        ->pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->live()
                    // Columns belong to a project, so the chosen status stops
                    // meaning anything the moment the project changes.
                    ->afterStateUpdated(fn (callable $set) => $set('task_status_id', null)),

                Select::make('task_status_id')
                    ->label(__('Στήλη'))
                    ->options(fn (Get $get) => $get('project_id')
                        ? TaskStatus::query()
                            ->where('project_id', $get('project_id'))
                            ->ordered()
                            ->pluck('name', 'id')
                        : [])
                    ->required()
                    ->default(fn (Get $get) => $get('project_id')
                        ? Project::find($get('project_id'))?->defaultStatus()?->id
                        : null)
                    ->disabled(fn (Get $get) => ! $get('project_id'))
                    ->helperText(fn (Get $get) => $get('project_id')
                        ? null
                        : __('Διάλεξε πρώτα έργο.')),

                Select::make('assignee_id')
                    ->label(__('Ανάθεση σε'))
                    ->relationship(
                        'assignee',
                        'name',
                        fn ($query) => $query->where('tenant_id', Filament::getTenant()?->id),
                    )
                    ->searchable()
                    ->preload(),

                Select::make('priority')
                    ->label(__('Προτεραιότητα'))
                    ->options(Task::priorities())
                    ->default(Task::PRIORITY_NORMAL),

                DatePicker::make('start_date')
                    ->label(__('Έναρξη'))
                    ->native(false),

                DatePicker::make('due_date')
                    ->label(__('Προθεσμία'))
                    ->native(false)
                    ->afterOrEqual('start_date'),

                Textarea::make('description')
                    ->label(__('Περιγραφή'))
                    ->rows(5)
                    ->columnSpanFull(),

                // Which fields apply depends on the project, and the project
                // select is live, so this section rebuilds when it changes.
                Section::make(__('Πρόσθετα Πεδία'))
                    ->schema(fn (Get $get) => CustomFieldSchema::formComponents($get('project_id')))
                    ->visible(fn (Get $get) => CustomField::forProject($get('project_id'))->isNotEmpty())
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
