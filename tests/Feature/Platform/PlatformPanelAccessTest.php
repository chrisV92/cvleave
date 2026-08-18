<?php

use App\Filament\Platform\Resources\Tenants\TenantResource;
use App\Filament\Platform\Resources\Users\UserResource;
use App\Models\Tenant;
use App\Models\User;

it('allows a platform admin to access the platform panel', function () {
    $tenant = Tenant::factory()->create();
    $superadmin = User::factory()->for($tenant)->create(['is_platform_admin' => true]);

    $response = $this->actingAs($superadmin)->get('/platform');

    $response->assertSuccessful();
});

it('forbids a tenant admin without the platform flag from accessing the platform panel', function () {
    $tenant = Tenant::factory()->create();
    $tenantAdmin = User::factory()->for($tenant)->admin()->create();

    $response = $this->actingAs($tenantAdmin)->get('/platform');

    $response->assertForbidden();
});

it('redirects a guest hitting the platform panel to the login page', function () {
    $response = $this->get('/platform');

    $response->assertRedirect('/platform/login');
});

it('lets a platform admin see tenants and users from every tenant', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $superadmin = User::factory()->for($tenantA)->create(['is_platform_admin' => true]);
    User::factory()->for($tenantB)->create();

    $this->actingAs($superadmin);

    expect(TenantResource::getEloquentQuery()->count())->toBeGreaterThanOrEqual(2)
        ->and(UserResource::getEloquentQuery()->pluck('tenant_id')->unique()->count())->toBeGreaterThanOrEqual(2);
});
