<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Filament\Resources\Tasks\TaskResource;
use App\Services\CustomFieldSchema;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTask extends EditRecord
{
    protected static string $resource = TaskResource::class;

    /** @var array<int|string, mixed> */
    protected array $customFieldState = [];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => TaskResource::canDelete($this->getRecord())),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data[CustomFieldSchema::STATE_KEY] = $this->getRecord()->customFieldState();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->customFieldState = $data[CustomFieldSchema::STATE_KEY] ?? [];
        unset($data[CustomFieldSchema::STATE_KEY]);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->saveCustomFieldState($this->customFieldState);
    }
}
