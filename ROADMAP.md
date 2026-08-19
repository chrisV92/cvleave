# CvTech — Future Directions

Notes below capture the conversations about turning this into a sellable SaaS
product, kept here so the context isn't lost.

The product is branded **CvTech** as of August 2026, deliberately named for a
company rather than for leave management — the Task Manager specified below is
the second module, and there may be more.

## Naming ideas considered

Settled on **CvTech**. Earlier candidates, kept for the record: Ferio (from
"feriae" = holidays), Αδειολόγιο, LeaveIQ, OffDuty, TimeOff HQ, and a later
round aimed at a company-level name — Meltemi, Stoa, Argo, Delos, Pleiada,
Talos, Daidalos, Metron.

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
  - Granular permissions beyond the current binary admin/employee — see the
    dedicated section below.
  - A global/cross-tenant LeaveRequests view in the platform panel. The
    dashboard now carries cross-tenant reporting (totals, signups per month,
    and per-company activity with a last-used date to spot dormant customers),
    but there is still no browsable list of every request across tenants.
  - Self-service *company* signup (a stranger creating a tenant) — distinct
    from employee invitations above; see the Onboarding flow item.
- **Granular permissions / custom roles per company** — not started; the notes
  below are a design discussion, not a decision.

  *Why it is worth doing.* `isAdmin()` is checked in **19 places** across 13 files and gates
  about ten separate capabilities: managing users, managing leave types, seeing
  everyone's requests, approving/rejecting, editing someone else's request,
  filing leave on another person's behalf, the all-employees PDF, company
  settings, the shared calendar and the admin guide. Today it is all-or-nothing,
  so there is no way to have an HR person who adds employees but cannot approve
  leave, or a supervisor who approves but cannot touch leave types.

  *The foundation already exists.* Roles are already per-tenant — Spatie's teams
  feature keys them by `tenant_id`, so every company has its own `admin` and
  `employee` rows. The `permissions` table ships with the package and is
  currently **empty**: we only ever used roles. So this extends what is there
  rather than introducing new architecture.

  *⚠️ The trap.* The most useful middle role — a "Task Manager" who approves
  **their own team's** leave — is not a permissions problem at all, it is a
  **data scoping** problem. Adding an `approve_leave_requests` permission
  without a notion of teams produces a role that can approve *everyone* in the
  company. That is arguably worse than the current state, because it looks
  bounded while it is not. Team-scoped approval needs departments plus a
  manager relationship, which is a separate and larger feature.

  *Suggested sequencing:*
  1. **Permission catalogue.** Define the ten-odd permissions, replace the
     `isAdmin()` checks with permission checks, and seed `admin`/`employee` as
     bundles of them. No visible change, but everything after it becomes
     possible. **Now scheduled** as Phase 0 of the Task Manager below — that
     module adds roughly ten more capability checks, and writing them as
     `isAdmin()` only to rewrite them later is wasted work.
  2. **Roles UI per company.** An admin creates roles and ticks permissions.
     This is where a "Task Manager" becomes expressible — company-wide.
  3. **Departments/teams.** Only if approval authority needs to be scoped to a
     manager's own people.

  *Still undecided:* whether companies invent arbitrary role names or pick from
  a fixed set with editable permissions, and whether the Task Manager role is
  company-wide or team-scoped. The second question was parked until the Task
  Plan feature existed to define what a Task Manager is responsible for; that
  feature is now specified below, so it can be answered once projects have
  owners and members.

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

## Task Manager — planned, specified August 2026

A second module inside the same tenant panel: projects containing tasks, a
drag & drop kanban board, and — the part that makes it a product rather than a
to-do list — **statuses and fields each company defines for itself**. Roughly
what Monday.com and ActiveCollab do, scoped to what a Greek SME actually needs.

The landing page has advertised this as "Σύντομα" since the CvTech rebrand.

### Decisions taken

- **Statuses and custom fields belong to the project**, with a set of company
  defaults that a new project inherits. Per-company-only was the simpler
  option and was rejected: a sales pipeline and a development backlog want
  different columns, and retrofitting per-project scoping later would mean
  migrating live data.

- **Kanban via `relaticle/flowforge`** (MIT). It solves the genuinely fiddly
  parts — fractional position ranking, optimistic UI, drag interactions.
  Because the data model stays ours, replacing it later costs a view layer,
  not a migration.

- **Custom fields built in-house, deliberately.** `relaticle/custom-fields` is
  the obvious candidate and is good, but it is **AGPL-3.0 or a paid commercial
  licence**. AGPL's network clause would require publishing the whole
  application source to anyone using the hosted product — incompatible with
  selling it. This constraint is worth remembering before reaching for any
  other plugin: check the licence *first*.

- **Permission catalogue first.** See the granular-permissions section above;
  the module adds around ten capability checks and there is no sense writing
  them as `isAdmin()` twice.

### Two requests that are not custom fields

Both were asked for as field types. Neither can be one:

- A **timer** is not a single value. It is a log of sessions — who worked,
  from when to when, repeatedly. Squeezed into a value column you cannot answer
  "how much time did this person put in last week". It gets its own
  `task_time_entries` table, with one running timer per user (starting a second
  stops the first).

