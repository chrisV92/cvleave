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
- **Year-boundary leave accounting / carry-over** — two related gaps, both
  currently unhandled:

  1. *A request spanning the year boundary.* `LeaveBalanceService::usedDays()`
     buckets a request by `start_date`'s year, so leave running 28 Dec – 5 Jan
     is charged entirely to the starting year instead of being split.
  2. *Carry-over into the next year.* Greek practice appears to allow leave
     accrued in one year to still be taken early in the next (commonly cited
     as up to 31 March). Today the app has no concept of this at all: a
     January request is charged against the **new** year's entitlement, so
     last year's unused days are silently lost, and there is no deadline
     after which they expire.

  **Agreed direction: make the deadline a per-tenant setting.** Rather than
  hardcoding "31 March" — which would bake a guessed legal rule into the
  product — each company configures its own carry-over cutoff, and can turn
  carry-over off entirely. Greek tenants set 31 March; anyone else sets what
  their own law or policy says. This also removes the legal uncertainty as a
  blocker: the app enforces whatever the customer configures rather than
  asserting what the law is.

  **Shape of the change:**
  - `tenants.carryover_deadline` (nullable, e.g. month+day) — null means no
    carry-over for that company.
  - `leave_types.allows_carryover` (boolean) — carry-over should apply to
    annual leave, not to sick or unpaid leave, so the toggle belongs per leave
    type as well as per tenant.
  - `leave_requests.days_from_carryover` (decimal, default 0) — how much of
    this request was drawn from the previous year's balance. Keeps one row per
    request (see "split across two years" below) while making the accounting
    explicit and auditable.
  - `LeaveBalanceService` gains a carry-over aware balance: entitlement for
    year N, minus days charged to N, where days charged to N include the
    `days_from_carryover` portion of requests taken in year N+1 before the
    cutoff. Nothing needs materialising at year end — it can all be computed,
    so no cron job.
  - Employee dashboard shows carried-over days as their own card, separate
    from the current year, with the expiry date visible — e.g.
    "5 / 22 από 2026 (λήγουν 31/03/2027)" next to "22 / 22 για 2027".

  **Two decisions still open:**
  1. *Consumption order.* Old balance first is the obvious default, since
     carried-over days expire — but it should be deliberate and stated in the
     UI, otherwise employees will not understand which days a request ate.
  2. *A request that straddles both buckets.* Someone with 3 carried-over days
     requesting 5 days in February draws 3 from last year and 2 from this one.
     That is why `days_from_carryover` sits on the request rather than a plain
     `charged_year` column — one request can legitimately be charged to two
     years, and splitting it into two rows would misrepresent what the
     employee actually submitted.
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
