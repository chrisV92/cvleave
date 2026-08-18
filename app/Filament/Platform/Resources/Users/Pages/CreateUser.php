<?php

namespace App\Filament\Platform\Resources\Users\Pages;

use App\Filament\Concerns\InvitesUsers;
use App\Filament\Platform\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Spatie\Permission\PermissionRegistrar;

class CreateUser extends CreateRecord
{
    use InvitesUsers;

    protected static string $resource = UserResource::class;

    protected ?string $roleToAssign = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->roleToAssign = $data['role'] ?? 'employee';
        unset($data['role']);

        return $this->prepareInvitation($data);
    }

    protected function afterCreate(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->record->tenant_id);
        $this->record->assignRole($this->roleToAssign);
        $this->sendInvitationIfRequested($this->record);
    }
}
