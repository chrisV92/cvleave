# CVLeave

Employee leave management system, built for Greek labour law compliance.

## Features

- Employees submit, view, and manage their own leave requests
- Admins approve/reject requests, manage users and leave types
- Automatic annual leave entitlement calculation per Greek law (Α.Ν. 539/1945) —
  staged 20/21/22/25/26 days based on employment year and total career
  seniority across employers — or simple fixed/tiered rules per leave type
- Shared calendar (personal view for employees, company-wide for admins)
- In-app + email notifications (new request, approved/rejected, leave
  starting/ending soon), including a daily reminder cron
- Overlap and remaining-balance validation on every submission
- Bilingual UI (Greek/English) with a language switcher
- Admin panel built on [Filament](https://filamentphp.com/)

## Requirements

- PHP 8.4+
- Composer
- MySQL/MariaDB
- Node.js + npm (for frontend assets, if/when added)
- A local mail-testing tool such as [Mailpit](https://mailpit.axllent.org/) for development

## Setup

```bash
git clone <this-repo>
cd cvleave

composer install
cp .env.example .env
php artisan key:generate
```

Configure your database in `.env`, then:

```bash
php artisan migrate
php artisan db:seed --class=LeaveTypeSeeder
php artisan make:filament-user
```

Set up the queue worker (required — notifications are queued) and the
scheduler (required for the daily leave-reminder cron):

```bash
php artisan queue:work
```

```cron
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

Serve the app (e.g. via nginx + php-fpm, or for local dev):

```bash
php artisan serve
```

Visit `/admin` to log in.

## Testing

```bash
php artisan test
```

Tests run against a dedicated database — see `phpunit.xml` for the
`DB_DATABASE` used, and create that database before running tests. Includes
unit tests for the Greek leave-accrual formula, feature tests for
authorization/overlap/balance validation, notification and reminder tests,
and N+1 query regression tests.

## Tech stack

Laravel 13, Filament 5, MariaDB, Pest.
