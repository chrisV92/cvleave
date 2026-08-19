<?php

use App\Models\Tenant;
use App\Support\Permissions;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Fills in the permission catalogue and hands every existing company's `admin`
 * role the full set.
 *
 * Until now the `permissions` table was empty — authorisation was decided
 * purely by `hasRole('admin')`. Roles keep working exactly as before; this only
 * gives them the permissions the code is about to start asking for.
 */
return new class extends Migration
{
    public function up(): void
    {
        Permissions::ensureExist();

        $registrar = app(PermissionRegistrar::class);

        Tenant::query()->each(function (Tenant $tenant) use ($registrar) {
            $registrar->setPermissionsTeamId($tenant->id);

            foreach (Permissions::roleBundles() as $name => $permissions) {
                Role::firstOrCreate(['name' => $name, 'tenant_id' => $tenant->id])
                    ->syncPermissions($permissions);
            }
        });

        $registrar->setPermissionsTeamId(null);
        $registrar->forgetCachedPermissions();
    }

    public function down(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId(null);

        // role_has_permissions cascades on the permission being deleted.
        Permission::query()->whereIn('name', Permissions::all())->delete();

        $registrar->forgetCachedPermissions();
    }
};
