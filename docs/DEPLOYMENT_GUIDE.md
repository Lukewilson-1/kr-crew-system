# KR Crew System — Deployment Guide

> **Applies to:** KR Crew System (CM & RM System) · Laravel 11 / PHP 8.4 / MySQL 8 · `cms.krc.co.ke`
> **Version:** 1.0 · **Date:** 2026-09-09 · **Owner:** Kenya Railways Corporation – ICT Division

---

## 1. Purpose & Scope

This guide covers the deployment of the KR Crew System to an enterprise production server. It specifies:

- **Minimum / recommended server requirements** sized for **~20 concurrent users** (a root-access VPS so system cron can run the Laravel scheduler).
- The supported software stack (PHP, MySQL, web server) and the cron daemon.
- Step-by-step deployment, cache/link setup, and scheduled-task configuration.
- First-boot verification and post-deployment security checklist.

**Out of scope:** code development workflow, feature details, and the SSO rollout (see `SYSTEM_DOCUMENTATION.md` Appendix F).

---

## 2. Deployment Server Requirements (sized for ~20 concurrent users)

> **Hosting model (required):** a **dedicated VPS / cloud IaaS VM with full OS-level access** (root/sudo + usable `crontab`). System cron (§5) is the primary scheduling path, and it **cannot** run on shared-hosting plans (no `crontab`, no persistent scheduler). Platforms such as Microsoft shared hosting / cPanel shared / PHP-only App Service plans are therefore **not** suitable for the default deployment. Budget ~US$30–60/month for an adequate VM; this is justified by the operational-criticality of the scheduled jobs.

The application is a Laravel 11 + Filament 4 app with file-based sessions/cache, a vanilla-JS SPA, and a MySQL database. It is **I/O-light on the web tier** and **database-bound** on report generation. The figures below assume ~20 simultaneous users (peak daily-status entry + running-room ops + monthly register generation).

### 2.1 Minimum (acceptable) specification

| Resource | Requirement | Notes |
|---|---|---|
| **Hosting model** | **VPS / cloud VM with root or sudo + `crontab` access** | Mandatory — system cron runs the Laravel scheduler (§5). Verify with `crontab -l` as the deploy user before committing |
| **CPU** | 2 vCPU (≥2.0 GHz) | Report generation (monthly register) is the heaviest operation |
| **Memory (RAM)** | **16 GB minimum / 32 GB recommended** | PHP-FPM pool + MySQL buffer pool; the biggest bottleneck for concurrent report generation |
| **Storage** | 100 GB SSD (OS + data) minimum / **200 GB SSD recommended** | SSD strongly recommended for MySQL, sessions, logs, audit trail, and database backups (retained ≥30 days) |
| **Swap** | 10 GB swap | Safety under transient spikes |
| **OS** | Ubuntu 22.04/24.04 LTS (or equivalent) | Linux with cron daemon active by default |

> **Verdict for ~20 concurrent users:** a **2 vCPU / 16 GB RAM cloud VM with root/crontab access** (32 GB recommended) and **≥200 GB SSD** is the recommended target. This comfortably handles the workload, runs the system cron scheduler, and leaves generous headroom for report generation, audit retention, and backups.

### 2.2 Why this server (system-cron support)

| Requirement | Purpose |
|---|---|
| **Root or sudo access** | Install/configure Nginx + PHP-FPM + MySQL; manage `crontab` |
| **Working `crontab`** | `* * * * * php artisan schedule:run` runs the scheduler every minute (see §5) |
| **Always-on VM** | The scheduler and web site must never sleep/park (excludes most cheap shared plans) |
| **Static/public IP + TLS** | HTTPS endpoint for `cms.krc.co.ke`; Let's Encrypt or enterprise cert |
| **SMTP egress (port 587)** | Email notifications deliver via SMTP (§8.5 of `SYSTEM_DOCUMENTATION.md`) |

### 2.3 Software stack requirements

