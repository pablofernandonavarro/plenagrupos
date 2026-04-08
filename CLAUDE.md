# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Full environment setup (first time)
composer setup

# Dev server (PHP + queue + logs + Vite, all concurrent)
composer dev

# Run tests
composer test

# Run a single test file
php artisan test tests/Feature/ExampleTest.php

# Reset and reseed the database
php artisan migrate:fresh --seed

# Generate recurring sessions for tomorrow (has --dry-run flag)
php artisan sessions:generate-recurring

# Format code
./vendor/bin/pint
```

## Architecture

### Roles & Access
Three roles: `admin`, `coordinator`, `patient`. A single `RoleMiddleware` enforces access via route middleware `role:admin|coordinator`. Routes are grouped under `routes/web.php` by role prefix (`/admin`, `/coordinador`, `/paciente`).

### Core Model: `Group`
The most complex model (~600 lines). Key computed behaviors:
- `isLiveSessionNow()` — whether the group is actively in session right now. Used everywhere to show/hide the "En vivo" badge and control attendance capture. **Always use this method** instead of checking `status` directly.
- `meetsOnDate(Carbon $date)` — whether a session occurs on a given date (handles recurrence logic).
- `isProgramVigente()`, `isProgramClosed()`, `isProgramPending()` — derived from `started_at`, `ended_at`, `active` flag.
- All date/time logic uses `America/Argentina/Buenos_Aires` timezone.

### Session Model Chain
`Group` → `GroupSession` (one row per calendar day per group) → `GroupAttendance` (one row per patient per session).

`Group::findOrCreateSessionForDate(Carbon $date)` creates/finds the `GroupSession` for a given date using a DB transaction with `lockForUpdate()` to prevent duplicates. **Note:** `lockForUpdate()` is a no-op in SQLite (dev), works correctly in MySQL (production).

### Recurrence
Groups define `recurrence_type` (none/daily/weekly/monthly/yearly) + `recurrence_interval` + `meeting_days` (JSON array of weekday names in Spanish). The `GenerateRecurringSessions` artisan command pre-creates `GroupSession` rows daily. Session status is always derived stateless from model fields + current time — no background jobs store state.

### Shared Controller Logic
`app/Http/Controllers/Concerns/BuildsGroupHistorial.php` is a trait shared between admin and coordinator group detail controllers. It builds the unified attendance history, membership events timeline, and weight statistics shown on group detail pages.

### Plan Enforcement
`PlanRule` model is a lookup table keyed on `(patient_plan, group_type) → monthly_limit`. Checked when enrolling or when displaying compliance statistics.

### Dual Weight Tracking
- `WeightRecord` — simple weight, optionally linked to an attendance record.
- `InbodyRecord` — full body composition (skeletal muscle, body fat %, visceral fat, BMI, inbody score, etc.). Supports OCR extraction from InBody machine printouts.

### No Livewire
The app uses traditional Blade + form submissions only. No Livewire components, no SPA framework.

## Database

Development uses SQLite (`database/database.sqlite`). Production uses MySQL. The `.env` is excluded from rsync on deploy so production config is never overwritten.

Test suite (`phpunit.xml`) uses an in-memory SQLite database — migrations run automatically.

## Deploy

Push to `main` triggers `.github/workflows/deploy.yml`, which runs `composer install --no-dev`, rsyncs to the server (excluding `.env`, `storage/logs`, cache), then runs `php artisan migrate --force` and `php artisan db:seed --class=AiDocumentSeeder --force` remotely. Required GitHub secrets: `SSH_PRIVATE_KEY`, `SSH_HOST`, `SSH_USER`, `DEPLOY_PATH`, `SSH_PORT`.
