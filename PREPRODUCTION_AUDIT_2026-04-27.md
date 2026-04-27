# Pre-Production Audit — 2026-04-27

## Summary

| Severity  | Count | Meaning                    |
|-----------|-------|----------------------------|
| CRITICAL  | 3     | Deploy blockers            |
| HIGH      | 5     | Security / integrity risks |
| MEDIUM    | 5     | Fix before go-live         |
| LOW       | 3     | Nice to have               |
| PASS      | 47    | Confirmed working          |

---

## Critical Findings

---

### CRIT-1 — Debug mode ON and environment set to local
- **CHECKS:** A1, A2
- **FILE:** `.env` lines 3–4
- **ISSUE:** `APP_DEBUG=true` and `APP_ENV=local`. In production, debug mode exposes full stack traces, environment variables, and source code paths to any user who triggers an error. `APP_ENV=local` also activates the dev-login route.
- **IMPACT:** Full exception detail visible in browser. Dev routes active in "production". Attacker can enumerate internal paths, DB structure, and credentials through error pages.
- **FIX:** Set `APP_DEBUG=false` and `APP_ENV=production` in the production `.env` before starting the server.

---

### CRIT-2 — SQLite configured as database
- **CHECK:** A5
- **FILE:** `.env` line 46
- **ISSUE:** `DB_CONNECTION=sqlite` pointing to `database/database_new.sqlite`. SQLite has no row-level locking under concurrent writes — the entire database file locks. The application uses `lockForUpdate()` in `WorkflowTransitionService` (line 73) which relies on SELECT FOR UPDATE, a construct that is a no-op in SQLite.
- **IMPACT:** Race conditions on concurrent workflow transitions that the application explicitly guards against. Data corruption under load. `lockForUpdate()` provides zero protection.
- **FIX:** Switch to `DB_CONNECTION=mysql` with a MySQL 8.0 instance; update `DB_*` credentials accordingly.

---

### CRIT-3 — Duplicate migrations will break `php artisan migrate` on fresh install
- **CHECK:** D1, H3
- **FILES:**
  - `database/migrations/2024_04_06_000001_add_default_template_id_to_request_types_table.php`
  - `database/migrations/2026_04_06_013248_add_default_template_id_to_request_types_table.php`
  - `database/migrations/2024_04_06_000002_add_stage_signatures_to_requests_table.php`
  - `database/migrations/2026_04_06_013258_add_stage_signatures_to_requests_table.php`
- **ISSUE:** Two pairs of migrations perform identical operations (adding `default_template_id` and stage signature columns). Laravel runs all migration files in filename order. The 2024 file runs first and adds the column; the 2026 file then attempts to add the same column again and throws a fatal `Duplicate column` error.
- **IMPACT:** `php artisan migrate` fails entirely on a fresh database. Deployment to any new server is blocked.
- **FIX:** Delete the two 2024-dated files (`2024_04_06_000001` and `2024_04_06_000002`) — they are superseded by the 2026 versions.

---

## High Findings

---

### HIGH-1 — APP_URL points to localhost
- **CHECK:** A3
- **FILE:** `.env` line 5
- **ISSUE:** `APP_URL=http://localhost:8000`. This value is embedded in generated asset URLs, email links, and Storage disk URL configuration (`config/filesystems.php` line 44: `'url' => rtrim(env('APP_URL', ...), '/').'/storage'`).
- **IMPACT:** All notification links in emails, all public file URLs, and all generated absolute URLs will point to localhost. Emails sent to real users will contain dead links.
- **FIX:** Set `APP_URL=https://yourdomain.com` in the production `.env`.

---

