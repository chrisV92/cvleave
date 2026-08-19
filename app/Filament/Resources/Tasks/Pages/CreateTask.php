<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Filament\Resources\Tasks\TaskResource;
use App\Models\Project;
use Filament\Resources\Pages\CreateRecord;

class CreateTask extends CreateRecord
{
    protected static string $resource = TaskResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Take the tenant from the project rather than the session: it is the
        // project that decides which company a task belongs to, and the two
        // must never disagree.
        $data['tenant_id'] = Project::find($data['project_id'])?->tenant_id;
        $data['created_by'] ??= auth()->id();

        return $data;
    }
}
