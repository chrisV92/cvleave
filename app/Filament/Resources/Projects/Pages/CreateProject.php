<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateProject extends CreateRecord
{
    protected static string $resource = ProjectResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set explicitly rather than relying on Filament's implicit tenant
        // association — the same omission made user creation fail before.
        $data['tenant_id'] ??= Filament::getTenant()?->id;
        $data['owner_id'] ??= auth()->id();

        return $data;
    }
}
