<?php

use App\Filament\Resources\LeaveTypes\LeaveTypeResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Permissions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Phase 0 of the Task Manager work: authorisation moved from a single
 * `isAdmin()` role check to named permissions. These tests exist because the
 * refactor is invisible — the whole suite stayed green either way, so
 * something has to assert the permissions are actually granted and actually
 * consulted.
 */
function makeUser(Tenant $tenant, string $role): User
{
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
    $user->assignRole($role);

    return $user->fresh();
}

it('creates every catalogue permission exactly once, globally', function () {
    Tenant::factory()->create();
    Tenant::factory()->create();

    foreach (Permissions::all() as $name) {
        expect(Permission::where('name', $name)->count())
            ->toBe(1, "expected exactly one '{$name}' permission row");
    }
});

it('gives a new company an admin holding every permission and an employee holding none', function () {
    $tenant = Tenant::factory()->create();

    $admin = makeUser($tenant, 'admin');
    $employee = makeUser($tenant, 'employee');

    foreach (Permissions::all() as $name) {
        expect($admin->can($name))->toBeTrue("admin should have {$name}");
        expect($employee->can($name))->toBeFalse("employee should not have {$name}");
    }
});

it('scopes permissions to the company the user belongs to', function () {
    $acme = Tenant::factory()->create();
    $other = Tenant::factory()->create();

    $admin = makeUser($acme, 'admin');

    // Same person, evaluated in another company's context: their admin role
    // belongs to Acme, so it must not carry over.
    app(PermissionRegistrar::class)->setPermissionsTeamId($other->id);

    expect($admin->fresh()->can(Permissions::USERS_MANAGE))->toBeFalse();
});

it('treats an admin as managing the company and an employee as not', function () {
    $tenant = Tenant::factory()->create();

    expect(makeUser($tenant, 'admin')->managesCompany())->toBeTrue();
    expect(makeUser($tenant, 'employee')->managesCompany())->toBeFalse();
});

it('lets a role hold some permissions without holding all of them', function () {
    $tenant = Tenant::factory()->create();
    app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

    // The point of the whole refactor: an HR person who adds employees but is
    // not allowed to approve anyone's leave. Impossible before.
    $hr = Role::create(['name' => 'hr', 'tenant_id' => $tenant->id]);
    $hr->syncPermissions([Permissions::USERS_MANAGE, Permissions::LEAVE_VIEW_ALL]);

    $user = makeUser($tenant, 'hr');

    expect($user->can(Permissions::USERS_MANAGE))->toBeTrue()
        ->and($user->can(Permissions::LEAVE_VIEW_ALL))->toBeTrue()
        ->and($user->can(Permissions::LEAVE_APPROVE))->toBeFalse()
        ->and($user->can(Permissions::COMPANY_SETTINGS))->toBeFalse()
        ->and($user->managesCompany())->toBeTrue()
        ->and($user->isAdmin())->toBeFalse();
});

it('grants a bespoke role exactly the access its permissions describe', function () {
    $tenant = Tenant::factory()->create();
    app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

    Role::create(['name' => 'hr', 'tenant_id' => $tenant->id])
        ->syncPermissions([Permissions::USERS_MANAGE]);

    $hr = makeUser($tenant, 'hr');
    test()->actingAs($hr);

    // The gates now answer to the permission, not to the admin role — which is
    // the entire point, and is not observable from the existing admin/employee
    // tests since those two sit at the extremes.
    expect(UserResource::canViewAny())->toBeTrue()
        ->and(LeaveTypeResource::canViewAny())->toBeFalse();
});

it('denies everything when no company context has been established', function () {
    $tenant = Tenant::factory()->create();
    $admin = makeUser($tenant, 'admin');

    // A request that somehow skipped SetPermissionsTeamId must fail closed.
    app(PermissionRegistrar::class)->setPermissionsTeamId(null);

    expect($admin->fresh()->can(Permissions::USERS_MANAGE))->toBeFalse()
        ->and($admin->fresh()->can(Permissions::LEAVE_APPROVE))->toBeFalse();
});
