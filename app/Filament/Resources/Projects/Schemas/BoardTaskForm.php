<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Models\Project;
use App\Models\Task;
use App\Services\CustomFieldSchema;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

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
    /** Newly uploaded files live under this key until the task is saved. */
    public const ATTACHMENTS_KEY = 'new_attachments';

    /** Separates the random prefix from the name the person uploaded. */
    private const NAME_SEPARATOR = '__';

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

            ...static::attachmentComponents($project, $task),

            Section::make(__('Πρόσθετα Πεδία'))
                ->schema(CustomFieldSchema::formComponents($project))
                ->visible(fn () => CustomFieldSchema::formComponents($project) !== [])
                ->columns(1)
                ->columnSpanFull(),
        ];
    }

    /**
     * Existing files, plus a way to add more without leaving the board.
     *
     * Removing one stays on the full task page: it is destructive and belongs
     * next to the proper list, not tucked inside an edit panel.
     *
     * @return array<mixed>
     */
    protected static function attachmentComponents(Project $project, ?Task $task): array
    {
        if (! $project->attachments_enabled) {
            return [];
        }

        return [
            Section::make(__('Συνημμένα'))
                ->schema([
                    ViewField::make('existing_attachments')
                        ->view('filament.resources.projects.pages.partials.attachments')
                        ->viewData(['task' => $task])
                        ->dehydrated(false)
                        ->columnSpanFull(),

                    FileUpload::make(self::ATTACHMENTS_KEY)
                        ->label(__('Προσθήκη αρχείων'))
                        ->multiple()
                        ->disk('local')
                        ->directory('task-attachments/'.$project->tenant_id)
                        ->visibility('private')
                        ->maxSize(10240)
                        ->imagePreviewHeight('90')
                        ->openable(false)
                        ->downloadable(false)
                        // Upload and save are separate requests on separate
                        // component instances, so the original name travels
                        // inside the stored filename and the size and type are
                        // read back off the disk when the task saves.
                        ->getUploadedFileNameForStorageUsing(
                            fn (TemporaryUploadedFile $file) => Str::random(20)
                                .self::NAME_SEPARATOR
                                .self::safeName($file->getClientOriginalName()),
                        )
                        ->columnSpanFull(),
                ])
                ->columns(1)
                ->columnSpanFull(),
        ];
    }

    /**
     * Turn uploaded paths into attachment rows on the task.
     *
     * @param  array<string>  $paths
     */
    public static function storeAttachments(Task $task, array $paths): void
    {
        $disk = Storage::disk('local');

        foreach ($paths as $path) {
            if (! $path || ! $disk->exists($path)) {
                continue;
            }

            $task->attachments()->create([
                'uploaded_by' => auth()->id(),
                'disk' => 'local',
                'path' => $path,
                'original_name' => Str::after(basename($path), self::NAME_SEPARATOR) ?: basename($path),
                'mime_type' => $disk->mimeType($path) ?: null,
                'size_bytes' => $disk->size($path) ?: 0,
            ]);
        }
    }

    /** The uploaded name becomes part of a path, so it cannot climb out of one. */
    private static function safeName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));

        return Str::of($name)
            ->replaceMatches('/[^\p{L}\p{N}._-]+/u', '-')
            ->limit(80, '')
            ->toString() ?: 'file';
    }
}
