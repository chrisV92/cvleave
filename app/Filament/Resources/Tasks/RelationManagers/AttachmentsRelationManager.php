<?php

namespace App\Filament\Resources\Tasks\RelationManagers;

use App\Models\TaskAttachment;
use App\Support\Permissions;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class AttachmentsRelationManager extends RelationManager
{
    /** Separates the random prefix from the name the person actually uploaded. */
    private const NAME_SEPARATOR = '__';

    protected static string $relationship = 'attachments';

    /**
     * The uploaded name ends up in a filesystem path, so it is stripped of
     * anything that could climb out of the directory it belongs in.
     */
    private static function safeName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));

        return Str::of($name)
            ->replaceMatches('/[^\p{L}\p{N}._-]+/u', '-')
            ->limit(80, '')
            ->toString() ?: 'file';
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Συνημμένα');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return ($ownerRecord->project?->attachments_enabled ?? false)
            && (auth()->user()?->can(Permissions::TASKS_VIEW) ?? false);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('path')
                    ->label(__('Αρχείο'))
                    ->required()
                    // The private disk, never the public one: these files are
                    // company data and must go through the access check in
                    // TaskAttachmentController.
                    ->disk('local')
                    ->directory('task-attachments/'.$this->getOwnerRecord()->tenant_id)
                    ->visibility('private')
                    ->maxSize(10240)
                    ->imagePreviewHeight('120')
                    ->openable(false)
                    ->downloadable(false)
                    // The upload and the submit are two separate requests on
                    // two separate component instances, so nothing captured
                    // during the upload survives to be saved. The original
                    // name is carried in the stored filename instead, and the
                    // size and type are read back off the disk at save time.
                    ->getUploadedFileNameForStorageUsing(
                        fn (TemporaryUploadedFile $file) => Str::random(20)
                            .self::NAME_SEPARATOR
                            .self::safeName($file->getClientOriginalName()),
                    )
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        $canManage = auth()->user()?->can(Permissions::TASKS_MANAGE) ?? false;

        return $table
            ->columns([
                ImageColumn::make('preview')
                    ->label('')
                    ->getStateUsing(fn (TaskAttachment $record) => $record->isImage()
                        ? route('task-attachments.show', $record)
                        : null)
                    ->height(44)
                    ->extraImgAttributes(['style' => 'border-radius: 6px; object-fit: cover']),

                TextColumn::make('original_name')
                    ->label(__('Όνομα'))
                    ->weight('bold')
                    ->description(fn (TaskAttachment $record) => $record->humanSize()),

                TextColumn::make('uploader.name')
                    ->label(__('Ανέβασε'))
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->label(__('Ημερομηνία'))
                    ->dateTime('d/m/Y H:i'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('Επισύναψη Αρχείου'))
                    ->visible($canManage)
                    ->mutateDataUsing(function (array $data): array {
                        $disk = Storage::disk('local');
                        $path = $data['path'];

                        $data['disk'] = 'local';
                        $data['uploaded_by'] = auth()->id();
                        $data['original_name'] = Str::after(basename($path), self::NAME_SEPARATOR)
                            ?: basename($path);
                        $data['mime_type'] = $disk->exists($path) ? $disk->mimeType($path) : null;
                        $data['size_bytes'] = $disk->exists($path) ? $disk->size($path) : 0;

                        return $data;
                    }),
            ])
            ->recordActions([
                Action::make('download')
                    ->label(__('Άνοιγμα'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (TaskAttachment $record) => route('task-attachments.show', $record))
                    ->openUrlInNewTab(),

                DeleteAction::make()->visible($canManage),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible($canManage),
                ]),
            ]);
    }
}
