<?php

use App\Models\Tenant;
use App\Support\Permissions;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Gives companies that already exist the board columns and task permissions a
 * company created from now on gets automatically.
 *
 * Re-syncs both roles rather than only adding the new permissions, so the
 * database matches the catalogue exactly instead of drifting from it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Permissions::ensureExist();

        $registrar = app(PermissionRegistrar::class);

        Tenant::query()->each(function (Tenant $tenant) use ($registrar) {
            $tenant->seedDefaultTaskStatuses();

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
        // The task statuses go with the tables dropped by the migration before
        // this one; the permissions go with the catalogue migration below it.
    }
};
