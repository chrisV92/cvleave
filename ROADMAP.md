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

- **Multi-tenancy** — currently single-company (one set of users/leave types).
  Would need a `companies`/`tenants` table and scoping on every model.
- **Billing** — Stripe/subscription integration, plan limits, trial handling.
- **Legal validation** — the Greek-law formula should be reviewed by an
  accountant/lawyer before being relied on for real payroll decisions, and
  edge cases (six-day work week, part-time, parental leave, etc.) aren't
  covered yet.
- **Onboarding flow** — self-serve company signup, not admin-provisioned users.
- **Production hosting** — this currently runs on a home server over
  Tailscale; a public product needs real infrastructure, backups, uptime.

## Status

Parked. Revisit if/when there's appetite to productize.
