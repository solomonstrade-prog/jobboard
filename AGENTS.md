# AGENTS.md

> Agent operating manual for the **JobBoard** Laravel 10 project.
> Generated during project audit — keep this file in sync with build steps,
> test commands, and dependencies.

---

## 1. Project Snapshot

- **Type:** Laravel 10 web application (job board for Job Seekers, Employers, Admins).
- **Stack:** PHP 8.1+ · Laravel 10.x · MySQL/SQLite · Vite 5 · Tailwind 3.4 · Alpine.js · Bootstrap 5 · Chart.js.
- **Auth:** Laravel Breeze (customized for role-based onboarding).
- **Roles:** `admin`, `employer`, `Job Seeker` (note the space and capital S).
- **Domain (prod):** `https://jobs.focusednet.com` (cPanel — see `DEPLOY_TO_CPANEL.md`).

---

## 2. Prerequisites

| Tool | Version | Purpose |
|---|---|---|
| PHP | ≥ 8.1 | Runtime |
| Composer | ≥ 2.x | PHP dependencies |
| Node.js | ≥ 18 (tested 22.18) | Asset build |
| npm | ≥ 10 | Asset build |
| MySQL or MariaDB | 8.x / 10.x | Database (SQLite for tests) |

PHP extensions required (cPanel checklist):
`pdo_mysql`, `mbstring`, `xml`, `curl`, `zip`, `gd`, `fileinfo`, `openssl`, `tokenizer`.

---

## 3. Build & Setup

```bash
# 1. Install PHP dependencies
composer install

# 2. Install JS dependencies
npm install

# 3. Environment
cp .env.example .env
php artisan key:generate

# 4. Database (choose one)
php artisan migrate                  # blank schema
php artisan migrate --seed           # with demo data (see §6)

# 5. Frontend assets
npm run dev                          # watch mode (dev)
npm run build                        # production bundle in public/build

# 6. Serve
php artisan serve                    # http://127.0.0.1:8000
```

`composer install` triggers `post-autoload-dump` → `php artisan package:discover` automatically.

---

## 4. Testing

```bash
# Full test suite (uses sqlite in-memory + array drivers per phpunit.xml)
php artisan test

# Or PHPUnit directly
vendor/bin/phpunit

# Filter
php artisan test --filter=AuthenticationTest
php artisan test tests/Feature/EmployerApplicationTest.php
```

**Configuration** (`phpunit.xml`):
- `APP_ENV=testing`
- `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`
- `MAIL_MAILER=array`, `CACHE_DRIVER=array`, `SESSION_DRIVER=array`, `QUEUE_CONNECTION=sync`
- `BCRYPT_ROUNDS=4` (faster hashing in tests)

**Test layout:**
- `tests/Unit/` — model fillable, schema, factory, relationship integrity.
- `tests/Feature/` — auth, role-based redirects, job CRUD, application workflows.

---

## 5. Linting & Code Style

```bash
# PHP (Laravel Pint — default Laravel preset, included via composer require-dev)
vendor/bin/pint
vendor/bin/pint --test          # CI mode: exit 1 if changes needed

# Frontend — no linter/formatter currently configured in package.json
# (Add eslint/prettier under devDependencies if needed)
```

---

## 6. Database Seeding

`database/seeders/DatabaseSeeder.php` creates a full demo dataset in one call:
- 1 admin (`admin@jobboard.com` / `password`)
- 5 employers (TechCorp, FinancePlus, DesignHub, HealthFirst, EduSmart)
- 10 jobs across 8 categories
- 5 job seekers
- 5 applications
- **All demo accounts use password `password` — never deploy as-is.**

Run with: `php artisan migrate --seed`

`DatabaseSeeder` automatically delegates to `SampleDataSeeder`
(`database/seeders/SampleDataSeeder.php`) which augments the base dataset
with a richer demo population:
- 2 additional admins
- 10 additional employers (Technology, Logistics, Media, Healthcare, Finance, Gaming, Construction, FMCG, Aerospace, Education)
- 35 additional jobs across 10 categories and 12 locations
- 25 additional job seekers with varied skills, education, and experience
- ~50–75 additional applications (varied statuses, cover letters, dates spread over the last 60 days)
- 25–75 saved-job bookmarks

