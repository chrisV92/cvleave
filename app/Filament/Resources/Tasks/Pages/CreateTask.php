<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Filament\Resources\Tasks\TaskResource;
use App\Models\Project;
use App\Services\CustomFieldSchema;
use Filament\Resources\Pages\CreateRecord;

class CreateTask extends CreateRecord
{
    protected static string $resource = TaskResource::class;

    /** @var array<int|string, mixed> */
    protected array $customFieldState = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Take the tenant from the project rather than the session: it is the
        // project that decides which company a task belongs to, and the two
        // must never disagree.
        $data['tenant_id'] = Project::find($data['project_id'])?->tenant_id;
        $data['created_by'] ??= auth()->id();

        // Custom field answers are not columns on tasks, and they need the
        // record to exist before they can point at it.
        $this->customFieldState = $data[CustomFieldSchema::STATE_KEY] ?? [];
        unset($data[CustomFieldSchema::STATE_KEY]);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->saveCustomFieldState($this->customFieldState);
    }
}
