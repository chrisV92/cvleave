<?php

namespace App\Filament\Platform\Resources\Tenants\Pages;

use App\Filament\Platform\Resources\Tenants\TenantResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected array $adminData = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->adminData = [
            'name' => $data['admin_name'],
            'email' => $data['admin_email'],
            'password' => $data['admin_password'],
        ];

        unset($data['admin_name'], $data['admin_email'], $data['admin_password']);

        return $data;
    }

    protected function afterCreate(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->record->id);

        $admin = User::create([
            'tenant_id' => $this->record->id,
            'name' => $this->adminData['name'],
            'email' => $this->adminData['email'],
            'password' => Hash::make($this->adminData['password']),
        ]);

        $admin->assignRole('admin');
    }
}
