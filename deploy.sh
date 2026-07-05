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
# Resolve the right PHP binary. Aliases from ~/.bashrc don't carry into
# non-interactive shells, so we walk a list of common cPanel paths.
PHP=""
for cand in \
  /opt/cpanel/ea-php84/root/usr/bin/php \
  /opt/cpanel/ea-php83/root/usr/bin/php \
  /opt/cpanel/ea-php82/root/usr/bin/php \
  /opt/cpanel/ea-php81/root/usr/bin/php \
  /opt/cpanel/ea-php80/root/usr/bin/php \
  /usr/local/bin/ea-php82 \
  /usr/local/bin/php \
  php; do
  if command -v "$cand" >/dev/null 2>&1; then
    PHP="$cand"
    break
  fi
done

if [ -z "$PHP" ]; then
  err "No PHP binary found. Install PHP 8.1+ or set the PHP env var."
  exit 1
fi

PHP_MAJOR=$("$PHP" -r 'echo PHP_MAJOR_VERSION;')
PHP_MINOR=$("$PHP" -r 'echo PHP_MINOR_VERSION;')
if [ "$PHP_MAJOR" -lt 8 ] || { [ "$PHP_MAJOR" -eq 8 ] && [ "$PHP_MINOR" -lt 1 ]; }; then
  err "PHP $PHP_MAJOR.$PHP_MINOR detected. Laravel 10 needs >= 8.1."
  exit 1
fi
log "PHP $PHP_MAJOR.$PHP_MINOR detected ($PHP)."

# ---- 2. composer install (skip if vendor already exists) -----------------
if [ ! -d vendor ]; then
  COMPOSER=""
  for cand in /home/focusedn/bin/composer /usr/local/bin/composer composer; do
    if command -v "$cand" >/dev/null 2>&1; then
      COMPOSER="$cand"
      break
    fi
  done
  if [ -z "$COMPOSER" ]; then
    err "vendor/ missing and composer is not installed."
    exit 1
  fi
  log "Running composer install (no dev)..."
  "$PHP" "$COMPOSER" install --no-dev --optimize-autoloader --no-interaction --prefer-dist
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
  "$PHP" artisan storage:link
else
  log "public/storage already exists."
fi

# ---- 6. APP_KEY ----------------------------------------------------------
if ! grep -qE '^APP_KEY=base64:[A-Za-z0-9/+=]{20,}' .env 2>/dev/null; then
  log "Generating APP_KEY..."
  "$PHP" artisan key:generate --force
else
  log "APP_KEY already set."
fi

# ---- 7. Migrate (and optionally seed) -----------------------------------
read -r -p "Run database migrations? [y/N] " MIGRATE
if [[ "$MIGRATE" =~ ^[Yy]$ ]]; then
  log "Running migrations..."
  "$PHP" artisan migrate --force
  read -r -p "Also seed demo data? [y/N] " SEED
  if [[ "$SEED" =~ ^[Yy]$ ]]; then
    log "Seeding database..."
    "$PHP" artisan db:seed --force
  fi
fi

# ---- 8. Optimize for production -----------------------------------------
log "Caching config / routes / views..."
"$PHP" artisan config:clear
"$PHP" artisan route:clear
"$PHP" artisan view:clear
"$PHP" artisan config:cache
"$PHP" artisan route:cache
"$PHP" artisan view:cache
"$PHP" artisan event:cache || true

log "Done. Sanity checks:"
"$PHP" artisan about --only=environment 2>/dev/null || true

log "Next steps:"
echo "  - Set up cron:  * * * * * \$PHP \$HOME/jobboard/artisan schedule:run >> /dev/null 2>&1"
echo "  - Confirm /home/<user>/public_html is a symlink to /home/<user>/jobboard/public"
echo "  - Visit https://jobs.focusednet.com and register the first admin"
