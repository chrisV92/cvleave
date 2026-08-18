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

  Employee invitations: done — leaving the password blank when creating a user
  emails them a link to set their own (`users.invitation_token`, a sha256 of
  what was sent, valid 7 days, single use). Pending/expired invitations are
  flagged in the users table with a resend action that invalidates the old
  link. Setting a password manually still works, which matters while a real
  SMTP provider is outstanding. Fixed alongside: every notification email
  linked to `/admin/leave-requests`, which stopped resolving when tenancy made
  panel routes `/admin/{tenant}/...` — links now go through `App\Support\PanelUrl`
  and a test asserts each CTA matches a real route.

  Still missing (in rough priority order):
  - Granular permissions beyond the current binary admin/employee.
  - A global/cross-tenant LeaveRequests view in the platform panel.
  - Self-service *company* signup (a stranger creating a tenant) — distinct
    from employee invitations above; see the Onboarding flow item.
- **Billing** — Stripe/subscription integration, plan limits, trial handling.
- **Legal validation** — the Greek-law formula should be reviewed by an
  accountant/lawyer before being relied on for real payroll decisions, and
  edge cases (six-day work week, part-time, parental leave, etc.) aren't
  covered yet.
- **Year-boundary leave carry-over** — implemented. Each company sets its own
  cutoff in Company Settings (`tenants.carryover_deadline_month/day`; unset
  means no carry-over), and each leave type opts in via
  `leave_types.allows_carryover` — annual leave typically, not sick or unpaid.
  `leave_requests.days_from_carryover` records how much of a request was drawn
  from the previous year, so one request can legitimately straddle both years
  (3 days from last year + 2 from this one) while staying a single row.
  Carried-over days are spent first, since they expire, and the split is
  recomputed authoritatively at approval rather than trusted from submission
  time. Employees see them as a separate dashboard card with the expiry date.
  Nothing is materialised at year end — it is all computed, so no cron job.

  Adopting carry-over on an existing company: handled by
  `tenants.carryover_from_year` — the first year the app has complete records
  for. Nothing carries over from earlier, so switching the feature on no longer
  credits everyone with a year that was never tracked; the settings form
  defaults it to the current year. Recent hires were already handled by
  `hire_date` (the Greek-law formula prorates the joining year), and a related
  bug was fixed at the same time: fixed-days and tiered leave types used to
  ignore `hire_date` entirely and grant a full previous-year entitlement to
  someone hired after that year ended.

  Still open:
  - A single request that *spans* the year boundary (28 Dec – 5 Jan) is charged
    entirely to the year it starts in.
  - Fixed-days and tiered types do not prorate the *joining* year the way the
    Greek-law formula does — a November hire gets the full fixed allowance.
    That is arguably deliberate policy (many companies do grant the full
    amount), so it would want to be a per-leave-type toggle rather than
    imposed silently.
  - A per-tenant cap on carried-over days, which several jurisdictions and
    company policies impose. Unrelated to adoption, just not built.

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
