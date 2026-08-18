<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function leaveTypes(): HasMany
    {
        return $this->hasMany(LeaveType::class);
    }

    protected static function booted(): void
    {
        static::created(function (Tenant $tenant) {
            $tenant->seedDefaultLeaveTypes();
            $tenant->seedDefaultRoles();
        });
    }

    public function seedDefaultRoles(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $previousTeamId = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId($this->id);

        Role::firstOrCreate(['name' => 'admin', 'tenant_id' => $this->id]);
        Role::firstOrCreate(['name' => 'employee', 'tenant_id' => $this->id]);

        $registrar->setPermissionsTeamId($previousTeamId);
    }

    public function seedDefaultLeaveTypes(): void
    {
        $this->leaveTypes()->updateOrCreate(
            ['name' => 'Κανονική Άδεια'],
            [
                'color' => '#22c55e',
                'requires_note' => false,
                'auto_calculate' => true,
                'use_greek_law_formula' => true,
                'is_active' => true,
            ]
        );

        $this->leaveTypes()->updateOrCreate(
            ['name' => 'Αναρρωτική Άδεια'],
            [
                'color' => '#ef4444',
                'requires_note' => true,
                'auto_calculate' => false,
                'fixed_days_per_year' => 0,
                'is_active' => true,
            ]
        );

        $this->leaveTypes()->updateOrCreate(
            ['name' => 'Άδεια Άνευ Αποδοχών'],
            [
                'color' => '#94a3b8',
                'requires_note' => true,
                'auto_calculate' => false,
                'fixed_days_per_year' => 0,
                'is_active' => true,
            ]
        );
    }
}
