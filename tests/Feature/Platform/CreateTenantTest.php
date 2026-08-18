<?php

use App\Filament\Platform\Resources\Tenants\Pages\CreateTenant;
use App\Models\Tenant;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

it('creates a first admin user for the tenant, scoped with the admin role', function () {
    $tenant = Tenant::factory()->create();
    $superadmin = User::factory()->for($tenant)->create(['is_platform_admin' => true]);

    test()->actingAs($superadmin);
    Filament::setCurrentPanel(Filament::getPanel('platform'));

    Livewire::test(CreateTenant::class)
        ->set('data.name', 'Acme Inc')
        ->set('data.slug', 'acme-inc')
        ->set('data.admin_name', 'Acme Admin')
        ->set('data.admin_email', 'admin@acme-inc.test')
        ->set('data.admin_password', 'super-secret-password')
        ->call('create');

    $newTenant = Tenant::where('slug', 'acme-inc')->first();
    expect($newTenant)->not->toBeNull();

    $admin = User::where('email', 'admin@acme-inc.test')->first();
    expect($admin)->not->toBeNull()
        ->and($admin->tenant_id)->toBe($newTenant->id);

    app(PermissionRegistrar::class)->setPermissionsTeamId($newTenant->id);
    expect($admin->hasRole('admin'))->toBeTrue();
});
