# CVLeave — Future Directions

Internal dev tool for now. Notes below capture a conversation about turning this
into a sellable SaaS product later, kept here so the context isn't lost.

## Naming ideas considered

- Ferio (brandable, from "feriae" = holidays)
- Αδειολόγιο (Greek, descriptive)
- LeaveIQ, OffDuty, TimeOff HQ

## Could this be sold as a SaaS / subscription?

Yes, realistically — as a focused leave-management tool for Greek SMEs.

**The differentiator:** Greek labour law leave accrual (Α.Ν. 539/1945 — staged
20/21/22/25/26 days based on employment year + total career seniority across
employers) is something generic international tools (BambooHR, factorial, Deel,
etc.) don't implement correctly out of the box. This app already does
(`App\Services\LeaveBalanceService::greekLawEntitledDays()`).

**The trade-off:** crowded market of established players — competing on
price/simplicity for the Greek market specifically, not on feature breadth.

## What's missing before it could actually be sold

- **Multi-tenancy** — Phase 1 done: `tenants` table, `spatie/laravel-permission`
  (teams feature, `tenant_id` as the team key) replacing the old `role` column,
  Filament panel wired with `->tenant(Tenant::class)`, `LeaveType`/`User`
  scoped per tenant with default leave types + admin/employee roles seeded
  automatically on tenant creation. Phase 2 done: a separate `platform` Filament
  panel (`/platform`, no tenant scoping) for the SaaS owner, gated by a plain
  `is_platform_admin` flag on `users` (independent of the tenant-scoped Spatie
  roles), with `Tenants` and cross-tenant `Users` resources.

  Tenant-scoping on exports/reports: done — `LeaveRequestExporter` (Excel) was
  already safe (rides on the resource's scoped table query); the
  `reports/employee/{user}` and `reports/all-employees` PDF routes were the
  real cross-tenant leak (any admin could view/download another company's
  data) and are now scoped to the viewer's own tenant.

  Auto-create the first tenant admin on tenant creation: done — the Platform
  `TenantForm` now has a "Πρώτος Admin" section (name/email/password);
  `CreateTenant` creates that `User` and assigns the `admin` role for the new
  tenant in `afterCreate()`, so a company is never left without anyone who
  can log in.

  Impersonation: done — `stechstudio/filament-impersonate` wired into the
  Platform `Users` table ("Είσοδος ως" row action, gated by
  `User::canImpersonate()`/`canBeImpersonated()` so only platform admins can
  impersonate, and never another platform admin), redirecting into the
  target's own tenant panel. Every start/end is logged to a new
  `impersonation_logs` table (via the package's `EnterImpersonation`/
  `LeaveImpersonation` events) and browsable read-only in the Platform panel
  under "Ιστορικό Impersonation".

  Still missing (in rough priority order):
  - Self-service tenant registration/invite flow (email/token-based, instead
    of an admin manually creating each employee).
  - Granular permissions beyond the current binary admin/employee.
  - A global/cross-tenant LeaveRequests view in the platform panel.
- **Billing** — Stripe/subscription integration, plan limits, trial handling.
- **Legal validation** — the Greek-law formula should be reviewed by an
  accountant/lawyer before being relied on for real payroll decisions, and
  edge cases (six-day work week, part-time, parental leave, etc.) aren't
  covered yet.
- **Leave spanning a year boundary** — `LeaveBalanceService::usedDays()` buckets
  a request by `start_date`'s year, so leave running e.g. 28 Dec – 5 Jan is
  charged entirely to the starting year rather than split across both. Fine for
  current use, but worth deciding deliberately before this is sold.
- **Onboarding flow** — self-serve company signup, not admin-provisioned users.
- **Production hosting** — this currently runs on a home server over
  Tailscale; a public product needs real infrastructure, backups, uptime.

## Near-term feature backlog (not SaaS-related, just "not built yet")

- **Real SMTP provider** — Mailpit is dev-only; production needs a real
  transactional mail provider configured in `.env`.
- **CI/CD** — tests (`php artisan test`) are run manually; no automated
  pipeline runs them on push/PR yet.
- **Knowledge Base maintenance** — the in-panel Knowledge Base pages
  (Employee/Admin guides) must be kept in sync by hand whenever a feature
  changes — there's no automated check for staleness.

## Status

Core app: done and in active internal use. SaaS productization: parked,
revisit if/when there's appetite. Backlog above: pick up opportunistically.