### HIGH-2 — Mail driver is `log`; placeholder from-address
- **CHECK:** A6
- **FILE:** `.env` lines 68–75
- **ISSUE:** `MAIL_MAILER=log` means all mail is written to the log file and never delivered. `MAIL_FROM_ADDRESS=hello@example.com` is a placeholder.
- **IMPACT:** All workflow notification emails (submission confirmed, request returned, request approved, etc.) silently disappear. Users never receive them.
- **FIX:** Configure a real SMTP provider: set `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, and `MAIL_FROM_ADDRESS` to real values.

---

### HIGH-3 — User-submitted documents stored on public disk (directly URL-accessible)
- **CHECK:** B6
- **FILES:**
  - `app/Http/Controllers/RequestController.php` lines 123, 234
  - `app/Http/Controllers/DocumentController.php` lines 27, 61
- **ISSUE:** All user-submitted grant application documents and staff attachments are stored via `$file->store("documents/request-{$id}", 'public')`. The public disk maps to `storage/app/public/` which is symlinked to `public/storage/`. Any file stored there is directly accessible at `https://domain.com/storage/documents/request-1/filename.pdf` with no authentication check. The `download()` and `preview()` controllers check authorization, but the raw storage URL bypasses them entirely.
- **IMPACT:** Any person (unauthenticated) who knows or can guess a file URL can download sensitive grant application documents, signatures, and financial data.
- **FIX:** Change user submission and staff attachment uploads to `'local'` disk (which maps to `storage/app/private/`) and rely solely on the authorized `download()` / `preview()` controller endpoints.

---

### HIGH-4 — `staff2_signature_data` has no size cap in UpdateStatusRequest
- **CHECK:** B8
- **FILE:** `app/Http/Requests/UpdateStatusRequest.php` line 41
- **ISSUE:** `'staff2_signature_data' => 'nullable|string'` — no `max:` rule. By contrast, `StoreRequestRequest` correctly caps `signature_data` at `max:819200`. The staff2 approval signature field has no equivalent limit.
- **IMPACT:** A malicious authenticated staff2 user can POST an arbitrarily large base64 string (e.g. 50MB), potentially exhausting PHP memory and causing a 500 or OOM crash on every approval attempt (DoS against the approval workflow).
- **FIX:** Add `'max:819200'` to the `staff2_signature_data` rule in `UpdateStatusRequest`.

---

### HIGH-5 — AuditLog::create() in RequestController::store() is outside the DB transaction
- **CHECK:** C4
- **FILE:** `app/Http/Controllers/RequestController.php` lines 92–162
- **ISSUE:** The `DB::transaction(...)` closes at approximately line 142 (after returning `$gr`). `AuditLog::create([...])` is called at line 144 — after the transaction has committed. This violates the explicit ordering rule in `Agent.md` (rule 6): audit log creation must be inside the transaction.
- **IMPACT:** If `AuditLog::create()` fails (DB error, constraint violation), the request is already committed with no audit trail for the initial submission. More critically, if a partial failure occurs, there is no rollback — the request exists but the submission audit entry does not.
- **FIX:** Move the `AuditLog::create([...])` block (lines 144–153) inside the `DB::transaction()` closure, after the `Signature::updateOrCreate()` call.

---

## Medium Findings

---

### MED-1 — Removed-feature columns (`deadline`, `is_priority`, `snapshot_requires_dean_signature`) still in DB schema
- **CHECK:** D4, D3, G9
- **FILES:**
  - `database/migrations/2026_03_25_080918_add_revision_fields_to_requests_table.php` lines 13–14
  - `database/migrations/2026_04_14_000002_add_snapshot_requires_dean_signature_to_requests_table.php` line 12
  - `database/migrations/2026_04_20_000001_revamp_workflow_and_add_documents_table.php` lines 60–62
- **ISSUE:** The revamp workflow migration (Apr 20) re-adds `deadline` and `is_priority` columns. The `snapshot_requires_dean_signature` migration (Apr 14) adds that column and is never explicitly dropped. These columns belong to features explicitly listed in `Agent.md` section 15 as "REMOVED — NEVER ADD BACK."
- **IMPACT:** Schema carries dead weight columns. Any future ORM mass-assignment audit or `$fillable` review is confused. No runtime crash, but documents a broken invariant.
- **FIX:** Add a new migration that drops `deadline`, `is_priority`, and `snapshot_requires_dean_signature` from the `requests` table (using `if Schema::hasColumn()`).

---

### MED-2 — Two SQLite database files coexist
- **CHECK:** D9
- **FILES:** `database/database.sqlite` (376 KB, last modified Apr 15), `database/database_new.sqlite` (278 KB, last modified Apr 27)
- **ISSUE:** `.env` points to `database_new.sqlite` but the older `database.sqlite` still exists. Both are committed in the repo (or present locally). Unclear which is canonical. The older file may contain real user data.
- **IMPACT:** Confusion during deployment. Risk of pointing production `.env` at wrong file. Potential data leak if old file with real records is included in deployment package.
- **FIX:** Delete `database/database.sqlite` after confirming all needed data is in `database_new.sqlite`. Add `*.sqlite` to `.gitignore` (except a blank placeholder if needed).