| Component | Version | Notes |
|---|---|---|
| **Operating System** | Ubuntu 22.04/24.04 LTS (or equivalent) | Any modern Linux |
| **Scheduler** | **cron daemon** (preinstalled on Ubuntu; e.g. `cron`/`cronie`) | Runs `php artisan schedule:run` every minute (§5). Verify: `systemctl status cron` and `crontab -l` |
| **Web server** | Nginx (recommended) or Apache 2.4 | PHP-FPM with Nginx is preferred |
| **PHP** | **8.4** (min 8.2) — see `composer.json` | CLI + FPM |
| **PHP extensions** | `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `redis` (optional), `intl` (optional) | Confirm `php -m` |
| **Database** | **MySQL 8.0+** | InnoDB storage engine |
| **SSL/TLS** | TLS 1.2+ certificate | Let's Encrypt or enterprise cert |
| **Node.js** | 20+ (build assets **only** — not required at runtime) | `npm run build` happens in CI/build step |

### 2.4 Non-obligatory at runtime

- **Node.js / npm** — only needed to build frontend assets before deployment, not on the production server.
- **Redis** — optional; file-based session/cache is configured by default and is sufficient at this scale.
- **Composer** — needed to install dependencies at deploy time; can be removed/restricted after (`composer install --no-dev`).

---

## 3. Pre-deployment checklist

Before deploying, verify on a staging/local copy:

- [ ] `composer install --no-dev --optimize-autoloader` succeeds.
- [ ] `npm run build` produces `public/build/manifest.json` (present).
- [ ] `php artisan migrate --force` runs against the target DB.
- [ ] `.env` uses production values (`APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`).
- [ ] `APP_KEY` is generated (`php artisan key:generate`) and is **not** the default.
- [ ] Maintenance credentials and break-glass credentials are rotated to strong production values.
- [ ] Scheduled tasks run via **system cron** (§5): the `* * * * * ... schedule:run` line is in `crontab` and `php artisan schedule:list` shows all 4 jobs.
- [ ] `CRON_TOKEN` is rotated/strong **only if** using the shared-hosting webhook fallback (§5); otherwise it may be left unset.
- [ ] `php artisan storage:link` has been created (if any file uploads are used).

---

## 4. Step-by-step deployment (Nginx + PHP-FPM + MySQL)

> These steps assume an Ubuntu server with root/sudo access. Adjust package names for your distro.

### 4.1 Install system packages

```bash
sudo apt update
sudo apt install -y nginx cron php8.4-fpm php8.4-cli php8.4-mysql \
  php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip php8.4-bcmath \
  php8.4-gd php8.4-intl mysql-server composer unzip git
```

### 4.2 Create the application user and deploy code

```bash
sudo useradd -r -s /bin/bash -d /var/www/kr-crew-system krcrew
sudo mkdir -p /var/www/kr-crew-system
sudo chown krcrew:www-data /var/www/kr-crew-system

# Deploy code (from your CI/release artifact or git)
cd /var/www/kr-crew-system
sudo -u krcrew git pull origin main   # or copy release artifact here
```

### 4.3 Configure the environment

```bash
cd /var/www/kr-crew-system
sudo -u krcrew cp .env.example .env
sudo -u krcrew php artisan key:generate
# Edit .env with production values (see §6 mail and §7 break-glass below)
```

**Key `.env` values for production:**

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://cms.krc.co.ke
APP_KEY=<generated>

SESSION_DRIVER=file
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=cms
DB_USERNAME=<app-user>
DB_PASSWORD=<strong-password>

# Email notifications (see SYSTEM_DOCUMENTATION.md §8.5)
MAIL_MAILER=smtp
MAIL_HOST=smtp.office365.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=<smtp-account>
MAIL_PASSWORD=<smtp-password>
MAIL_FROM_ADDRESS=crew-system@krc.co.ke
MAIL_FROM_NAME="KR Crew System"
NOTIFICATIONS_EMAIL_ENABLED=true
ALERT_EMAIL_RECIPIENTS=ict-security@krc.co.ke,ict-helpdesk@krc.co.ke
```

### 4.4 Set up the database (MySQL 8)

```bash
sudo mysql <<'SQL'
CREATE DATABASE cms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'krcrew'@'localhost' IDENTIFIED BY '<strong-password>';
GRANT ALL PRIVILEGES ON cms.* TO 'krcrew'@'localhost';
FLUSH PRIVILEGES;
SQL
```

### 4.5 Install app dependencies & build assets

```bash
cd /var/www/kr-crew-system
sudo -u krcrew composer install --no-dev --optimize-autoloader --no-interaction

# Build assets (must be done on a build machine or here with Node available)
# (recommended: build in CI and ship public/build/) — else:
npm ci && npm run build
```

### 4.6 File permissions & storage link

```bash
sudo chown -R krcrew:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
sudo -u krcrew php artisan storage:link
```

### 4.7 Run migrations & seed

```bash
cd /var/www/kr-crew-system
sudo -u krcrew php artisan migrate --force
# The default superadmin and roles are auto-seeded by AppServiceProvider.
# Immediately rotate the seeded superadmin password.
```

### 4.8 Configure Nginx

Create `/etc/nginx/sites-available/kr-crew-system`:

