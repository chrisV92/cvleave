<?php

use App\Models\LeaveType;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $tenant = Tenant::withoutEvents(fn () => Tenant::create([
            'name' => 'Default',
            'slug' => 'default',
        ]));

        User::query()->whereNull('tenant_id')->update(['tenant_id' => $tenant->id]);
        LeaveType::query()->whereNull('tenant_id')->update(['tenant_id' => $tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

        $adminRole = Role::create(['name' => 'admin', 'tenant_id' => $tenant->id]);
        $employeeRole = Role::create(['name' => 'employee', 'tenant_id' => $tenant->id]);

        User::query()->where('tenant_id', $tenant->id)->get()->each(function (User $user) use ($adminRole, $employeeRole) {
            $user->assignRole($user->role === 'admin' ? $adminRole : $employeeRole);
        });
    }

    public function down(): void
    {
        $tenant = Tenant::where('slug', 'default')->first();

        if (! $tenant) {
            return;
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        Role::where('tenant_id', $tenant->id)->delete();
        $tenant->delete();
    }
};