---

### MED-3 — At least one template document record points to a missing file
- **CHECK:** E6, D8
- **SOURCE:** Seeder output: `Template file not found: blank-forms/imfTpCXuJWfsGCbPfDSNu14icefUpL4EaKrZc9yv.pdf`
- **ISSUE:** A `documents` table row has `document_type = 'template'` with a `file_path` that does not exist in `storage/app/public/blank-forms/`.
- **IMPACT:** Downloading or previewing that template returns a 404 from the controller. Zone designer will also fail to load its PDF. Users see an error when trying to view that request type's template.
- **FIX:** Either re-upload the missing PDF via the Staff2 template management UI, or delete the orphaned `documents` row if the template is no longer needed.

---

### MED-4 — Institutional placeholder "UNIKL123456" in profile form
- **CHECK:** G1
- **FILE:** `resources/views/update-profile-information-form.blade.php` line 26
- **ISSUE:** `placeholder="e.g. UNIKL123456"` — hardcodes a university-specific staff ID format as a UX hint.
- **IMPACT:** Violates `Agent.md` rule 10: no university branding in any view. If this system is deployed for a different institution, the placeholder is incorrect and identifies the original institution.
- **FIX:** Change placeholder to a generic value, e.g. `placeholder="e.g. STF001234"`.

---

### MED-5 — Dead controllers exist in codebase
- **CHECK:** G3
- **FILES:** `app/Http/Controllers/AdminController.php`, `app/Http/Controllers/OverrideController.php`
- **ISSUE:** Both files exist but neither is imported or referenced in any route file. They are unreachable from the web but add noise, may contain outdated logic, and could confuse future developers.
- **IMPACT:** No runtime impact. Maintenance burden; any security audit will flag unrouted controllers with unreviewed logic.
- **FIX:** Delete both files after confirming they contain no logic reused by other classes.

---

## Low Findings

---

### LOW-1 — `FEATURE_*` flags defined in `.env` but not consumed in application code
- **CHECK:** A11
- **FILE:** `.env` lines 13–19
- **ISSUE:** Six `FEATURE_*` environment variables (`FEATURE_ADVANCED_ANALYTICS`, `FEATURE_EMAIL_NOTIFICATIONS`, etc.) are declared but grep of `app/` finds no `config('FEATURE_')`, `env('FEATURE_')`, or feature-flag checks referencing them.
- **IMPACT:** Dead configuration. Operators may believe toggling `FEATURE_EMAIL_NOTIFICATIONS=false` disables emails — it does not.
- **FIX:** Either wire them into the application or remove them from `.env` and `.env.example` to avoid false confidence.

---

### LOW-2 — Stray file `an db:seed` in project root
- **CHECK:** B4
- **FILE:** `./an db:seed` (root directory)
- **ISSUE:** A file literally named `an db:seed` exists in the project root. It appears to be the output of a `less` command accidentally redirected to a file (its contents are the `less` man page). This was almost certainly created by a typo: `> an db:seed` instead of running `php artisan db:seed`.
- **IMPACT:** Harmless at runtime. Will be included in any `git add .` or deployment package. Looks unprofessional in a public repository.
- **FIX:** Delete the file: `rm "./an db:seed"`.

---

### LOW-3 — `.env.example` has `LOG_LEVEL=debug`
- **CHECK:** G6
- **FILE:** `.env.example` line 43
- **ISSUE:** The example file templates `LOG_LEVEL=debug`. A developer copying `.env.example` to `.env` for a production setup will start with verbose debug logging.
- **IMPACT:** Production logs fill with debug output; log disk exhaustion risk on busy servers.
- **FIX:** Change `LOG_LEVEL=debug` to `LOG_LEVEL=error` in `.env.example`.

---

## Passed Checks

