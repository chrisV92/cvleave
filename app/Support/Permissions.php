<?php

namespace App\Support;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * The permission catalogue.
 *
 * Permissions are global (the `permissions` table has no `tenant_id`), while
 * roles are per-tenant — that is how Spatie's teams feature is wired here, and
 * it is the right split: the vocabulary of what *can* be done is defined by the
 * application, and each company decides which of its roles get which words.
 *
 * Adding a capability means adding a constant here and listing it in the role
 * bundles below. Nothing else needs to know.
 */
class Permissions
{
    /** See other people's leave requests — the list, the shared calendar, their PDF report. */
    public const LEAVE_VIEW_ALL = 'leave.view_all';

    /** Approve or reject a request, and set its status directly. */
    public const LEAVE_APPROVE = 'leave.approve';

    /** File or edit leave on somebody else's behalf. */
    public const LEAVE_MANAGE = 'leave.manage';

    /** Download the all-employees PDF report. */
    public const LEAVE_EXPORT_ALL = 'leave.export_all';

    /** Add, edit and invite users. */
    public const USERS_MANAGE = 'users.manage';

    /** Define the company's leave types and their accrual rules. */
    public const LEAVE_TYPES_MANAGE = 'leave_types.manage';

    /** Edit company settings, including the carry-over deadline. */
    public const COMPANY_SETTINGS = 'company.settings';

    /** See the company's projects and the work on their boards. */
    public const TASKS_VIEW = 'tasks.view';

    /** Create and edit tasks, and move them between columns. */
    public const TASKS_MANAGE = 'tasks.manage';

    /** Create and archive projects, and define their columns. */
    public const PROJECTS_MANAGE = 'projects.manage';

    /**
     * Every permission the application knows about.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::LEAVE_VIEW_ALL,
            self::LEAVE_APPROVE,
            self::LEAVE_MANAGE,
            self::LEAVE_EXPORT_ALL,
            self::USERS_MANAGE,
            self::LEAVE_TYPES_MANAGE,
            self::COMPANY_SETTINGS,
            self::TASKS_VIEW,
            self::TASKS_MANAGE,
            self::PROJECTS_MANAGE,
        ];
    }

    /**
     * Permissions granted to each role a new company starts with.
     *
     * `employee` carries no *leave* permissions. Everything an employee does
     * there — filing their own leave, editing it while it is pending,
     * downloading their own report — is the ungated default rather than a
     * permission, and gating it now would change behaviour rather than
     * refactor it.
     *
     * Tasks are different: the feature is new, so its defaults are a design
     * decision rather than a migration. Everyone in the company can see the
     * boards and work on them; only admins create projects and decide what
     * columns exist.
     *
     * @return array<string, list<string>>
     */
    public static function roleBundles(): array
    {
        return [
            'admin' => self::all(),
            'employee' => [
                self::TASKS_VIEW,
                self::TASKS_MANAGE,
            ],
        ];
    }

    /**
     * Create any catalogue entry that isn't in the database yet.
     *
     * Safe to call repeatedly. `firstOrCreate` rather than a seeder so that
     * adding a permission later reaches existing installations through the
     * ordinary tenant-creation path as well as a migration.
     */
    public static function ensureExist(string $guard = 'web'): void
    {
        $registrar = app(PermissionRegistrar::class);
        $previousTeamId = $registrar->getPermissionsTeamId();

        // The permissions table has no tenant_id; leaving a stale team id set
        // makes Spatie try to write one.
        $registrar->setPermissionsTeamId(null);

        foreach (self::all() as $name) {
            Permission::findOrCreate($name, $guard);
        }

        $registrar->setPermissionsTeamId($previousTeamId);
    }

    /**
     * Permissions that mark somebody as managing the company rather than just
     * working in it. Used to decide who is shown the admin-facing guide.
     *
     * @return list<string>
     */
    public static function management(): array
    {
        return [
            self::LEAVE_VIEW_ALL,
            self::LEAVE_APPROVE,
            self::LEAVE_MANAGE,
            self::LEAVE_EXPORT_ALL,
            self::USERS_MANAGE,
            self::LEAVE_TYPES_MANAGE,
            self::COMPANY_SETTINGS,
            self::PROJECTS_MANAGE,
        ];
    }
}
