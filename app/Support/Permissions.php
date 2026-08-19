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
        ];
    }

    /**
     * Permissions granted to each role a new company starts with.
     *
     * `employee` is deliberately empty. Everything an employee does today —
     * filing their own leave, editing it while it is pending, downloading their
     * own report — is the ungated default, not a permission. Introducing
     * `leave.request` here would mean gating behaviour that is currently open
     * to everyone, which is a change in behaviour rather than a refactor.
     *
     * @return array<string, list<string>>
     */
    public static function roleBundles(): array
    {
        return [
            'admin' => self::all(),
            'employee' => [],
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
        ];
    }
}
