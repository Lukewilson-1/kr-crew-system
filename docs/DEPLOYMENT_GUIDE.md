# KR Crew System — Deployment Guide

> **Applies to:** KR Crew System (CM & RM System) · Laravel 11 / PHP 8.4 / MySQL 8 · `cms.krc.co.ke`
> **Version:** 1.0 · **Date:** 2026-09-09 · **Owner:** Kenya Railways Corporation – ICT Division

---

## 1. Purpose & Scope

This guide covers the deployment of the KR Crew System to an enterprise production server. It specifies:

- **Minimum / recommended server requirements** sized for **~20 concurrent users**.
- The supported software stack (PHP, MySQL, web server).
- Step-by-step deployment, cache/link setup, and scheduled-task configuration.
- First-boot verification and post-deployment security checklist.

**Out of scope:** code development workflow, feature details, and the SSO rollout (see `SYSTEM_DOCUMENTATION.md` Appendix F).

---

## 2. Deployment Server Requirements (sized for ~20 concurrent users)

The application is a Laravel 11 + Filament 4 app with file-based sessions/cache, a vanilla-JS SPA, and a MySQL database. It is **I/O-light on the web tier** and **database-bound** on report generation. The figures below assume ~20 simultaneous users (peak daily-status entry + running-room ops + monthly register generation).

### 2.1 RMinimum (acceptable) specification

| Resource | Requirement | Notes |
|---|---|---|
| **CPU** | 2 vCPU (≥2.0 GHz) | Report generation (monthly register) is the heaviest operation |
| **Memory (RAM)** | **16 GB minimum / 32 GB recommended** | PHP-FPM pool + MySQL buffer pool; the biggest bottleneck for concurrent report generation |
| **Storage** | 40GB SSD (OS) + 100 GB for database/logs | SSD strongly recommended for MySQL + sessions |
| **Swap** | 10 GB swap | Safety under transient spikes |


> **Verdict for ~20 concurrent users:** A **2 vCPU / 16 GB RAM** cloud VM is the recommended target. This comfortably handles the workload and leaves headroom for report generation and backups.

### 2.3 Software stack requirements

| Component | Version | Notes |
|---|---|---|
| **Operating System** | Ubuntu 22.04/24.04 LTS (or equivalent) | Any modern Linux |
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
- [ ] Maintenance credentials, `CRON_TOKEN`, and break-glass credentials are rotated to strong production values.
- [ ] `php artisan storage:link` has been created (if any file uploads are used).

---

## 4. Step-by-step deployment (Nginx + PHP-FPM + MySQL)

> These steps assume an Ubuntu server with root/sudo access. Adjust package names for your distro.

### 4.1 Install system packages

```bash
sudo apt update
sudo apt install -y nginx php8.4-fpm php8.4-cli php8.4-mysql \
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
# Edit .env with production values (see §6 below)
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

Tune MySQL for 4 GB RAM (`/etc/mysql/mysql.conf.d/mysqld.cnf`):

```ini
[mysqld]
innodb_buffer_pool_size = 1G
innodb_log_file_size = 128M
max_connections = 100
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

The app relies on scheduled jobs that **must** run even though shared hosting lacks a native scheduler. On a dedicated server, use the Laravel scheduler:

```bash
# Add to crontab (sudo crontab -e)
* * * * * cd /var/www/kr-crew-system && php artisan schedule:run >> /dev/null 2>&1
```

The scheduler then runs (every minute check):

| Command | Frequency | Purpose |
|---|---|---|
| `crew:copy-end-of-day-status` | Daily 00:01 | Copies previous day's crew status to today |
| `running-rooms:auto-checkout-rested` | Every 5 min | Auto-checkout rested crew |
| `maintenance:auto-deactivate` | Every 1 min | Auto-exit maintenance mode |
| `audit:prune --retention=365` | Daily | Enforces audit log retention (≥12 months) |

> **Alternative (if shared hosting):** keep using the cron-job.org webhook (`/running-rooms/cron/auto-checkout?token=...`). You must separately trigger the daily/audit commands. On a dedicated server the Laravel scheduler above is the recommended path.

---

## 6. Break-glass & emergency access setup

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

---

## 7. Post-deployment verification

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
```

---

## 8. Post-deployment security checklist

- [ ] `APP_DEBUG=false` and `APP_ENV=production`.
- [ ] `SESSION_SECURE_COOKIE=true` (HTTPS only).
- [ ] TLS 1.2+ enforced; HSTS header added in Nginx (suggested: `add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;`).
- [ ] Default seeded `superadmin` password changed.
- [ ] Maintenance credentials + `CRON_TOKEN` rotated from defaults.
- [ ] Break-glass disabled (`BREAK_GLASS_ENABLED=false`) unless an outage.
- [ ] `.env` is **excluded** from version control and not web-accessible.
- [ ] `storage/` and `bootstrap/cache/` not web-accessible.
- [ ] Daily **database backup** scheduled (retained ≥30 days; see `SYSTEM_DOCUMENTATION.md` §7.5).
- [ ] Audit-log prune verified (`php artisan audit:prune --retention=365`).
- [ ] File/dir permissions: web server can only write `storage/`, `bootstrap/cache/`.

---

## 9. Rollback

1. Revert code to the previous release (git tag or previous artifact).
2. `composer install --no-dev --optimize-autoloader`.
3. If this release added migrations: `php artisan migrate:rollback` (only roll back **your own** reversible migrations; otherwise restore the DB from backup).
4. `php artisan optimize`.
5. Verify login + dashboard.

---

## 10. Recommended backup strategy (~20 concurrent users)

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

## 11. Reference

- System documentation: `docs/SYSTEM_DOCUMENTATION.md`
- Scheduled tasks: `docs/scheduled-tasks-cron.md`
- Composer config: `composer.json` (PHP ^8.2, Laravel ^11, Filament ^4)