```nginx
server {
    listen 80;
    server_name cms.krc.co.ke;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name cms.krc.co.ke;

    ssl_certificate     /etc/ssl/certs/cms.krc.co.ke.pem;
    ssl_certificate_key /etc/ssl/private/cms.krc.co.ke.key;
    ssl_protocols TLSv1.2 TLSv1.3;

    root /var/www/kr-crew-system/public;
    index index.php;

    charset utf-8;
    client_max_body_size 25M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }

    location ~ /\.(?!well-known).* { deny all; }

    location ~* \.(?:css|js|jpg|jpeg|png|svg|gif|ico|webp|woff2?)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
```

Enable and reload:

```bash
sudo ln -s /etc/nginx/sites-available/kr-crew-system /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

### 4.9 Configure PHP-FPM pool (tuned for ~20 concurrent users)

Edit `/etc/php/8.4/fpm/pool.d/www.conf`:

```ini
pm = dynamic
pm.max_children = 16
pm.start_servers = 4
pm.min_spare_servers = 2
pm.max_spare_servers = 8
pm.max_requests = 500

php_admin_value[memory_limit] = 512M
php_admin_value[max_execution_time] = 120
php_admin_value[upload_max_filesize] = 25M
php_admin_value[post_max_size] = 28M
```

Tune MySQL for 16 GB RAM (use ~50–60% of system RAM; e.g. 8 G on 16 GB, 16 G on 32 GB — `/etc/mysql/mysql.conf.d/mysqld.cnf`):

```ini
[mysqld]
innodb_buffer_pool_size = 8G
innodb_log_file_size = 256M
max_connections = 200
```

```bash
sudo systemctl restart php8.4-fpm mysql
```

### 4.10 Cache config / routes / views

```bash
cd /var/www/kr-crew-system
sudo -u krcrew php artisan optimize
```

> Re-run `php artisan optimize` after every deploy. Use `php artisan optimize:clear` before if old caches exist.

---

## 5. Scheduled tasks (critical)

The app relies on scheduled jobs that **must** run. On the target deployment (VPS/dedicated with `crontab` access) use **system cron with the Laravel scheduler** — this is the recommended, primary option:

```bash
# Add to crontab (sudo crontab -e)
* * * * * cd /var/www/kr-crew-system && php artisan schedule:run >> /dev/null 2>&1
```

> **Verify the cron line:** PHP must be reachable as `php` from `crontab`'s shell (add an absolute path, e.g. `/usr/bin/php`, if `which php` differs). The `cd` keeps Laravel's relative paths working regardless of `HOME`. Use a **web-accessible-host crontab** (`crontab -e` logged in as the `krcrew` user, or `sudo -u krcrew crontab -e`) so `storage/` file ownership stays consistent.

The scheduler checks every minute and runs whichever of these is due:

| Command | Frequency | Purpose |
|---|---|---|
| `crew:copy-end-of-day-status` | Daily 00:01 | Copies previous day's crew status to today |
| `running-rooms:auto-checkout-rested` | Every 5 min | Auto-checkout rested crew |
| `maintenance:auto-deactivate` | Every 1 min | Auto-exit maintenance mode |
| `audit:prune --retention=365` | Daily | Enforces audit log retention (≥12 months) |

Confirm the schedule is registered before/after deploy:

```bash
php artisan schedule:list
```

> **Fallback (shared hosting only):** if the hosting platform denies `crontab` access, the token-protected webhook `/running-rooms/cron/auto-checkout?token=...` can trigger the auto-checkout job. Note this fallback only triggers auto-checkout — the daily `crew:copy-end-of-day-status` and `audit:prune` jobs must then be triggered another way (manual or a second scheduler). System cron (above) is the supported path and covers all scheduled jobs automatically.

---

## 6. Mail / email notification setup

Email delivery is a **required** part of the operational alerting chain: HQ admins and depot booking officers receive an email copy for auto-checkout notifications, and ICT Security/Helpdesk receive alert-channel emails. Delivery is best-effort — a failure is logged (`Notification email delivery failed`) and never blocks the in-app notification.

### 6.1 Prerequisites

| Prerequisite | Detail |
|---|---|
| **SMTP account** | An authenticated SMTP mailbox (recommended: a dedicated service account, e.g. `crew-system@krc.co.ke`). On KR's Microsoft 365 this is the **smtp.office365.com** endpoint |
| **Egress on port 587** | Server outbound TCP **587** (STARTTLS) must be allowed in the VPS firewall/security group |
| **SPF/DKIM/DMARC** | Publish SPF to allow the VPS IP to send on behalf of `krc.co.ke`; align DKIM per provider docs (otherwise recipient mail servers may quarantine alerts) |

### 6.2 Configure SMTP in `.env`

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.office365.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=crew-system@krc.co.ke
MAIL_PASSWORD=<smtp-account-password>

# From address shown to recipients
MAIL_FROM_ADDRESS=crew-system@krc.co.ke
MAIL_FROM_NAME="KR Crew System"

# Master switch for email copies of notifications
NOTIFICATIONS_EMAIL_ENABLED=true

# Operational / security alert recipients (comma-separated)
ALERT_EMAIL_RECIPIENTS=ops@krc.co.ke,ict@krc.co.ke
```

