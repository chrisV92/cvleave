<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Concerns\InvitesUsers;
use App\Filament\Resources\Users\UserResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    use InvitesUsers;

    protected static string $resource = UserResource::class;

    protected ?string $roleToAssign = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->roleToAssign = $data['role'] ?? 'employee';
        unset($data['role']);

        // Filament can assign the tenant implicitly, but that depends on panel
        // boot context. Being explicit keeps the behaviour the same everywhere
        // and means it is actually exercised by tests.
        $data['tenant_id'] ??= Filament::getTenant()?->id;

        return $this->prepareInvitation($data);
    }

    protected function afterCreate(): void
    {
        $this->record->assignRole($this->roleToAssign);
        $this->sendInvitationIfRequested($this->record);
    }
}
