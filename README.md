# CvTech

A multi-tenant business platform for Greek companies: leave management and
project/task management in one panel.

Each company is a tenant with its own users, roles, settings and data. Nothing
crosses between companies. A separate platform panel manages the companies
themselves.

## Features

### Leave management

- Annual leave entitlement calculated per Greek law (Α.Ν. 539/1945) — staged
  20/21/22/25/26 days based on employment year and total career seniority
  across employers — or simple fixed/tiered rules defined per leave type
- Requests in full days, half days or hours, validated on submission against
  overlaps and the remaining balance
- Approval workflow, with notification at every step
- Year-boundary carry-over with a per-company deadline and split-bucket
  accounting, so carried days are spent before the new year's allowance
- Shared calendar — personal for employees, company-wide for admins
- PDF and Excel export, per employee or per company
- Manual balance overrides for what the formula cannot know about

### Project and task management

- Projects with their own columns, custom fields and settings
- Kanban board with drag-and-drop between columns
- Custom fields each company defines itself — text, number, date, select,
  multi-select, checkbox, user, money, percent — as company-wide defaults that
  each project can override
- Time tracking per task and per person, one running timer per user
- Attachments on a private disk behind an authorisation check, with image
  previews
- Comments on tasks

### Across both

- In-app bell and email notifications, plus a Monday morning summary:
  employees get their own open work, admins get the company view
- Email invitations — employees set their own password
- Named permissions rather than a single admin flag
- In-app Knowledge Base: separate guides for employees, company admins and the
  platform operator, in Greek and English
- Bilingual interface (Greek/English) with a switcher
- Admin panel built on [Filament](https://filamentphp.com/)

## Requirements

- PHP 8.3+ (developed on 8.4)
- Composer
- MariaDB 10.11+ or MySQL 8
- A queue worker — notifications are queued
- Cron running `schedule:run` — reminders and the weekly summary depend on it
- For development, a mail catcher such as [Mailpit](https://mailpit.axllent.org/)

There is no frontend build step. `package.json` and `vite.config.js` are
Laravel's defaults and are unused: no view references `@vite`, Filament ships
its own compiled assets, and the one third-party script (SortableJS, for the
board) is vendored in `public/js/`.

## Setup

```bash
git clone <this-repo>
cd cvleave

composer install
cp .env.example .env
php artisan key:generate
```

There is deliberately no `storage:link` step. Uploads are served through an
authorising controller and must never become public URLs — putting them on the
linked public disk is exactly the leak that was fixed in the PDF reports.

Configure the database in `.env`, then set two values the Laravel defaults get
wrong for this application:

```dotenv
APP_TIMEZONE=Europe/Athens   # the default is UTC, which puts "today" a day
                             # behind for Greek users after midnight
APP_LOCALE=el
```

Then migrate:

```bash
php artisan migrate
php artisan filament:assets
```

### Creating the first login

The database starts empty, and `php artisan make:filament-user` is **not**
enough on its own: it creates a user with no company and no role, who cannot
reach either panel.

**For development**, seed a company with an admin in it:

```bash
php artisan db:seed
```

That creates the company `Default` with its leave types and roles, and an admin
`test@example.com` (the password is the factory default, `password`).

**For production**, create a platform administrator, then create the real
companies from the platform panel. `users.tenant_id` is `NOT NULL`, so the
account has to be attached to a company row even though it never works inside
one — `migrate` leaves a `Default` company behind for exactly this:

```bash
php artisan tinker --execute='
App\Models\User::create([
    "tenant_id" => App\Models\Tenant::firstOrCreate(
        ["slug" => "default"], ["name" => "Default"]
    )->id,
    "name" => "Your Name",
    "email" => "you@example.com",
    "password" => Hash::make("choose-a-strong-one"),
    "is_platform_admin" => true,
]);'
```

A platform administrator signs in at `/platform`, not `/admin`, and creates each
company together with its first administrator, whose password they set on the
form and hand over. From there that administrator invites the rest of the
company by email, and those people choose their own passwords.

> **Never run `migrate:fresh` against a database that holds real data.** It
> drops every table first. Use plain `migrate` to apply new migrations to an
> existing installation.

### Queue and scheduler

Both are required, not optional. Notifications are queued, and the reminders
and weekly summary run from the scheduler.

```bash
php artisan queue:work
```

```cron
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

In production run the worker under a process supervisor (systemd or Supervisor)
rather than by hand, so it restarts on failure and after a deploy.

What the scheduler runs:

| Command | When | What it does |
| --- | --- | --- |
| `leave:send-reminders` | daily 08:00 | leave starting or ending tomorrow |
| `tasks:send-reminders` | daily 08:05 | tasks due tomorrow; overdue once, then weekly |
| `tasks:send-weekly-digest` | Mondays 08:15 | personal and company task summaries |

Each can be run by hand to test it.

### Serving

```bash
php artisan serve          # development only
```

In production, nginx + php-fpm. Set `APP_ENV=production`, `APP_DEBUG=false`,
a correct `APP_URL`, and a real transactional mail provider — invitations,
reminders and the weekly summary all depend on outbound mail.

Employees and company admins sign in at `/admin`, platform administrators at
`/platform`.

## Testing

```bash
php artisan test
```

Tests run against a **separate database**, `cvleave_test` (see `phpunit.xml`).
Create it before the first run:

```bash
mysql -e 'CREATE DATABASE cvleave_test'
```

Note that the suite refreshes that database, so anything else using it is wiped.

Coverage includes the Greek leave-accrual formula, authorisation, overlap and
balance validation, tenant isolation, custom fields, the board's drop handler,
notifications and reminders, and N+1 query regressions.

Tenant isolation tests must go through `actingInTenant()` from `tests/Pest.php`.
Filament's tenancy scope does nothing unless the current panel is set, so an
isolation test written without it passes whether or not the scope works.

## Tech stack

Laravel 13, Filament 5, MariaDB, Pest 4.

## Licence

Proprietary. See [LICENSE](LICENSE).