All sample accounts share the `sample.*` email namespace so the seeder can
be re-run safely on top of the base dataset. To run it on its own:

```bash
php artisan db:seed --class=SampleDataSeeder
```

---

## 7. Key Conventions

- **Eloquent profile pattern:** `users.profile_id` maps to a role-specific table
  (`profil_admins`, `profil_employers`, `profil_jobseekers`).
- **Authorization:** middleware `role:<Role>` (defined in
  `app/Http/Middleware/RoleMiddleware.php`); applied in `routes/web.php`.
- **Database portability:** dashboard month aggregations use
  `DB::connection()->getDriverName()` to switch between `MONTH()` (MySQL) and
  `strftime('%m', ...)` (SQLite). Keep this pattern when adding new aggregates.
- **Application status enum:** `pending` | `approved` | `rejected` (lowercase;
  use `'approved'`, **not** `'accepted'` — there is a test fixture relying on this).
- **Upload disk:** resumes go to the `public` disk under `resumes/` or `resume/`
  — keep `php artisan storage:link` in the deploy steps.
- **Logout route names** are namespaced per role: `admin.logout`, `employer.logout`, `jobseeker.logout`.

---

## 8. Frontend Asset Pipeline

- **Entry:** `resources/js/app.js`, `resources/css/app.css`
- **Config:** `vite.config.js`, `tailwind.config.js`, `postcss.config.js`
- **Build output:** `public/build/` (gitignored)
- **Watch:** `npm run dev`
- **Production:** `npm run build`

There is **no** `npm run lint` or `npm run test` script — add them if you
introduce eslint/prettier/jest.

---

## 9. Deployment (cPanel)

See `DEPLOY_TO_CPANEL.md` for the full step-by-step. Quick version:

1. Upload project to `~/jobboard/`.
2. Point document root at `~/jobboard/public` (or symlink).
3. `cp .env.example .env`, fill in real `DB_*` and `MAIL_*` values,
   `php artisan key:generate`.
4. `php artisan migrate --seed` (or via one-shot cron job).
5. `npm install && npm run build`.
6. `chmod -R 775 storage bootstrap/cache`.
7. Add cron `* * * * * php /home/<user>/jobboard/artisan schedule:run`.

---

## 10. Files to Watch / Repo Hygiene

- **Static previews** live under `previews/` — eight clickable HTML mockups
  (landing, jobs, job detail, login, register, jobseeker/employer/admin
  dashboards). Tailwind via CDN, no build step. Serve with
  `php -S 127.0.0.1:8000 -t previews` and open `http://127.0.0.1:8000/`.
  See `previews/README.md` for the full link table.
- `.env` — gitignored, never commit. The on-disk copy currently has placeholder
  `CHANGE_ME_*` values for DB credentials and must be filled in for production.
- `laravel` (Laravel installer binary at repo root) — gitignored, do not commit.
  Delete it from the project root if not needed locally.
- `migration_*.log`, `migration_output.txt` — debug artifacts at repo root;
  gitignored. Delete them after debugging sessions.
- `.gitattributes` already sets `* text=auto eol=lf` — write all new files in LF.
- No `AGENTS.md` will be auto-regenerated. Keep this file current; if you run
  `/init` in opencode, merge any new findings back into here.

---

## 11. Common Tasks

| Task | Command |
|---|---|
| Reset DB and reseed | `php artisan migrate:fresh --seed` |
| Clear caches | `php artisan optimize:clear` |
| Create storage symlink | `php artisan storage:link` |
| Run a single test file | `php artisan test tests/Feature/Auth/AuthenticationTest.php` |
| Format changed PHP files | `vendor/bin/pint app/Http/Controllers/Foo.php` |
| Tinker REPL | `php artisan tinker` |
| List all routes | `php artisan route:list` |
| List all artisan commands | `php artisan list` |
| Add rich sample data (additive) | `php artisan db:seed --class=SampleDataSeeder` |