> **Microsoft 365 note:** use an app password or modern-auth-enabled SMTP credential.
> Enable **SMTP AUTH** for the mailbox in Exchange admin (Settings → Mail flow → SMTP AUTH) or the `Set-CASMailbox -SmtpClientAuthenticationDisabled $false` cmdlet, otherwise the send fails with `535 5.7.3 Authentication unsuccessful`.

After editing, refresh the cached config on the server:

```bash
sudo -u krcrew php artisan config:clear
sudo -u krcrew php artisan optimize
```

### 6.3 Test email delivery

Send a test message (no recipient arg uses the first `ALERT_EMAIL_RECIPIENTS` entry; give one explicitly to target a mailbox):

```bash
sudo -u krcrew php artisan notifications:test-email ictsupport@krc.co.ke
```

Expect: `Test email queued/sent to ictsupport@krc.co.ke.` — then check the mailbox (including Junk, first time only).

**Troubleshooting** (`storage/logs/laravel.log`):

| Symptom | Likely cause | Fix |
|---|---|---|
| `Connection could not be established … 535` | SMTP AUTH disabled / wrong password | Enable SMTP AUTH; verify the mailbox credential |
| `Connection … timed out` | Egress 587 blocked | Open port 587 in firewall/security group |
| No email but no error | `NOTIFICATIONS_EMAIL_ENABLED=false` | Set to `true`, `config:clear` |
| Email not delivered in-app banner | In-app row still created (best-effort) | Fix SMTP; in-app notifications unaffected |
| Recipient never receives | SPF/DKIM missing | Add SPF record; check server IP ranges |

### 6.4 Verification checklist (email)

- [ ] `NOTIFICATIONS_EMAIL_ENABLED=true` and SMTP credentials populated in `.env`.
- [ ] Outbound TCP 587 allowed from the VPS.
- [ ] `php artisan notifications:test-email <mailbox>` delivers.
- [ ] Auto-checkout run (or `running-rooms:auto-checkout-rested`) produces an email; confirmed via `laravel.log` + mailbox.
- [ ] `ALERT_EMAIL_RECIPIENTS` points to live ICT addresses.
- [ ] SPF record published so KR mail servers accept sender.

---

## 7. Break-glass & emergency access setup

Break-glass credentials are **disabled by default**. When required:

1. Set **production** `.env`:

```env
BREAK_GLASS_ENABLED=true
BREAK_GLASS_USERNAME=kr-emergency
BREAK_GLASS_PASSWORD=<strong-generated-value>
BREAK_GLASS_LOGIN_AS=superadmin
BREAK_GLASS_SESSION_HOURS=8
BREAK_GLASS_REQUIRE_JUSTIFICATION=true
```

2. Generate a passphrase + optionally write it back to `.env`:

```bash
php artisan break-glass:rotate --update-env
```

3. Store the passphrase in the **enterprise vault + sealed envelope**; never in source.

> Break-glass is intended for **SSO/local-login outages**. Every attempt (success and failure) is written to `audit_logs`. Rotate credentials quarterly per Section 9.10 of the system documentation.

### 7.1 Maintenance account setup & rotation

The maintenance account is the **only** credential that can pass the maintenance gate (`/maintenance-login` and the control page `/maintenance`). Set **production** `.env`:

```env
MAINTENANCE_USERNAME=site@maintenance.com
MAINTENANCE_LOGIN=sitemaintenance
MAINTENANCE_PASSWORD=<strong-generated-value>
MAINTENANCE_LOGIN_AS=superadmin
MAINTENANCE_LOCKDOWN=true
MAINTENANCE_TIMEZONE=Africa/Nairobi
```