- **A4** — `APP_KEY` is set and non-empty in `.env` ✓
- **A7** — `.env.example` has empty `APP_KEY=`; no real credentials ✓
- **A8** — No university-specific values in `.env.example` ✓
- **A9** — `.env` is in `.gitignore` (`/.env` listed) ✓
- **A10** — No `.env.development` or `.env.local` committed ✓
- **A12** — `QUEUE_CONNECTION=database` (not sync) ✓
- **B1** — Dev-login route is inside `if (app()->environment('local'))` guard ✓
- **B2** — No `simulate-*.php` or `test-*.php` in project root ✓
- **B3** — No stray `.blade.php` files in project root ✓
- **B5** — Signed documents stored on `local` disk (`storage/app/private/`) via `Storage::disk('local')` in `DocumentSigningService` lines 152, 258 ✓
- **B7** — File uploads validated with both `mimes:` and `mimetypes:` in `StoreRequestRequest` and `DocumentController` ✓
- **B9** — Status transition endpoint has `throttle:30,1` middleware (`routes/web.php` line 39) ✓
- **B10** — No `dd()`, `dump()`, `var_dump()`, or `print_r()` found in `app/` ✓
- **B11** — CSRF protection active via web middleware on all POST/PATCH/DELETE routes ✓
- **B12** — All document downloads route through `DocumentController::download()` which calls `$this->authorize('view', $grantRequest)` ✓
- **C1** — All status changes go through `WorkflowTransitionService::executeTransition()`; no direct `status_id` writes in controllers ✓
- **C2** — `DB::transaction()` opens before any validation in `executeTransition()` (line 70 before line 78) ✓
- **C3** — `lockForUpdate()` called inside the transaction (line 73) ✓
- **C5** — `Signature::updateOrCreate()` is inside the transaction in `WorkflowTransitionService::saveStageSignatures()` ✓
- **C6** — `COMPLETED` and `DECLINED` have no outgoing transitions in `getAllowedTransitions()` — truly terminal ✓
- **C7** — Checklist enforcement filters `->where('is_active', true)` in both `WorkflowTransitionService` (line 133) and `Request::getChecklistItems()` (line 162) ✓
- **C8** — No dean/priority/deadline logic found in any active controller, service, or model code path ✓
- **D3** — Dean approval columns (`dean_approved_by`, etc.) are removed by the revamp migration (`2026_04_20_000001`) using `hasColumn()` check before dropping ✓
- **D5** — `system_settings` table migration exists; `SettingsService` has defensive defaults fallback ✓
- **D6** — `signatures` table migration exists (`2026_04_08_120000_create_signatures_table.php`) ✓
- **D7** — `zones` and `pdf_page_count` columns exist in `documents` table (migration `2026_04_23_100000`) ✓
- **D10** — Performance indexes on `requests.status_id`, `requests.user_id`, `requests.request_type_id`, `documents.request_id` created in `2026_04_20_200000` ✓
- **E1** — `public/storage` symlink exists and correctly points to `storage/app/public` (confirmed `lrwxrwxrwx`) ✓
- **E2** — `storage/app/public/` exists and is writable (files present confirm this) ✓
- **E3** — `storage/app/private/` exists; `local` disk root is `storage_path('app/private')` per `config/filesystems.php` line 35 ✓
- **F5** — `SettingsService` uses versioned `v2_*` cache keys with `instanceof` type-checking and empty-collection fallback to defaults ✓
- **F6** — `Notification::createForUser()` has 5-minute deduplication window (`app/Models/Notification.php` line 48–53) ✓
- **G2** — No `TODO`, `FIXME`, or `HACK` comments found in `app/` ✓
- **G4** — `DeanUserSeeder` not called in `DatabaseSeeder::run()` ✓
- **G5** — `AdvancedRequestRequest` does not exist in `app/Http/Requests/` ✓
- **G7** — `README.md` exists in project root ✓
- **G8** — No Windows-style paths (`C:\`) found ✓

---

## Production Deployment Steps

Assumes Ubuntu 22.04, Nginx, PHP 8.3, MySQL 8.0. Commands to run in order on the server.

```bash
# 1. Server packages
sudo apt update && sudo apt install -y nginx php8.3-fpm php8.3-mysql php8.3-gd \
  php8.3-mbstring php8.3-xml php8.3-zip php8.3-curl php8.3-fileinfo \
  php8.3-sqlite3 mysql-server nodejs npm git unzip

# 2. Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# 3. Deploy application
cd /var/www
git clone <repo> strg && cd strg

# 4. PHP dependencies (no dev)
composer install --no-dev --optimize-autoloader

# 5. Node build
npm ci && npm run build

# 6. Create and populate .env
cp .env.example .env
# Then edit .env — set ALL of the following before continuing:
#   APP_ENV=production
#   APP_DEBUG=false
#   APP_URL=https://yourdomain.com
#   APP_KEY=  (fill after step 7)
#   DB_CONNECTION=mysql
#   DB_HOST=127.0.0.1
#   DB_PORT=3306
#   DB_DATABASE=strg
#   DB_USERNAME=strg_user
#   DB_PASSWORD=<strong-password>
#   MAIL_MAILER=smtp
#   MAIL_HOST=<smtp-host>
#   MAIL_PORT=587
#   MAIL_USERNAME=<smtp-user>
#   MAIL_PASSWORD=<smtp-password>
#   MAIL_FROM_ADDRESS=<real-address>
#   MAIL_ENCRYPTION=tls
#   LOG_LEVEL=error

# 7. Generate app key
php artisan key:generate

# 8. MySQL: create database and user
mysql -u root -p <<'SQL'
CREATE DATABASE strg CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'strg_user'@'localhost' IDENTIFIED BY '<strong-password>';
GRANT ALL PRIVILEGES ON strg.* TO 'strg_user'@'localhost';
FLUSH PRIVILEGES;
SQL

# 9. Run migrations  ← CRIT-3 must be resolved first (delete duplicate 2024 migrations)
php artisan migrate --force

# 10. Seed production data (request types + templates only — NOT dev accounts)
php artisan db:seed --class=RequestTypeSeeder --force
php artisan db:seed --class=TemplateSeeder --force
# Then create the first admin user manually via tinker:
php artisan tinker --execute="
  App\Models\User::create([
    'name' => 'System Admin',
    'email' => 'admin@yourdomain.com',
    'password' => Hash::make('<strong-password>'),
    'role' => 'admin',
    'is_active' => true,
    'email_verified_at' => now(),
  ]);
"

# 11. Storage symlink
php artisan storage:link

# 12. Cache for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 13. Storage permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# 14. Nginx config — point root to /var/www/strg/public
# /etc/nginx/sites-available/strg:
cat <<'NGINX' | sudo tee /etc/nginx/sites-available/strg
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/strg/public;
    index index.php;

    location / { try_files $uri $uri/ /index.php?$query_string; }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }

    client_max_body_size 15M;
}
NGINX
sudo ln -s /etc/nginx/sites-available/strg /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx

# 15. Queue worker (systemd service for database queue)
cat <<'SERVICE' | sudo tee /etc/systemd/system/strg-queue.service
[Unit]
Description=STRG Queue Worker
After=network.target

[Service]
User=www-data
WorkingDirectory=/var/www/strg
ExecStart=/usr/bin/php artisan queue:work database --sleep=3 --tries=3 --timeout=90
Restart=on-failure

[Install]
WantedBy=multi-user.target
SERVICE
sudo systemctl enable --now strg-queue

# 16. SSL (Let's Encrypt)
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com
```

---

## Go/No-Go Recommendation

```
READY FOR PRODUCTION: NO
```

### Must fix before deploying (blockers)

| # | Finding | Estimated time |
|---|---------|----------------|
| CRIT-1 | Set `APP_DEBUG=false`, `APP_ENV=production` | 2 min |
| CRIT-2 | Switch to MySQL | 30 min |
| CRIT-3 | Delete duplicate migration files (`2024_04_06_000001`, `2024_04_06_000002`) | 5 min |
| HIGH-1 | Set real `APP_URL` | 2 min |
| HIGH-2 | Configure real SMTP mail | 15 min |
| HIGH-3 | Move user uploads to private disk | 45 min |
| HIGH-4 | Add `max:819200` to `staff2_signature_data` rule | 5 min |
| HIGH-5 | Move `AuditLog::create()` inside transaction in `RequestController::store()` | 10 min |

**Estimated total blocker fix time: ~2 hours**

### Can fix post-deploy (conditionally acceptable)

- MED-1: Drop legacy DB columns (doesn't crash, schema-only cleanup)
- MED-2: Delete old `database.sqlite` file
- MED-3: Re-upload missing template file via Staff2 UI
- MED-4: Fix UNIKL placeholder text
- MED-5: Delete dead controllers
- LOW-1, LOW-2, LOW-3: All minor

**Conditional deploy is acceptable only if HIGH-3 (public disk exposure) is acknowledged as a known risk and the deployment audience is internal/trusted users.**