- **Attachments** are many rows per task, each with a file behind it, and files
  need an access check. Paths in a value column work right until somebody
  shares a URL. They get `task_attachments`, stored on the **private disk** and
  served through an authenticated controller — not the public disk with
  `storage:link`, which is precisely the hole that had to be closed in the PDF
  report routes.

Both become first-class task features, switchable per project.

### Storage: typed EAV columns, not a JSON blob

`custom_field_values` carries `value_string`, `value_text`, `value_number`,
`value_date`, `value_boolean` and `value_json`, and each field type maps to
one of them.

The tempting alternative — a single JSON column on `tasks` — was rejected
because **MariaDB 10.11 has no native JSON type and no multi-valued indexes**.
JSON there is `LONGTEXT` with a validity check, so filtering or sorting by a
custom field means a full table scan. Filtering and sorting by custom fields is
most of what people do with them. If the database ever moves to PostgreSQL or
MySQL 8, the calculation changes.

### Phases

0. **Permission catalogue** — *done.* Authorisation moved off `isAdmin()` onto
   named permissions in `App\Support\Permissions`.
1. **Projects, statuses, tasks** — *done.* Statuses are seeded from company
   defaults the way leave types already are, and each project copies them so
   one board can diverge without disturbing the rest.

   Uncovered on the way: Filament's tenancy global scope was never active in
   tests, because it is registered in `Panel::boot()` which only a real request
   triggers. Every isolation test in the project had been passing because
   nothing was scoped in either direction. `actingInTenant()` now boots the
   panel per test — per test, not once, since each builds a fresh container and
   the scope closure compares against the Panel instance it captured.
2. **Custom fields** — *done.* Definitions are company-wide (applying to every
   board) or project-specific; unlike statuses they are inherited rather than
   copied, since a field like "Contract value" means the same thing everywhere.
   Values sit in typed columns chosen by `App\Support\CustomFieldType`, and
   `App\Services\CustomFieldSchema` turns a stored definition into a form
   input or a table column. Writes are filtered through the fields that
   actually apply to the task's project, so a crafted submission cannot attach
   another board's — or another company's — field.
3. **Kanban board** — *done, and not with Flowforge.* The spike found the
   blocker: the package registers only a JS asset and ships no CSS, so its
   views depend on the host application's Tailwind build scanning them
   (`@source` in a custom Filament theme). This project ships no compiled
   assets at all, and Vite 8 needs Node 20+ against the 18 on the machine —
   so adopting it meant a Node upgrade plus a build step on every deploy,
   permanently, for one feature.

   Built by hand instead: SortableJS vendored as a plain file in `public/js`,
   Alpine (already present), and self-contained CSS. `App\Services\TaskPosition`
   does the fractional ranking — midpoint between neighbours, renumber the
   column when the gap gets too small. Worth noting Flowforge's own column is
   `decimal(20,10)`, exactly what the schema already had.

   The drop endpoint resolves both the card and the target column *through the
   project*, so an id from another board or another company cannot be moved
   into place — the package's own handler writes the incoming column id
   unvalidated, so this guard would have been needed either way.
4. **Timer, attachments, comments** — *done.* Both timers and attachments are
   per-project switches, since a board that is a to-do list should not carry
   timesheet controls. `App\Services\TimeTracking` enforces one running timer
   per person: starting a second stops the first, or a forgotten timer bills a
   task nobody touched. Durations are stored on stop rather than derived, so a
   later clock change cannot rewrite how long something took.

   Attachments live on the private disk and are served only through
   `TaskAttachmentController`, which checks the viewer's company and permission
   — the public disk with a storage link would make every file readable by
   anyone holding the URL, and URLs get forwarded. Comments stay editable only
   by their author.
5. **Documentation** — all three Knowledge Base guides in EL and EN with
   screenshots, and the landing page stops saying "Σύντομα".

### Known risks

- Flowforge's published documentation does not show the integration API in
  detail; it has to be confirmed against the installed version. Fallback is a
  hand-rolled SortableJS board, about a day, with no change to the data model.
- The drag handler receives a status id from the browser. It must verify that
  status belongs to the project, or it is a cross-tenant write.
- `tenant_id` is denormalised onto `projects` and `tasks` on purpose.
  `LeaveRequest` has no direct tenant link, which forced
  `$isScopedToTenant = false` plus a hand-written `whereHas('user', ...)` on
  every query. Not repeating that.

## Near-term feature backlog (not SaaS-related, just "not built yet")

- **Real SMTP provider** — Mailpit is dev-only; production needs a real
  transactional mail provider configured in `.env`.
- **CI/CD** — tests (`php artisan test`) are run manually; no automated
  pipeline runs them on push/PR yet.
- **Knowledge Base maintenance** — the in-panel Knowledge Base pages
  (Employee/Admin guides) must be kept in sync by hand whenever a feature
  changes — there's no automated check for staleness.

## Status

**Leave management:** done and in active internal use. Multi-tenancy,
invitations, carry-over, impersonation, the platform panel and the three
Knowledge Base guides are all built.

**Task Manager:** specified, not started. Phase 0 (permission catalogue) is the
next piece of work.

**SaaS productization:** the branding and landing page exist; billing,
self-serve signup, real SMTP, CI/CD and production hosting do not. Nothing here
can be sold until at minimum SMTP and `APP_DEBUG=false` are dealt with — a
prospect cannot currently receive an invitation email.

**Backlog:** pick up opportunistically.
