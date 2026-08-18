<?php

use App\Models\Tenant;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

it('reports isAdmin correctly for each role', function () {
    $tenant = Tenant::factory()->create();
    app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

    $admin = User::factory()->for($tenant)->admin()->create();
    $employee = User::factory()->for($tenant)->create();

    expect($admin->isAdmin())->toBeTrue()
        ->and($employee->isAdmin())->toBeFalse();
});

it('scopes roles per tenant so the same role name does not leak across tenants', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $adminA = User::factory()->for($tenantA)->admin()->create();
    $employeeB = User::factory()->for($tenantB)->create();

    app(PermissionRegistrar::class)->setPermissionsTeamId($tenantA->id);
    expect($adminA->isAdmin())->toBeTrue();

    app(PermissionRegistrar::class)->setPermissionsTeamId($tenantB->id);
    expect($employeeB->isAdmin())->toBeFalse();
});
