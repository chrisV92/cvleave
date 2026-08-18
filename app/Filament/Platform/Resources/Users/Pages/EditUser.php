<?php

namespace App\Filament\Platform\Resources\Users\Pages;

use App\Filament\Platform\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Spatie\Permission\PermissionRegistrar;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected ?string $roleToAssign = null;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->roleToAssign = $data['role'] ?? 'employee';
        unset($data['role']);

        return $data;
    }

    protected function afterSave(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->record->tenant_id);
        $this->record->syncRoles([$this->roleToAssign]);
    }
}
