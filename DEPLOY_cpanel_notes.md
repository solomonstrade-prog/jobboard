# cPanel Deployment Notes — `deploy.sh`

`deploy.sh` is an idempotent one-shot script you can run from cPanel
Terminal (or via SSH) to bring a fresh upload up to a runnable state.

## Typical run

```bash
cd ~/jobboard
bash deploy.sh
```

You will be prompted whether to run migrations and (optionally) seed
demo data. Both default to **No** — answer `y` only on first install.

## What it does

1. Verifies PHP >= 8.1.
2. `composer install --no-dev --optimize-autoloader` (skipped if
   `vendor/` is already present, e.g. uploaded via FTP).
3. `npm ci && npm run build` — only if `public/build` is empty AND
   `npm` is on PATH. If Node is not available on cPanel, upload a
   pre-built `public/build/` from your dev box.
4. `chmod -R 775 storage bootstrap/cache`.
5. `php artisan storage:link` (skipped if `public/storage` exists).
6. `php artisan key:generate --force` (only when `APP_KEY` is empty).
7. Migrations and (optional) seeders.
8. `config:cache`, `route:cache`, `view:cache`, `event:cache`.

## Why a script (and not just a doc)?

cPanel shared hosting often lacks `php artisan` from a login shell's
default `$PATH`. The script calls `php` directly and can be re-run
after any subsequent deploy.

## Re-deploys

For routine redeploys, just upload the changed files and run:

```bash
cd ~/jobboard && bash deploy.sh
```

Answer `n` to the migrate/seed prompts unless you actually changed
the schema. The script will still re-run cache rebuilds.
