# Job-Board cPanel Deployment Guide
## Domain: jobs.focusednet.com

> A streamlined, copy-pasteable runbook. The project now ships a
> `deploy.sh` one-shot script (see `DEPLOY_cpanel_notes.md`) — the
> steps below are still useful as a reference and for hosts where
> you cannot run shell scripts.

---

## STEP 0: Local pre-flight (on your dev box, BEFORE uploading)

```bash
# 1. Make sure dependencies and a production front-end build are ready.
composer install --no-dev --optimize-autoloader
npm ci
npm run build          # writes public/build/

# 2. Sanity checks
vendor/bin/pint --test
php artisan test
```

> The build step **is** required for cPanel shared hosting that does
> not have Node.js. The compiled assets live in `public/build/` and
> MUST be uploaded (or pushed via git) alongside the rest of the
> project.

---

## STEP 1: cPanel Preparation (Do This First)

### 1a. Set PHP Version
- Login to cPanel
- Go to **MultiPHP Manager** (or "Select PHP Version")
- Find your domain/subdomain `jobs.focusednet.com`
- Set PHP to **8.1** or **8.2**
- Click on "Extensions" and ensure these are ENABLED:
  - `pdo_mysql`, `mbstring`, `xml`, `curl`, `zip`, `gd`, `fileinfo`, `openssl`, `tokenizer`
- Optional but recommended: enable `opcache` for performance

### 1b. Create MySQL Database
- cPanel > **MySQL Databases**
- Create database: e.g., `focusednet_jobboard`
- Create user: e.g., `focusednet_jbuser`
- Password: generate a strong one
- Add user to database with **ALL PRIVILEGES**
- **SAVE the database name, username, and password!**

### 1c. Enable Terminal (strongly recommended)
- cPanel > Advanced > **Terminal**
- If available, you can run `bash deploy.sh` from the project root.
- If not, the runbook below works with the cPanel File Manager and
  one-shot cron jobs.

---

## STEP 2: Upload Files via FTP / Git

### Option A: FTP / File Manager
1. Connect to your cPanel FTP.
2. Upload the **entire project** to `/home/<cpaneluser>/jobboard/`.
3. Make sure `public/build/` is included (it is the Vite output).

### Option B: Git
```bash
cd ~
git clone <your-repo-url> jobboard
cd jobboard
composer install --no-dev --optimize-autoloader
npm ci && npm run build
```

> cPanel Terminal sometimes lacks `git` or `npm`. In that case use
> Option A and upload a tarball that already includes `vendor/` and
> `public/build/`.

After upload, the layout must be:
```
/home/<cpaneluser>/jobboard/
  app/
  bootstrap/
  config/
  database/
  public/        <-- This becomes the web root
  resources/
  routes/
  storage/
  vendor/
  .env
  artisan
  ...
```

---

## STEP 3: Configure Document Root

**Method A (preferred — cPanel allows custom document root):**
- cPanel > **Domains** > `jobs.focusednet.com`
- Set document root to: `/home/<cpaneluser>/jobboard/public`

**Method B (symlink — requires Terminal):**
```bash
rm -rf ~/public_html
ln -s ~/jobboard/public ~/public_html
```

> The root `.htaccess` shipped in this repo blocks access to
> `app/`, `vendor/`, `.env`, etc. in case Method A is not available
> and the symlink is wrong.

---

## STEP 4: Configure `.env`

```bash
cd ~/jobboard
cp .env.production.example .env
nano .env       # or edit via cPanel File Manager
```

Fill in:

```
APP_NAME="JobFinder"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://jobs.focusednet.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=<cpaneluser>_jobboard
DB_USERNAME=<cpaneluser>_jbuser
DB_PASSWORD=<strong-password>

MAIL_MAILER=smtp
MAIL_HOST=mail.focusednet.com
MAIL_PORT=465
MAIL_USERNAME=noreply@jobs.focusednet.com
MAIL_PASSWORD=<mail-password>
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS="noreply@jobs.focusednet.com"
MAIL_FROM_NAME="${APP_NAME}"
```

Notes:
- `DB_HOST=localhost` is correct on cPanel even though the daemon
  is local — cPanel proxies it. Use `127.0.0.1` only if your host
  documents it.
- The first time you run `php artisan key:generate` a new
  `APP_KEY` will be written into `.env` automatically.

---

## STEP 5: Run the deploy script (or its manual equivalent)

### Recommended (one command):

```bash
cd ~/jobboard
bash deploy.sh
```

The script will:
1. Check PHP version.
2. Run `composer install --no-dev` (if `vendor/` is missing).
3. Run `npm ci && npm run build` (if Node is available AND
   `public/build/` is empty).
4. Fix permissions on `storage/` and `bootstrap/cache/`.
5. Create the `public/storage` symlink.
6. Generate `APP_KEY` if missing.
7. Run migrations (after a `y/N` prompt).
8. Run seeders (after a `y/N` prompt).
9. Rebuild `config:cache`, `route:cache`, `view:cache`,
   `event:cache`.

