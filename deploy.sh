#!/usr/bin/env bash
# ----------------------------------------------------------------------------
# cPanel one-shot deploy script for the JobBoard Laravel application.
#
# Usage on cPanel (after uploading the project to ~/jobboard):
#     cd ~/jobboard
#     bash deploy.sh
#
# What it does:
#   1. Verifies PHP >= 8.1
#   2. composer install (no dev)  -- skip if vendor/ was uploaded
#   3. npm ci && npm run build    -- only if Node is available
#   4. Storage & cache permissions
#   5. Creates the public/storage symlink
#   6. Generates APP_KEY if missing
#   7. Migrates & (optionally) seeds the DB
#   8. Caches config, routes, views
# ----------------------------------------------------------------------------
set -euo pipefail

cd "$(dirname "$0")"

log() { printf "\033[1;34m[deploy]\033[0m %s\n" "$*"; }
warn() { printf "\033[1;33m[warn]\033[0m %s\n" "$*"; }
err() { printf "\033[1;31m[err ]\033[0m %s\n" "$*"; }

# ---- 1. PHP check --------------------------------------------------------
if ! command -v php >/dev/null 2>&1; then
  err "php is not on PATH. Run with the full path, e.g. /usr/local/bin/php"
  exit 1
fi

PHP_MAJOR=$(php -r 'echo PHP_MAJOR_VERSION;')
PHP_MINOR=$(php -r 'echo PHP_MINOR_VERSION;')
if [ "$PHP_MAJOR" -lt 8 ] || { [ "$PHP_MAJOR" -eq 8 ] && [ "$PHP_MINOR" -lt 1 ]; }; then
  err "PHP $PHP_MAJOR.$PHP_MINOR detected. Laravel 10 needs >= 8.1."
  exit 1
fi
log "PHP $PHP_MAJOR.$PHP_MINOR detected."

# ---- 2. composer install (skip if vendor already exists) -----------------
if [ ! -d vendor ]; then
  if ! command -v composer >/dev/null 2>&1; then
    err "vendor/ missing and composer is not installed."
    exit 1
  fi
  log "Running composer install (no dev)..."
  composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
else
  log "vendor/ present — skipping composer install."
fi

# ---- 3. Frontend build (only if Node is available) ----------------------
if [ ! -d public/build ] || [ -z "$(ls -A public/build 2>/dev/null || true)" ]; then
  if command -v npm >/dev/null 2>&1; then
    log "Building front-end assets..."
    npm ci --no-audit --no-fund
    npm run build
  else
    warn "Node/npm not available and public/build is empty."
    warn "Upload a pre-built public/build/ from your dev machine."
  fi
else
  log "public/build already populated — skipping npm run build."
fi

# ---- 4. Permissions ------------------------------------------------------
log "Setting storage & cache permissions (775)..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# ---- 5. Storage symlink --------------------------------------------------
if [ ! -e public/storage ]; then
  log "Creating public/storage symlink..."
  php artisan storage:link
else
  log "public/storage already exists."
fi

# ---- 6. APP_KEY ----------------------------------------------------------
if ! grep -qE '^APP_KEY=base64:[A-Za-z0-9/+=]{20,}' .env 2>/dev/null; then
  log "Generating APP_KEY..."
  php artisan key:generate --force
else
  log "APP_KEY already set."
fi

# ---- 7. Migrate (and optionally seed) -----------------------------------
read -r -p "Run database migrations? [y/N] " MIGRATE
if [[ "$MIGRATE" =~ ^[Yy]$ ]]; then
  log "Running migrations..."
  php artisan migrate --force
  read -r -p "Also seed demo data? [y/N] " SEED
  if [[ "$SEED" =~ ^[Yy]$ ]]; then
    log "Seeding database..."
    php artisan db:seed --force
  fi
fi

# ---- 8. Optimize for production -----------------------------------------
log "Caching config / routes / views..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache || true

log "Done. Sanity checks:"
php artisan about --only=environment 2>/dev/null || true

log "Next steps:"
echo "  - Set up cron:  * * * * * /usr/local/bin/php \$HOME/jobboard/artisan schedule:run >> /dev/null 2>&1"
echo "  - Confirm /home/<user>/public_html is a symlink to /home/<user>/jobboard/public"
echo "  - Visit https://jobs.focusednet.com and register the first admin"
