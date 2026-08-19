<?php

namespace App\Models;

use App\Support\Permissions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'carryover_deadline_month',
        'carryover_deadline_day',
        'carryover_from_year',
    ];

    /**
     * The date, in the given year, up to which employees may still draw on the
     * previous year's leftover leave. Null when this company does not allow
     * carry-over at all.
     */
    public function carryoverDeadlineFor(int $year): ?Carbon
    {
        if (! $this->carryover_deadline_month || ! $this->carryover_deadline_day) {
            return null;
        }

        $endOfMonth = Carbon::create($year, $this->carryover_deadline_month, 1)->endOfMonth();

        // Guards against a deadline of e.g. the 31st in a 30-day month.
        return $endOfMonth->copy()
            ->setDay(min($this->carryover_deadline_day, $endOfMonth->day))
            ->endOfDay();
    }

    public function allowsCarryover(): bool
    {
        return $this->carryover_deadline_month !== null && $this->carryover_deadline_day !== null;
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function leaveTypes(): HasMany
    {
        return $this->hasMany(LeaveType::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function taskStatuses(): HasMany
    {
        return $this->hasMany(TaskStatus::class);
    }

    protected static function booted(): void
    {
        static::created(function (Tenant $tenant) {
            $tenant->seedDefaultLeaveTypes();
            $tenant->seedDefaultTaskStatuses();
            $tenant->seedDefaultRoles();
        });
    }

    /**
     * The company's board columns, which every new project starts from.
     *
     * These are templates (no project_id); each project copies them, so a
     * company can keep a house standard while individual boards diverge.
     */
    public function seedDefaultTaskStatuses(): void
    {
        $defaults = [
            ['name' => 'Νέο', 'color' => '#94a3b8', 'is_default' => true],
            ['name' => 'Σε εξέλιξη', 'color' => '#3b82f6'],
            ['name' => 'Σε έλεγχο', 'color' => '#f59e0b'],
            ['name' => 'Ολοκληρώθηκε', 'color' => '#22c55e', 'is_completed' => true],
        ];

        foreach ($defaults as $position => $status) {
            $this->taskStatuses()->updateOrCreate(
                ['project_id' => null, 'name' => $status['name']],
                [
                    'color' => $status['color'],
                    'position' => $position,
                    'is_default' => $status['is_default'] ?? false,
                    'is_completed' => $status['is_completed'] ?? false,
                ]
            );
        }
    }

    public function seedDefaultRoles(): void
    {
        // Permissions are global, not per-tenant, so they are created once and
        // shared. Only the role-to-permission assignment below is per company.
        Permissions::ensureExist();

        $registrar = app(PermissionRegistrar::class);
        $previousTeamId = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId($this->id);

        foreach (Permissions::roleBundles() as $name => $permissions) {
            $role = Role::firstOrCreate(['name' => $name, 'tenant_id' => $this->id]);
            $role->syncPermissions($permissions);
        }

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
                'allows_carryover' => true,
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