### Manual fallback (no terminal, just cron):

cPanel > **Cron Jobs** — add a one-time job, then delete it:

```bash
/usr/local/bin/php /home/<cpaneluser>/jobboard/artisan migrate --seed --force
```

Or split into:
```bash
/usr/local/bin/php /home/<cpaneluser>/jobboard/artisan migrate --force
/usr/local/bin/php /home/<cpaneluser>/jobboard/artisan db:seed --force
/usr/local/bin/php /home/<cpaneluser>/jobboard/artisan config:cache
/usr/local/bin/php /home/<cpaneluser>/jobboard/artisan route:cache
/usr/local/bin/php /home/<cpaneluser>/jobboard/artisan view:cache
```

After each job succeeds, **delete it** from the cron list.

---

## STEP 6: Set Up Scheduled Tasks

Add to cPanel > **Cron Jobs** (runs every minute):

```
* * * * * /usr/local/bin/php /home/<cpaneluser>/jobboard/artisan schedule:run >> /dev/null 2>&1
```

> The project does not yet have scheduled jobs (see
> `app/Console/Kernel.php`), but the cron entry is required the
> moment any are added and is harmless to leave in place.

---

## STEP 7: Fix Permissions

```bash
chmod -R 775 ~/jobboard/storage
chmod -R 775 ~/jobboard/bootstrap/cache
```

> cPanel often ignores `chmod` over FTP. If files are uploaded as
> the cPanel user, the defaults usually work. The `deploy.sh`
> script already runs these commands.

---

## STEP 8: Force HTTPS (already enabled in `.htaccess`)

The shipped `public/.htaccess` already contains a `RewriteCond` to
redirect `http://` traffic to `https://`. To disable it (e.g. for
HTTP-only staging), comment out the two lines under "Force HTTPS".

`public/.user.ini` sets sensible PHP limits for cPanel shared
hosting (uploads up to 8 MB, 256 MB memory, secure session
cookies). Adjust if your host has stricter caps.

---

## STEP 9: First Login & Admin Setup

1. Visit: https://jobs.focusednet.com/register
2. Register your admin account
3. Go to cPanel > **phpMyAdmin**
4. Find the `users` table and edit your user:
   - Set the `role` column to: `admin`
5. Login at: https://jobs.focusednet.com/login
6. You should see the admin dashboard!

> The `DatabaseSeeder` already creates an `admin@jobboard.com`
> account (password: `password`). **Change it** the first time you
> log in, or delete it via phpMyAdmin and use STEP 9 above.

---

## STEP 10: Post-Install Checklist

- [ ] Site loads at https://jobs.focusednet.com (HTTPS, no mixed content)
- [ ] `php artisan about` shows `APP_ENV=production` and `APP_DEBUG=false`
- [ ] `public/build/assets/` is reachable (Tailwind + Alpine loaded)
- [ ] `/storage/avatars/...` (or any uploaded resume) is reachable
- [ ] Can register, login, logout
- [ ] Admin dashboard works (after setting `role='admin'` in DB)
- [ ] Employer can post a job; jobseeker can apply with a PDF resume
- [ ] Email notifications send (test with `php artisan tinker` →
  `Mail::raw('test', fn($m) => $m->to('you@example.com')->subject('test'));`)
- [ ] One-time cron jobs removed (only the `schedule:run` line remains)
- [ ] `storage/logs/laravel.log` rotated / empty

---

## Troubleshooting

| Problem | Fix |
|---------|-----|
| 500 Error | Check `.env` `APP_KEY` is set. `php artisan key:generate` |
| Database error | Verify `DB_*` in `.env`. cPanel DBs need the `cpaneluser_` prefix |
| CSS not loading | `public/build/` is missing — re-run `npm run build` |
| 404 on all pages | Document root must be `/home/<user>/jobboard/public` |
| Resume upload fails | Bump `upload_max_filesize` in `public/.user.ini` (max 8M) |
| Storage symlink error | `php artisan storage:link` (run as the cPanel user) |
| "Permission denied" on logs | `chmod -R 775 storage bootstrap/cache` |
| Blank page | Temporarily set `APP_DEBUG=true`, check `storage/logs/laravel.log` |
| Mixed-content warnings | Make sure `APP_URL` uses `https://` and the HTTPS rewrite in `public/.htaccess` is active |

---

## Files in this repo that are deploy-specific

| File | Purpose |
|---|---|
| `.htaccess` (root) | Denies all access if web root is mistakenly set to project root |
| `public/.htaccess` | Laravel rewrites + Force HTTPS |
| `public/.user.ini` | cPanel PHP overrides (memory, upload, sessions) |
| `.env.production.example` | Template for the on-server `.env` |
| `deploy.sh` | One-shot idempotent installer |
| `DEPLOY_cpanel_notes.md` | Usage notes for `deploy.sh` |