`MAINTENANCE_LOCKDOWN=true` (default) means **activating maintenance signs out every session across the system** (file + DB session store, and "remember me" tokens) and removes the operator's bypass cookie — nobody stays logged in. The only way back in is `/maintenance-login` with the maintenance credentials (which signs in as `MAINTENANCE_LOGIN_AS` and re-issues the bypass cookie). While maintenance is active, a `System Under Maintenance` banner stays visible on every portal page for the operator; scheduled end times are anchored to `MAINTENANCE_TIMEZONE` (default `Africa/Nairobi`).

Generate a passphrase and write it back to `.env` (mirrors `break-glass:rotate`):

```bash
php artisan maintenance:rotate --update-env
```

- Rotate the maintenance passphrase **quarterly with the break-glass passphrase**.
- The maintenance sign-in operates as `MAINTENANCE_LOGIN_AS` (default `superadmin`) once the maintenance credentials are accepted.
- All maintenance activity (login, activate, deactivate, credential rotation) is written to `audit_logs`.

---

## 8. Post-deployment verification

```bash
# 1. App responds
curl -I https://cms.krc.co.ke

# 2. Login page loads (expect 200)
curl -s -o /dev/null -w "%{http_code}\n" https://cms.krc.co.ke/login

# 3. Admin panel login loads
curl -s -o /dev/null -w "%{http_code}\n" https://cms.krc.co.ke/admin/login

# 4. Migrations are all applied
php artisan migrate:status | grep -c "Ran"

# 5. Scheduled tasks can run
php artisan schedule:list

# 6. Audit logging works (creates a system audit row)
php artisan break-glass:rotate --no-audit > /dev/null  # or
php artisan tinker --execute="\App\Services\AuditLogger::record('deploy','deploy_check','boot');"

# 7. Email notifications deliver (sends a test email via SMTP)
php artisan notifications:test-email ictsupport@krc.co.ke
```

---

## 9. Post-deployment security checklist

- [ ] `APP_DEBUG=false` and `APP_ENV=production`.
- [ ] `SESSION_SECURE_COOKIE=true` (HTTPS only).
- [ ] TLS 1.2+ enforced; HSTS header added in Nginx (suggested: `add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;`).
- [ ] Default seeded `superadmin` password changed.
- [ ] Maintenance credentials rotated from defaults (`CRON_TOKEN` only if using the webhook fallback).
- [ ] Break-glass disabled (`BREAK_GLASS_ENABLED=false`) unless an outage.
- [ ] `.env` is **excluded** from version control and not web-accessible.
- [ ] `storage/` and `bootstrap/cache/` not web-accessible.
- [ ] SMTP credentials configured and a test email delivered (`php artisan notifications:test-email`).
- [ ] Daily **database backup** scheduled (retained ≥30 days; see `SYSTEM_DOCUMENTATION.md` §7.5).
- [ ] Audit-log prune verified (`php artisan audit:prune --retention=365`).
- [ ] File/dir permissions: web server can only write `storage/`, `bootstrap/cache/`.

---

## 10. Rollback

1. Revert code to the previous release (git tag or previous artifact).
2. `composer install --no-dev --optimize-autoloader`.
3. If this release added migrations: `php artisan migrate:rollback` (only roll back **your own** reversible migrations; otherwise restore the DB from backup).
4. `php artisan optimize`.
5. Verify login + dashboard.

---

## 11. Recommended backup strategy (~20 concurrent users)

| Item | Frequency | Retention |
|---|---|---|
| MySQL dump (`mysqldump cms`) | Daily | 30 days |
| `.env` + `storage/` uploads | Daily | 30 days |
| Off-site copy | Weekly | 3 months |

```bash
# example cron:
0 2 * * * mysqldump --single-transaction -u krcrew -p'...' cms | gzip > /backups/cms-$(date +%F).sql.gz
```

---

## 11.1 Disaster recovery & MySQL replication

Production data is replicated to a **DR server** (read-only MySQL replica) and its
restore-ability is proven by a **semi-annual DR drill** (RPO ≤ 5 min, RTO ≤ 1 h).

- Source (Production): `scripts/mysql/enable-mysql-source.sh`
- Replica (DR): `scripts/mysql/setup-mysql-replica.sh --source-host <PROD_IP>`
- Drill (DR): `scripts/mysql/dr-drill.sh --db cms` (exit 0 = PASS)

Full failover/fallback runbook: **`docs/disaster-recovery.md`**.

---

## 12. Reference

- System documentation: `docs/SYSTEM_DOCUMENTATION.md`
- Scheduled tasks: `docs/scheduled-tasks.md`
- Disaster recovery: `docs/disaster-recovery.md`
- Composer config: `composer.json` (PHP ^8.2, Laravel ^11, Filament ^4)
