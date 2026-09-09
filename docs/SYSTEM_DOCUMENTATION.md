# System Documentation – KR Crew System (CM & RM System)

---

## 1. Document Control

| Field | Value |
|---|---|
| **Document title** | System Documentation – KR Crew System |
| **System name / ID** | KR Crew System (CM & RM System) · `cms.krc.co.ke` |
| **Owner (product / engineering)** | Kenya Railways Corporation – ICT Division |
| **Primary authors** | Engineering Team |
| **Version** | 1.4 |
| **Last updated** | 2026-09-09 |
| **Status** | Draft |

### Change History

| Version | Date | Author | Description |
|---|---|---|---|
| v1.0 | 2026-09-09 | Engineering Team | Initial version |
| v1.1 | 2026-09-09 | Engineering Team | Added audit logging implementation (§8.3), closed PII gap (§8.1, §9.7.3), appended AD/Entra SSO implementation plan (Appendix F) |
| v1.2 | 2026-09-09 | Engineering Team | Implemented break-glass & emergency access (§9.10, `config/breakglass.php`); fixed model namespace inconsistencies; added deployment guide (`docs/DEPLOYMENT_GUIDE.md`); confirmed `users` table stores no `hire_date` |
| v1.3 | 2026-09-09 | Engineering Team | Added Version 2 Features roadmap (§10): AD/Entra SSO, SMS notifications, driver schedules for trains, automated status changes, rest/leave notifications, and training-gap analysis; renumbered Glossary (§11) and References (§12) |
| v1.4 | 2026-09-09 | Engineering Team | Implemented email notification service (§8.5): `NotificationService` (multi-channel in-app + email), `SystemNotificationMail` + KR-branded template, `config/notifications.php`, `email_delivered_at` tracking, `notifications:test-email` verification command; auto-checkout notifications now email HQ/booking officers |

---

## 2. Introduction

### 2.1 Purpose of this Document

This document provides a comprehensive description of the **KR Crew System** (also referred to as the **CM & RM System** — Crew Management & Running Room Management System). It is intended for internal enterprise users (Kenya Railways operations staff), internal developers, IT operations. It covers how the system works, how to use it, how it is built and maintained, and the security controls that protect it.

### 2.2 System Overview

**What it is:** A web application for managing daily crew operations and running-room facility management for Kenya Railways Corporation. It consists of two integrated modules:

- **Crew Management (CM):** Tracks daily crew status (BK/SB/R/SK/ABS/T/NTB/TO), manages depots, regions, and designations, produces monthly registers, manages rosters, and enforces rest-eligibility rules.
- **Running Room Management (RM):** Manages room and bed occupancy with check-in/checkout workflows, tracks matters/issues as ticketed items (`MTR-YYYY-####`), manages rest locations and duty rosters, and supports automated checkout via scheduled cron jobs.

**Primary users:** Internal Kenya Railways staff — Station Officers, Booking Officers, Crew Admins, HQ Admins, and Super Admins across operational depots, regions, and headquarters.

**Business value:** Replaces manual crew-status tracking and paper-based running-room registers with a centralized digital system, reducing data-entry errors, accelerating reporting cycles, ensuring compliance with rest-period regulations, and providing management with real-time operational visibility.

### 2.3 Scope

**In scope:**

- Crew daily-status recording, editing (within configurable day window), and aggregation.
- Crew status segments with time-based tracking and composite indexing.
- Depot, designation, region, status-code, train-type, and shift-template management.
- Running-room room/bed management, occupancy tracking, check-in/checkout.
- Matters/issues ticketing system with photo attachments.
- Rest-location and duty-roster management.
- Rest-eligibility calculation and enforcement.
- Monthly register, daily-status reports, utilization reports, absence reports.
- RBAC with seeded roles and permissions.
- Maintenance mode with separate admin login.
- Automated end-of-day status copy and auto-checkout via scheduled jobs.

**Out of scope:**

- Mobile-native applications (the frontend is browser-based SPA).

### 2.4 Assumptions & Constraints

**Assumptions:**

- Users access the system via modern browsers (Chrome, Edge, Firefox) on the Kenya Railways internal network or approved VPN.
- Corporate identity infrastructure (Active Directory) is available for future SSO integration.
- The Microsoft shared hosting environment provides PHP runtime and MySQL database access.
- Scheduled tasks rely on an external cron-job.org webhook due to the absence of a native server scheduler.

**Constraints:**

- Hosting on Microsoft shared infrastructure limits server-level customization (no native cron, no Docker).
- PHP 8.4 runtime with MySQL 8.x database.
- No CI/CD pipeline — deployments are manual via Composer and Artisan.
- No automated test suite currently in place.
- Legacy password-hash bridge (`pw` column) must be maintained during transition period.

---

## 3. Business Context

### 3.1 Business Objectives

| Objective | Success Metric |
|---|---|
| Eliminate manual crew-status tracking | 100% of daily crew status recorded digitally |
| Ensure rest-period compliance | Zero rest-period violations reaching operations |
| Reduce report-generation time | Monthly register produced in < 5 minutes (vs. hours manually) |
| Improve running-room utilization visibility | Real-time bed occupancy dashboard available |
| Centralize issue tracking for running rooms | All matters logged as traceable tickets with SLA tracking |

### 3.2 Stakeholders

| Role | Responsibility |
|---|---|
| **Business Owner** | Kenya Railways Corporation — Operations Directorate |
| **Product Owner** | ICT Division / Operations Planning |
| **Primary User Groups** | Station Officers, Booking Officers, Crew Admins, HQ Admins |
| **Support / Operations** | ICT Helpdesk, System Administrators |
| **Security / Compliance** | ICT Security, Internal Audit |

### 3.3 Related Systems & Dependencies

| System | Relationship |
|---|---|
| **Active Directory / Azure AD** | Potential future identity provider for SSO |
| **MySQL Database (`cms`)** | Primary data store, hosted on Microsoft infrastructure |
| **cron-job.org** | External scheduler for automated tasks (auto-checkout, end-of-day status copy, maintenance deactivation) |
| **Filament Admin Panel** | Admin UI framework providing CRUD, RBAC, and dashboard widgets |
| **Filament Apex Charts** | Visualization library for operational dashboards |
| **Laravel Echo / Pusher** | Real-time event broadcasting (configured, usage TBD) |

---

## 4. User Guide (for Internal Enterprise Users)

### 4.1 Getting Started

| Item | Detail |
|---|---|
| **Access URL** | `https://cms.krc.co.ke` |
| **Supported browsers** | Google Chrome (latest), Microsoft Edge (latest), Mozilla Firefox (latest) |
| **How to request access** | Submit a request to the ICT Helpdesk specifying: full name, employee ID, depot/region, and required role. Access is provisioned by a Super Admin or HQ Admin. |
| **Login method** | Username or email + password. MFA is not yet enforced at the application level. |

### 4.2 First-Time Orientation

Upon logging in, users are presented with a dashboard customized to their role:

- **Top navigation bar:** Application branding ("Kenya Railways Corporation"), user profile dropdown (logout, settings), and any system notifications.
- **Side menu:** Module navigation — Crew Management, Running Rooms, Reports, and administration options (role-dependent).
- **Dashboard widgets:** Key operational metrics (crew status summary, room occupancy, pending matters) are displayed as charts and summary cards.
- **Help & support:** The ICT Helpdesk can be contacted via the internal support channel. System notifications (bell icon) display important alerts.

### 4.3 Core Workflows

#### 4.3.1 Record Daily Crew Status

| Field | Detail |
|---|---|
| **Goal** | Record the duty status of each crew member for a given day |
| **Who can perform it** | Booking Officer, Crew Admin, HQ Admin, Super Admin |
| **Steps** | 1. Navigate to **Crew Management > Daily Status**. 2. Select the date (defaults to today). 3. Select the depot. 4. For each crew member, select a status code (BK, SB, R, SK, ABS, T, NTB, TO) from the dropdown. 5. Enter time segments if applicable (departure/arrival times). 6. Click **Save**. |
| **Expected outcome** | Crew status records are saved and reflected in reports. Status segments are indexed for time-based queries. |

#### 4.3.2 Check In / Check Out (Running Room)

| Field | Detail |
|---|---|
| **Goal** | Track crew occupancy in running-room beds |
| **Who can perform it** | Station Officer, Booking Officer, Running Room Admin |
| **Steps (Check-in)** | 1. Navigate to **Running Rooms**. 2. Select a room and available bed. 3. Click **Check In**. 4. Enter crew member details (or search by name/ID). 5. Confirm check-in. |
| **Steps (Check-out)** | 1. Navigate to **Running Rooms > Occupancy**. 2. Select the occupied bed. 3. Click **Check Out**. 4. Confirm. (Note: Auto-checkout runs via cron if not manually checked out.) |
| **Expected outcome** | Bed occupancy is updated; check-in/check-out timestamps are recorded. |

#### 4.3.3 Log a Matter / Issue (Running Room)

| Field | Detail |
|---|---|
| **Goal** | Record and track issues in running rooms (maintenance, cleanliness, etc.) |
| **Who can perform it** | Any running-room user |
| **Steps** | 1. Navigate to **Running Rooms > Matters**. 2. Click **New Matter**. 3. Enter description (rich text supported), severity, and room reference. 4. Optionally attach photos. 5. Submit. |
| **Expected outcome** | A ticket is created with reference `MTR-YYYY-####`, visible in the matters dashboard. |

#### 4.3.4 Generate Reports

| Field | Detail |
|---|---|
| **Goal** | Produce operational reports (daily status, monthly register, utilization, absence) |
| **Who can perform it** | Crew Admin, HQ Admin, Super Admin |
| **Steps** | 1. Navigate to **Reports**. 2. Select report type (Daily Status, Monthly Register, Utilization, Absence). 3. Set date range and filters (depot, region). 4. Click **Generate**. 5. Export as needed (printable view available). |
| **Expected outcome** | Report is rendered and available for print or export. |

### 4.4 Permissions & Roles

| Role | Capabilities |
|---|---|
| **Super Admin** | Full system access. Bypasses all authorization gates. Manages users, roles, permissions, and all modules. |
| **HQ Admin** | Access to all depots/regions at headquarters level. Manages crew records, reports, rosters, and running-room configuration. |
| **Crew Admin** | Manages crew records, depots, designations, rosters, and crew-related reports. Cannot manage users or roles. |
| **Station Officer** | Operational access at station level. Manages running-room check-in/checkout, bed assignments, and matter logging. |
| **Booking Officer** | Records daily crew status and manages running-room operations at the booking office level. |

**Role change requests:** Must be approved by a Super Admin. Changes are recorded in the user's profile and take effect immediately.

### 4.5 Common Tasks & How-Tos

- **Copy yesterday's status:** Use the end-of-day status copy feature to pre-populate today's roster from yesterday's records.
- **View rest eligibility:** Check a crew member's rest hours from their profile. The system calculates eligibility based on last duty time.
- **Bulk edit crew status:** Crew Admins can edit multiple crew statuses in sequence. Note the configurable edit window (`CREW_PAST_DAY_EDIT_WINDOW_DAYS=2` — status older than 2 days cannot be edited).
- **Filter reports by region:** Use the region dropdown in the report builder to narrow results.

### 4.6 Troubleshooting (User-Facing)

| Issue | Resolution |
|---|---|
| **Cannot log in** | Verify username/email. If locked out, contact ICT Helpdesk. Ensure Caps Lock is off. |
| **Status not saving** | Check if the date is within the editable window. Ensure all required fields are filled. Try refreshing the page. |
| **Report not generating** | Verify date range is valid. Ensure you have the correct role permissions. Clear browser cache and retry. |
| **Running room bed not available** | Check if the bed is occupied. Verify check-out was completed. Contact a Station Officer. |
| **"Maintenance Mode" displayed** | The system is under maintenance. Use the maintenance login if you are an authorized admin. Otherwise, wait for restoration and contact ICT. |

**How to contact support:** Reach out to the ICT Helpdesk via the internal support channel. Include your username, the page you were on, and the exact error message (screenshot if possible).

---

## 5. Developer Guide

### 5.1 Technical Overview

The KR Crew System is a **Laravel 11** monolithic web application with a **Filament 4** admin panel providing the UI layer, and a supplementary vanilla JavaScript SPA in `public/js/` for specific operational views. The backend is PHP 8.4, the database is MySQL, and the frontend uses Tailwind CSS with Alpine.js for interactivity.

**Architecture diagram reference:** _(To be created — see Section 6.1)_

### 5.2 Technology Stack

| Layer | Technology |
|---|---|
| **Frontend (Admin)** | Filament 4 (Livewire/Alpine.js), Tailwind CSS, ApexCharts |
| **Frontend (SPA)** | Vanilla JS (ES modules), Alpine.js, Axios, jQuery, Lodash |
| **Backend** | PHP 8.4, Laravel 11 |
| **Database** | MySQL 8.x (`cms` database) |
| **Real-time** | Laravel Echo + Pusher (configured) |
| **Rich text** | `tonysm/rich-text-laravel` (for matter descriptions) |
| **Charts** | `leandrocfe/filament-apex-charts` |
| **Build tool** | Vite |
| **Session / Cache** | File-based driver |

### 5.3 Repository & Project Structure

**Repository:** _(Internal repository URL — to be filled)_

**Branching strategy:** _(To be documented — currently manual deployment)_

**High-level folder structure:**

```
kr-crew-system/
├── app/
│   ├── Console/
│   │   ├── Commands/          # Artisan commands (copy-end-of-day-status, etc.)
│   │   └── Kernel.php         # Scheduling configuration
│   ├── Exceptions/
│   ├── Filament/
│   │   ├── Pages/             # Custom Filament pages
│   │   ├── Resources/         # Filament CRUD resources (CrewMemberResource, RoomResource, etc.)
│   │   └── Widgets/           # Dashboard widgets (charts, stats)
│   ├── Http/
│   │   ├── Controllers/       # Web controllers + hand-rolled JSON API endpoints
│   │   └── Middleware/
│   ├── Models/                # Eloquent models (CrewRecord, Room, Matter, etc.)
│   ├── Policies/              # Authorization policies
│   ├── Services/              # Business logic services (CrewStatusService, etc.)
│   └── Support/               # Helper classes and traits
├── config/
│   ├── crew.php               # Crew-specific configuration
│   ├── maintenance.php        # Maintenance mode configuration
│   └── auth.php               # Authentication configuration
├── database/
│   └── migrations/            # ~37 migration files
├── docs/                      # Documentation
├── public/
│   ├── js/                    # Vanilla JS SPA
│   ├── assets/                # Branding assets (logo, CSS)
│   └── filament/              # Published Filament assets
├── resources/views/           # Blade templates (crew/shell, running_rooms, hub)
├── routes/
│   ├── web.php                # Web routes
│   └── auth.php               # Authentication routes
├── .env.example               # Environment template
├── composer.json
├── README.md
└── vite.config.js
```

### 5.4 Local Development Setup

**Prerequisites:**

- PHP 8.4 (path on dev: `C:\tools\php84\php.exe`)
- Composer (latest)
- MySQL 8.x
- Node.js (for Vite asset compilation)
- Git

**Steps:**

```bash
# 1. Clone the repository
git clone <repository-url>
cd kr-crew-system

# 2. Install PHP dependencies
composer install

# 3. Configure environment
cp .env.example .env
php artisan key:generate
# Edit .env with local database credentials and settings

# 4. Create the database
mysql -u root -e "CREATE DATABASE cms;"

# 5. Run migrations
php artisan migrate

# 6. Seed roles and permissions (runs automatically via AppServiceProvider)

# 7. Install Node.js dependencies and build assets
npm install
npm run build

# 8. Start the development server
php artisan serve
```

**Running the app locally:** Access at `http://localhost:8000`. Login with seeded admin credentials.

**Running tests:** _(No test suite currently exists. See Section 5.10.)_

### 5.5 Build, CI/CD & Deployment

**Build commands:**

```bash
composer install --no-dev --optimize-autoloader
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**CI/CD:** _(No CI/CD pipeline currently in place. Deployment is manual.)_

**Deployment process (current):**

1. SSH to production server or access file system.
2. Pull latest code from repository.
3. Run `composer install --no-dev`.
4. Run `php artisan migrate --force`.
5. Clear and re-cache config/routes/views.
6. Verify application health at `https://cms.krc.co.ke`.

**Rollback procedure:**

1. Revert to previous commit/tag in the repository.
2. Run `composer install --no-dev`.
3. Run `php artisan migrate:rollback` if schema changes were applied.
4. Clear caches.

### 5.6 Configuration

**Key configuration files:**

| File | Purpose |
|---|---|
| `.env` | Environment-specific settings (DB credentials, app keys, feature flags) |
| `config/crew.php` | Crew-specific business rules (edit window days, status codes) |
| `config/maintenance.php` | Maintenance mode credentials and behavior |
| `config/auth.php` | Authentication providers, password reset settings |
| `config/database.php` | Database connection configuration |
| `config/services.php` | Third-party service defaults (superadmin defaults) |

**Key environment variables:**

| Variable | Description |
|---|---|
| `APP_NAME` | Application display name (`CM & RM System`) |
| `APP_URL` | Application base URL (`https://cms.krc.co.ke`) |
| `DB_DATABASE` | MySQL database name (`cms`) |
| `CREW_PAST_DAY_EDIT_WINDOW_DAYS` | Number of days crew status remains editable (default: 2) |
| `CRON_TOKEN` | Secret token for cron webhook authentication |
| `MAINTENANCE_USERNAME` | Maintenance mode admin username |
| `MAINTENANCE_PASSWORD` | Maintenance mode admin password |

### 5.7 API & Integration Details

**Internal JSON API endpoints (hand-rolled, under `auth` middleware with `throttle:120,1`):**

| Endpoint | Purpose |
|---|---|
| `POST /mysql/login` | Sessionless JSON authentication |
| `GET /admin/meta/*` | Admin metadata (legacy compatibility) |
| `GET /mysql/meta/*` | MySQL metadata endpoints |
| `GET /mysql/users/*` | User management data |
| `GET /mysql/crew-view/*` | Crew view data (legacy admin-meta compatibility) |
| `/running-rooms/api/*` | Running room data, crew search, records, checkout, matters, beds, options, notifications |
| `/reports/*` | Report builder, daily-status, monthly-register, utilization, absence, printable |

**External integrations:**

- **cron-job.org:** Scheduled webhook at `/running-rooms/cron/auto-checkout?token=...` (Job ID: 8273192, runs every 5 minutes) to trigger auto-checkout of rested crew.

**Webhooks / events:** Laravel Echo/Pusher is configured for real-time broadcasting (usage scope TBD).

### 5.8 Data Model (Developer View)

**Core entities and relationships:**

```
┌─────────────────┐     ┌──────────────────┐     ┌──────────────────┐
│   CrewMember     │────▶│   CrewRecord      │────▶│CrewStatusSegment │
│  (id, name,      │     │ (id, crew_member, │     │ (id, crew_record,│
│   staff_no,      │     │  date, depot,     │     │  status_code,    │
│   depot,         │     │  status,          │     │  start_time,     │
│   designation)   │     │  train_type)      │     │  end_time)       │
└─────────────────┘     └──────────────────┘     └──────────────────┘
         │                       │
         ▼                       ▼
┌─────────────────┐     ┌──────────────────┐     ┌──────────────────┐
│   Depot          │     │   Designation     │     │   Region         │
│  (code, name,    │     │  (code, name)     │     │  (code, name)    │
│   region)        │     │                   │     │                  │
└─────────────────┘     └──────────────────┘     └──────────────────┘

┌─────────────────┐     ┌──────────────────┐     ┌──────────────────┐
│   Room           │────▶│   RoomBed         │────▶│AttendanceRecord  │
│  (id, name,      │     │ (id, room, bed_no,│     │ (id, bed, crew,  │
│   floor,         │     │  status)          │     │  check_in,       │
│   capacity)      │     │                   │     │  check_out)      │
└─────────────────┘     └──────────────────┘     └──────────────────┘
                                 │
                                 ▼
                         ┌──────────────────┐     ┌──────────────────┐
                         │   Matter          │────▶│   MatterPhoto     │
                         │ (id, ref MTR-*,   │     │ (id, matter,      │
                         │  room, severity,  │     │  path)            │
                         │  description,     │     │                  │
                         │  status)          │     │                  │
                         └──────────────────┘     └──────────────────┘

┌─────────────────┐     ┌──────────────────┐     ┌──────────────────┐
│   DutyRoster     │────▶│  DutyRosterItem   │     │   RestLocation   │
│  (id [UUID],     │     │ (id [UUID],       │     │  (id, name,      │
│   name, date)    │     │  roster, crew,    │     │   capacity)      │
│                  │     │  shift_template)  │     │                  │
└─────────────────┘     └──────────────────┘     └──────────────────┘

┌─────────────────┐     ┌──────────────────┐     ┌──────────────────┐
│   StatusCode     │     │   TrainType       │     │   ShiftTemplate  │
│  (code, label,   │     │  (code, name)     │     │  (id, name,      │
│   category)      │     │                   │     │   start, end)    │
└─────────────────┘     └──────────────────┘     └──────────────────┘

┌─────────────────┐     ┌──────────────────┐     ┌──────────────────┐
│   User           │────▶│   Role            │────▶│  Permission      │
│  (id, username,  │     │ (id, name,        │     │ (id, name,       │
│   email, pw,     │     │  guard)           │     │  guard)          │
│   roles)         │     │                   │     │                  │
└─────────────────┘     └──────────────────┘     └──────────────────┘

┌─────────────────┐     ┌──────────────────┐
│ ReportDefinition │     │SystemNotification│
│ (id, name, type,│     │ (id, title, body, │
│  params, filters)│    │  read_at)         │
└─────────────────┘     └──────────────────┘
```

**Schema management:** Laravel migrations in `database/migrations/`. No ORM-level schema builder beyond Eloquent conventions.

**Key business rules in the data layer:**

- `CrewStatusSegment` has composite indexes for time-range queries.
- `CREW_PAST_DAY_EDIT_WINDOW_DAYS` (configurable) prevents editing crew status beyond the allowed window.
- Rest-eligibility is calculated from `CrewStatusSegment` end times.
- `DutyRoster`/`DutyRosterItem` use UUID string primary keys.

### 5.9 Security Implementation

**Authentication flow:**

- Laravel session-based authentication with a custom `Login` page supporting email-or-username entry.
- Password verification via `User::passwordMatches()` with bcrypt hashing.
- Legacy `pw` column bridge for gradual migration from plaintext/hashed legacy passwords.
- Maintenance-mode authentication via separate `/maintenance-login` endpoint with hardcoded credentials.

**Authorization model:**

- Role-Based Access Control (RBAC) using `spatie/laravel-permission` conventions.
- Tables: `roles`, `permissions`, `role_permissions`, `model_has_roles`, `model_has_permissions`.
- Seeded roles: `super_admin`, `hq_admin`, `station_officer`, `booking_officer`, `crew_admin`.
- Seeded permissions: `manage_depots`, `manage_users`, `manage_crew`, `manage_roles`, `manage_rosters`, `manage_reports`.
- Super Admin bypass via `AuthServiceProvider` Gate override.
- Filament resource-level authorization via Policies in `app/Policies/`.

**Protections:**

- CSRF tokens on all forms (Laravel default).
- Rate limiting on API routes (`throttle:120,1`).
- Input validation via Form Requests and Filament validation rules.
- `EncryptCookies` middleware with explicit exclusion for `laravel_maintenance` cookie.

### 5.10 Testing Strategy

**Current state:** No automated test suite exists. The `/tests` directory is gitignored and absent.

**Recommended approach (for future implementation):**

| Test Type | Tool | Coverage Target |
|---|---|---|
| Unit | PHPUnit | Models, Services, Helpers |
| Integration | PHPUnit + SQLite in-memory | API endpoints, RBAC enforcement |
| End-to-End | Laravel Dusk or Playwright | Critical workflows (login, crew status, checkout) |
| Performance | Apache JMeter / k6 | Report generation, concurrent user load |

**Quality gates:** _(To be established — recommend linting via Laravel Pint and static analysis via PHPStan.)_

### 5.11 Coding Standards & Conventions

- **Language style:** PSR-12 for PHP; ES modules for JavaScript.
- **Linting/Formatting:** Laravel Pint (PHP), Tailwind CSS conventions.
- **Naming:** Models use singular PascalCase (`CrewMember`); migrations use snake_case (`create_crew_records_table`); config keys use dot notation.
- **Commit conventions:** _(To be established)_
- **PR review expectations:** _(To be established)_

---

## 6. System Architecture

### 6.1 High-Level Architecture

```
┌──────────────────────────────────────────────────────────┐
│                      BROWSER                             │
│  ┌─────────────┐  ┌──────────────┐  ┌───────────────┐  │
│  │  Filament    │  │  Vanilla JS  │  │  Blade Views  │  │
│  │  Admin Panel │  │  SPA (Alpine)│  │  (Hub/Shell)  │  │
│  └──────┬──────┘  └──────┬───────┘  └───────┬───────┘  │
└─────────┼────────────────┼──────────────────┼────────────┘
          │ HTTPS          │ HTTPS            │ HTTPS
          ▼                ▼                  ▼
┌──────────────────────────────────────────────────────────┐
│               MICROSOFT SHARED HOSTING                    │
│  ┌──────────────────────────────────────────────────┐   │
│  │           Laravel 11 Application                  │   │
│  │  ┌──────────┐  ┌───────────┐  ┌──────────────┐  │   │
│  │  │ Filament │  │ HTTP API  │  │ Console/      │  │   │
│  │  │ Resources│  │ Endpoints │  │ Commands      │  │   │
│  │  └──────────┘  └───────────┘  └──────────────┘  │   │
│  │  ┌──────────┐  ┌───────────┐  ┌──────────────┐  │   │
│  │  │ Services │  │ Policies  │  │ Middleware    │  │   │
│  │  └──────────┘  └───────────┘  └──────────────┘  │   │
│  └──────────────────────┬───────────────────────────┘   │
│                         │                                │
│                         ▼                                │
│  ┌──────────────────────────────────────────────────┐   │
│  │              MySQL 8.x Database                   │   │
│  │                    (cms)                          │   │
│  └──────────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────┘
          ▲
          │ Webhook (every 5 min)
┌─────────┴───────────────────────────────────────────────┐
│              EXTERNAL SCHEDULER                          │
│         cron-job.org (Job ID: 8273192)                   │
│         Triggers: auto-checkout, end-of-day,             │
│                   maintenance deactivation               │
└─────────────────────────────────────────────────────────┘
```

### 6.2 Components & Services

| Component | Responsibility | Technology | Key Interfaces |
|---|---|---|---|
| **Filament Admin Panel** | CRUD operations, dashboards, RBAC management | Filament 4 / Livewire | Web routes, Filament resources |
| **Crew Management Module** | Daily status tracking, roster management, rest eligibility | Laravel Services | JSON API endpoints, Filament widgets |
| **Running Room Module** | Occupancy management, check-in/checkout, matters | Laravel Services + Controllers | JSON API endpoints (`/running-rooms/api/*`) |
| **Report Engine** | Generate daily, monthly, utilization, and absence reports | Laravel Services | `/reports/*` endpoints |
| **Scheduler (External)** | Automated periodic tasks | cron-job.org → webhook | `GET /running-rooms/cron/auto-checkout?token=...` |

### 6.3 Infrastructure & Environments

| Environment | Purpose | Access |
|---|---|---|
| **Production** | Live system at `https://cms.krc.co.ke` | Kenya Railways internal network / VPN |
| **Local Development** | Developer workstations | `php artisan serve` on localhost |

**Hosting model:** Microsoft shared hosting (PHP + MySQL). No container orchestration, load balancing, or CDN currently in place.

**Key infrastructure note:** The absence of a native server scheduler necessitates the external cron-job.org integration. This is a single point of failure for automated tasks — if the webhook is blocked or the cron-job.org service is unavailable, auto-checkout and end-of-day status copy will not run.

### 6.4 Non-Functional Requirements

| Requirement | Target | Current State |
|---|---|---|
| **Response time** | < 2 seconds for standard page loads | Dependent on hosting performance |
| **Availability** | 99.5% uptime during operating hours | Not formally monitored |
| **Scalability** | Support ~500 concurrent users across depots | Not load-tested |
| **Data retention** | Crew records retained per Kenya Railways policy | Enforced by no-delete policy on historical records |
| **Security** | Session-based auth, RBAC, CSRF protection | Implemented; SSO not yet in place |

---

## 7. Operations & Support

### 7.1 Operational Overview

**Owning team:** ICT Division, Kenya Railways Corporation.

**Typical operational tasks:**

| Task | Frequency | Method |
|---|---|---|
| End-of-day crew status copy | Daily (automated) | Cron webhook → `crew:copy-end-of-day-status` |
| Auto-checkout of rested crew | Every 5 minutes (automated) | Cron webhook → `running-rooms:auto-checkout-rested` |
| Maintenance mode deactivation | Periodic (automated) | Cron webhook → `maintenance:auto-deactivate` |
| Database backups | _(To be documented)_ | _(To be documented)_ |
| User provisioning / deprovisioning | As needed | Manual via admin panel |

### 7.2 Monitoring & Alerting

**Current state:** No formal monitoring or alerting infrastructure is in place.

**Recommended setup:**

| Tool | Purpose |
|---|---|
| Application Performance Monitoring (e.g., Laravel Telescope, New Relic) | Request tracing, slow query detection |
| Uptime monitoring (e.g., UptimeRobot, Pingdom) | Availability checks on `https://cms.krc.co.ke` |
| Log aggregation (e.g., Papertrail, CloudWatch) | Centralized log review |
| Alert channels | Email to ICT Helpdesk, SMS for critical failures |

### 7.3 Logging & Diagnostics

**Log storage:** Laravel default log files (`storage/logs/laravel.log`).

**Key log fields:** Timestamp, severity, exception message, stack trace, request URL, user ID (when authenticated).

**Trace a user action:** Two complementary sources:
1. **Application logs** — check `storage/logs/laravel.log` for the approximate timestamp, filter by request URL or user context.
2. **Audit logs** — for sensitive entities (users, roles, permissions, crew members, crew records), query the `audit_logs` table by actor, entity, and timestamp to see the full before/after trail (see Section 8.3).

_(Enhanced request-ID logging is recommended — see Section 7.2.)_

### 7.4 Incident Management

**Detection:** Currently relies on user reports to ICT Helpdesk.

**Recommended runbooks for common failures:**

| Failure Mode | Diagnostic Steps | Resolution |
|---|---|---|
| Database connection failure | Check MySQL service status; verify `.env` credentials | Restart MySQL; verify credentials; check hosting status |
| Cron webhook not firing | Check cron-job.org job status (ID: 827392); verify token validity | Restart cron job; verify `CRON_TOKEN` in `.env` |
| Auth failure (users locked out) | Check `users` table for account status; verify password hashes | Reset password via admin panel; check legacy `pw` column migration |
| Maintenance mode stuck | Check `laravel_maintenance` cookie; verify `maintenance.php` config | Clear cookie; manually deactivate via `/maintenance-login` |

**Escalation path:** ICT Helpdesk → ICT Division Manager → External hosting support (if infrastructure issue).

### 7.5 Backup, Restore & DR

| Item | Detail |
|---|---|
| **Backup schedule** | _(To be documented — recommend daily MySQL dumps with 30-day retention)_ |
| **Restore procedure** | _(To be documented)_ |
| **DR strategy** | _(To be documented — currently no formal DR plan)_ |
| **RTO / RPO** | _(To be defined)_ |

---

## 8. Security & Compliance

### 8.1 Data Classification

| Classification | Data Examples |
|---|---|
| **Internal** | Crew duty status, room assignments, operational reports |
| **Confidential** | User credentials, role assignments, maintenance credentials |
| **Restricted (PII)** | Crew personal data stored in `crew_members`: first/last/display name, `staff_number`, `crew_id`, `hire_date`, `phone`, `email` |

> **PII gap (closed in v1.0):** The `crew_members` table stores personal data (names, staff numbers, hire date, phone, email). The **Kenya Data Protection Act 2019** therefore applies today. The application-level controls implemented so far:
>
> - Audit logging of every CRUD operation on `CrewMember` (read/modify/delete trail, redacted of secrets).
> - Soft deletes on operational tables (`2026_09_04_000003_add_soft_deletes_to_operational_tables`) enabling erasure without physical loss.
> - RBAC-gated access to crew data (`manage_crew` permission; station/booking officers scoped to their depot via `depot_code`).
>
> **Remaining PII gaps to close (see Section 9.7.3):** encryption at rest, log redaction of PII fields, field-level classification, export approvals, and privacy-regime documentation (DPIA, data-subject rights, retention policy).

### 8.2 Access Control

- RBAC with five seeded roles enforcing least-privilege access.
- Super Admin bypass is logged via Gate override in `AuthServiceProvider`.
- API endpoints are throttled (120 requests per minute per user).
- Maintenance mode has separate credential set to prevent unauthorized admin access.

### 8.3 Audit Logging

**Status: Implemented (v1.0).**

Dedicated audit logging is implemented for all CRUD operations on sensitive entities.

**Entities audited (via Eloquent observers in `app/Observers/EloquentAuditObserver.php`):**

| Entity | Model | Events Logged |
|---|---|---|
| Users | `App\User` | created, updated, deleted, restored |
| Roles | `App\Role` | created, updated, deleted, restored |
| Permissions | `App\Permission` | created, updated, deleted, restored |
| Crew Members (PII) | `App\CrewMember` | created, updated, deleted, restored |
| Crew Records | `App\Models\CrewRecord` | created, updated, deleted, restored |

Observers are registered in `app/Providers/AppServiceProvider.php` and capture **all** entry points — Filament admin panel, SPA endpoints, controllers, and Artisan commands — because they hook into Eloquent model events.

**Capture fields (`app/Services/AuditLogger.php`):**

| Field | Description |
|---|---|
| `created_at` | Timestamp of the event |
| `actor_username` | User ID / username who performed the action (or `system` for CLI tasks) |
| `actor_ip` | Source IP address (web requests) |
| `actor_user_agent` | Browser/user-agent string |
| `event` | `created` / `updated` / `deleted` / `restored` |
| `entity_type` | Model class name |
| `entity_id` | Record identifier |
| `before` | Snapshot of the record state before the change |
| `after` | Snapshot of the record state after the change |
| `changes` | Attribute-level diff (before → after) for updates |
| `metadata` | Optional extra context (route, source) |

**Storage:** Dedicated, append-only `audit_logs` table (no `updated_at` column — audit rows are never modified, ensuring tamper-evident records).

**Sensitive-data redaction:** The `AuditLogger::redact()` method masks values of sensitive fields before persistence — `password`, `pw`, `remember_token`, and any key containing `token`, `secret`, or `_key` are stored as `[REDACTED]`.

**Retention:** The `audit:prune` Artisan command (`app/Console/Commands/PruneAuditLogs.php`) deletes entries older than the configured retention period (default **365 days / 12 months**). It is scheduled to run daily in `app/Console/Kernel.php`.

**Querying audit logs:**

```bash
php artisan tinker --execute="
  \App\Models\AuditLog::where('entity_type', \App\User::class)
    ->where('entity_id', '<username>')
    ->orderByDesc('id')->get();
"
```

### 8.4 Compliance & Policy Alignment

| Standard/Policy | Applicability | Status |
|---|---|---|
| Kenya Railways ICT Security Policy | Required | Partial — RBAC + audit logging implemented; access reviews and formal DR still needed |
| Data Protection Act (Kenya, 2019) | **Applicable — crew PII stored** (names, staff numbers, hire date, phone, email) | Audit logging implemented; encryption at rest, log mask/redaction of PII, DPIA and data-subject workflows still needed (see Section 9.7.3) |
| OWASP Top 10 | Best practice | CSRF protection in place; XSS mitigated by Filament/Livewire; input validation implemented |

### 8.5 Email Notification Service (v1.4)

**Objective:** deliver notification emails for operational events (e.g., running-room auto-checkout, alerts to ICT Security/Helpdesk) in addition to the existing in-app `system_notifications` channel.

**Implementation:**

| Component | Location | Role |
|---|---|---|
| `NotificationService` | `app/Services/NotificationService.php` | Central service: `notify()` persists an in-app `SystemNotification` and delivers an email copy; `notifyUsers()` batches; `email()`/`emailAdmins()` send alert-channel emails without an in-app row |
| `SystemNotificationMail` | `app/Mail/SystemNotificationMail.php` | Mailable (subject prefixed with the uppercased `type`, e.g. `[RUNNING_ROOM_AUTO_CHECKOUT] …`) |
| Email template | `resources/views/email/system-notification.blade.php` | KR-branded HTML template (logo header, type badge, body, meta) |
| Config | `config/notifications.php` | Email master switch (`NOTIFICATIONS_EMAIL_ENABLED`), alert recipients (`ALERT_EMAIL_RECIPIENTS`), retention placeholder |
| Env template | `.env.example` | `MAIL_*` SMTP block + `NOTIFICATIONS_*` / `ALERT_EMAIL_RECIPIENTS` |
| Migration | `2026_09_09_160000_add_email_delivery_to_system_notifications_table` | Adds `email_delivered_at` to `system_notifications` to track successful email copies |
| Test command | `notifications:test-email {email?}` | Ops verification: sends a test email (`php artisan notifications:test-email ops@krc.co.ke`) |

**Behaviour & guarantees:**

- **Best-effort delivery:** a mail failure is logged (`Notification email delivery failed`) and never prevents the in-app notification from being recorded.
- **Queue-aware:** when `QUEUE_CONNECTION` ≠ `sync`, emails are `queue()`d; on shared hosting (sync) they are sent synchronously.
- **Audited:** notification creation flows through `SystemNotification`; delivery status is captured in `email_delivered_at`.
- **Not PII-leaking:** emails carry operational content only; no credentials or sensitive personal data.

**Email config (from `.env`):**

```
MAIL_MAILER=smtp            MAIL_HOST=smtp.office365.com
MAIL_PORT=587               MAIL_ENCRYPTION=tls
MAIL_USERNAME=...           MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=crew-system@krc.co.ke   MAIL_FROM_NAME="KR Crew System"
NOTIFICATIONS_EMAIL_ENABLED=true
ALERT_EMAIL_RECIPIENTS=ict-security@krc.co.ke,ict-helpdesk@krc.co.ke
```

**Wiring:** the existing auto-checkout notification (`Command\AutoCheckoutExpiredRest`, scheduled every 5 min) now routes through `NotificationService::notifyUsers()`, so HQ admins and depot booking officers receive both an in-app notification and an email copy.

---

## 9. Enterprise Security & Single Sign-On (SSO)

> **Audience:** Security engineers, platform/identity team, backend developers, and IT operations.

### 9.1 Security Architecture Overview

The KR Crew System currently implements **application-level authentication** using Laravel session-based auth with username/password credentials. All user access is managed within the application itself; no external identity provider (IdP) is currently integrated.

**Trust boundaries (current):**

```
Browser ←──HTTPS──→ Laravel App ←──TCP──→ MySQL Database
                                              │
                                    cron-job.org (webhook)
```

**Trust boundaries (target, with SSO):**

```
Browser ←──HTTPS──→ Laravel App ←──TCP──→ MySQL Database
    │                    │
    │              IdP (Azure AD / Okta)
    │              SAML 2.0 / OIDC
    └──────────────────┘
```

**Reference:** Enterprise security architecture documents and Kenya Railways ICT Security Policy (to be linked when available).

### 9.2 Authentication Model

#### 9.2.1 Authentication Method(s)

| Method | Status | Details |
|---|---|---|
| **Laravel Session Auth** | Active (primary) | Username/email + password, bcrypt verification |
| **SSO via SAML 2.0** | Planned | Azure AD or Okta integration (see Section 9.2.2 and **Appendix F**) |
| **SSO via OIDC/OAuth 2.0** | Planned | Alternative to SAML if IdP supports OIDC natively (**recommended** — see Appendix F) |
| **Service Accounts** | Not implemented | Future: for API integrations and automated processes |
| **Maintenance Mode Auth** | Active (restricted) | Separate credentials for system maintenance access |

#### 9.2.2 SSO Integration Details (Planned)

**IdP configuration (to be completed upon implementation):**

| Parameter | Value |
|---|---|
| **IdP Name** | _(TBD — Azure AD / Okta / Ping Identity)_ |
| **Entity ID / Issuer URL** | _(To be configured)_ |
| **SSO URL (SAML) / Authorization Endpoint (OIDC)** | _(To be configured)_ |
| **SLO URL** | _(To be configured)_ |
| **Certificate** | _(To be managed via enterprise KMS; rotation annually)_ |

**Service Provider (SP) configuration (to be implemented in Laravel):**

| Parameter | Value |
|---|---|
| **Assertion Consumer Service (ACS) URL** | `https://cms.krc.co.ke/auth/saml/callback` _(proposed)_ |
| **Audience / Client ID** | _(To be assigned by IdP admin)_ |
| **Redirect URIs** | `https://cms.krc.co.ke/dashboard`, `https://cms.krc.co.ke/auth/callback` |

**Recommended Laravel packages:**

- `socialiteproviders/manager` (for OIDC)
- `aacotroneo/laravel-saml2` or `lightsofthouse/laravel-saml2` (for SAML 2.0)

**Session management (target):**

| Parameter | Value |
|---|---|
| Session timeout | 30 minutes idle, 8 hours absolute |
| "Remember me" | Disabled (enterprise security best practice) |
| Session invalidation | On logout, on IdP-initiated SLO, on password change, on admin revoke |
| Token storage | `httpOnly`, `Secure`, `SameSite=Lax` cookies |

#### 9.2.3 Multi-Factor Authentication (MFA)

| Aspect | Detail |
|---|---|
| **Enforcement** | To be enforced at the IdP level (Azure AD Conditional Access / Okta policies) |
| **Supported methods** | TOTP authenticator apps, Microsoft Authenticator push notifications |
| **Exemptions** | Service accounts (using certificate-based auth); break-glass accounts (see Section 9.10) |
| **Application-level MFA** | Not currently implemented; delegated to IdP |

### 9.3 Authorization & Access Control

#### 9.3.1 Authorization Model

The application uses **Role-Based Access Control (RBAC)** enforced at both the Filament admin-panel level (via Policies) and the backend API level (via middleware and Gate checks).

| Layer | Enforcement Mechanism |
|---|---|
| **Filament Admin Panel** | Resource-level Policies (`app/Policies/`), page-level `canAccess()` |
| **Web Routes** | Middleware-based role checks |
| **JSON API Endpoints** | `auth` middleware + manual permission checks in controllers |
| **Super Admin** | Gate::before bypass in `AuthServiceProvider` |

#### 9.3.2 Roles, Groups & Claims (SSO Mapping — Planned)

| IdP Group | Application Role | Permissions |
|---|---|---|
| `KR-ICT-Admins` | `super_admin` | Full system access |
| `KR-Operations-HQ` | `hq_admin` | All depots/regions, crew management, reports |
| `KR-Crew-Admins` | `crew_admin` | Crew records, depots, rosters, crew reports |
| `KR-Station-Officers` | `station_officer` | Running room operations, check-in/checkout |
| `KR-Booking-Officers` | `booking_officer` | Daily crew status entry, running room operations |

**Role definition process:** New roles require approval from the Super Admin and ICT Security. Role definitions are recorded in `database/seeders/RoleSeeder.php` and version-controlled.

#### 9.3.3 Least Privilege & Separation of Duties

| Principle | Implementation |
|---|---|
| **Least privilege** | Default role for new users is none (no access until explicitly assigned). Users receive only the permissions required for their operational role. |
| **Separation of duties** | Crew Admins cannot manage user accounts or roles. Station Officers cannot generate system-wide reports. Only Super Admins can modify RBAC configuration. |
| **Audit** | _(Recommended: periodic access reviews quarterly, comparing active user roles against HR roster changes)_ |

### 9.4 Identity Lifecycle & Provisioning

| Lifecycle Event | Current Process | Target Process (with SSO) |
|---|---|---|
| **User creation** | Manual creation by Super Admin via Filament | Automated via SCIM provisioning from IdP |
| **Role assignment** | Manual assignment by Super Admin | IdP group membership → automatic role mapping |
| **User update** (department change) | Manual update by admin | SCIM sync from IdP; role remapping on group change |
| **User deactivation** (departure) | Manual deactivation by admin | IdP deactivation → SCIM deprovisioning → app account disabled |
| **Access review** | _(Not currently conducted)_ | Quarterly recertification by line managers via IdP or access-review tool |

### 9.5 Session & Token Security

| Aspect | Detail |
|---|---|
| **Token types** | Laravel session tokens (file-based); SAML assertions / JWTs (post-SSO implementation) |
| **Token lifetime** | Session: configurable in `config/session.php` (currently file driver, lifetime set by `SESSION_LIFETIME`) |
| **Storage** | Server-side file sessions; client-side `httpOnly` cookies |
| **CSRF protection** | Laravel CSRF token on all forms; `VerifyCsrfToken` middleware |
| **XSS protection** | Filament/Livewire auto-escaping; Content Security Policy headers _(recommended to add)_ |
| **Session fixation** | Laravel regenerates session ID on login (`session()->regenerate()`) |
| **SameSite cookies** | _(Recommended: set `SameSite=Lax` or `SameSite=Strict` in `config/session.php`)_ |

### 9.6 Audit Logging & Monitoring for Security Events

**Events to log (recommended implementation):**

| Event | Log Fields |
|---|---|
| Successful login | timestamp, user_id, IP, user_agent, method (password/SSO) |
| Failed login | timestamp, username_attempted, IP, user_agent, failure_reason |
| Role/permission change | timestamp, admin_user_id, target_user_id, old_role, new_role |
| Sensitive data access | timestamp, user_id, entity_type, entity_id, action |
| Configuration change | timestamp, admin_user_id, config_key, old_value, new_value |
| Maintenance mode activation | timestamp, admin_user_id, IP |

**Log destination:** _(Recommended: ship to enterprise SIEM — e.g., Microsoft Sentinel, Splunk — or a centralized logging platform.)_

**Retention:** Minimum 12 months for security events; align with Kenya Railways ICT policy.

**Alerts (recommended):**

| Condition | Threshold | Alert Channel |
|---|---|---|
| Failed logins from single IP | > 5 in 5 minutes | Email to ICT Security |
| Failed logins for single account | > 3 in 10 minutes | Email to ICT Security + temporary lockout |
| Privilege escalation event | Any | Immediate notification to Super Admin + ICT Security |
| Unauthorized admin access attempt | Any | Immediate notification to ICT Security |

### 9.7 Data Protection

#### 9.7.1 Data Classification & Handling

| Classification | Examples | Handling Requirements |
|---|---|---|
| **Internal** | Crew duty status, room assignments, operational reports | Standard internal access controls; no external sharing |
| **Confidential** | User credentials, RBAC configurations, maintenance credentials | Encrypted at rest and in transit; access logged; need-to-know basis |
| **Restricted** | _(Future: PII — staff personal details, contact information)_ | Data minimization; consent management; right-to-erasure capability |

#### 9.7.2 Encryption

| Aspect | Current | Target |
|---|---|---|
| **In transit** | TLS via hosting provider (HTTPS enforced at `cms.krc.co.ke`) | TLS 1.2+ enforced; HSTS headers added |
| **At rest** | _(Dependent on hosting provider's MySQL configuration)_ | Verify TDE or disk encryption is enabled; enable if not |
| **Backups** | _(To be documented)_ | Encrypted backups with enterprise KMS key management |
| **Key management** | _(N/A currently)_ | Enterprise KMS; annual key rotation |

#### 9.7.3 Sensitive Data Handling

**Current state:** The `crew_members` table stores Personal Information (name, `staff_number`, `crew_id`, `hire_date`, `phone`, `email`) and is therefore subject to the Kenya Data Protection Act 2019.

| Data Type | Current Handling | Required Enhancement |
|---|---|---|
| **Passwords** | bcrypt hashed; legacy `pw` column | Remove legacy `pw` column after migration complete |
| **Maintenance credentials** | Stored in `.env` (plaintext) | Use vault or encrypted secrets manager |
| **CRON_TOKEN** | Stored in `.env` (plaintext) | Use vault or encrypted secrets manager |
| **Crew PII (phone, email, names)** | Plain storage in `crew_members`; already excluded from `audit_logs` secrets via redaction; not yet masked in app logs | Encrypt at rest; mask phone/email in UI (`first_name`/`last_name`/`phone`/`email` column restrictions); redact PII in `storage/logs` and in export filenames |
| **Logs** | Default Laravel logging (may include sensitive context) | Implement log redaction for PII and credential fields |
| **Data export** | Reports available for print/export | Add approval workflow for bulk exports of crew PII; watermark exports; log export events in `audit_logs` |

**Required before adding any further personal data** (national ID, next-of-kin, medical, disciplinary, salary):

| Requirement | Detail |
|---|---|
| **DPIA** | Data Protection Impact Assessment covering each new PII category |
| **Lawful basis + notices** | Consent/legitimate-interest basis; data-subject notices to crew |
| **Terms (Controllers/Processors)** | Microsoft shared host is a processor — verify data-processing terms, region, and retention |
| **Right-to-erasure / rectification / access** | Operational workflows to fulfil DPA Part IV rights |
| **Retention policy** | HR-type records typically retained ~6 years post-exit; define and document |
| **Data minimization** | Collect only operationally required fields; avoid sensitive categories unless mandated |

### 9.8 Compliance & Policy Alignment

| Standard/Policy | Relevance | Current Alignment | Gap |
|---|---|---|---|
| **Kenya Railways ICT Security Policy** | Mandatory | RBAC implemented; session security in place; audit logging implemented | Access reviews, formal DR |
| **Data Protection Act (Kenya, 2019)** | **Mandatory — crew PII is stored** (names, staff numbers, hire date, phone, email) | CRUD auditing on PII implemented; soft deletes in place | Encryption at rest, PII redaction in logs, DPIA, data-subject rights workflows (see Section 9.7.3) |
| **OWASP Top 10 (2021)** | Best practice | CSRF, XSS mitigations in place; input validation | Security headers (CSP, HSTS); dependency scanning |
| **ISO 27001** | If enterprise requires | Partial alignment via RBAC and session controls | Formal ISMS documentation, risk assessment |
| **SOC 2** | If enterprise requires | Not currently targeted | Full control implementation required |

### 9.9 Security Testing & Vulnerability Management

| Activity | Current State | Recommended Implementation |
|---|---|---|
| **SAST (Static Analysis)** | Not implemented | Integrate PHPStan or SonarQube in development workflow |
| **DAST (Dynamic Analysis)** | Not implemented | Quarterly scans using OWASP ZAP or Burp Suite |
| **Dependency scanning** | Not implemented | `composer audit` in CI; GitHub Dependabot or Snyk |
| **Penetration testing** | Not conducted | Annual third-party penetration test |
| **Vulnerability tracking** | Not formalized | Use issue tracker with severity-based SLAs: Critical 24h, High 72h, Medium 30 days, Low 90 days |

### 9.10 Break-Glass & Emergency Access

**Status: Implemented (v1.1).**

A dedicated break-glass emergency login is available at `/break-glass-login` when normal sign-in (local credentials or future SSO) is unavailable.

**Implementation components:**

| Component | Location | Purpose |
|---|---|---|
| Controller | `app/Http/Controllers/Auth/BreakGlassController.php` | Renders the page, validates credentials, enforces justification, logs in as the configured target account |
| Middleware | `app/Http/Middleware/EnsureBreakGlassSession.php` | Enforces the session lifetime; force-expires and audited on expiry |
| View | `resources/views/auth/break-glass-login.blade.php` | Emergency login UI |
| Banner | `resources/views/partials/break-glass-banner.blade.php` | Shows a countdown banner on every panel page while a break-glass session is active |
| Config | `config/breakglass.php` | All break-glass settings (env-driven) |
| Rotation command | `app/Console/Commands/RotateBreakGlassCredentials.php` | `php artisan break-glass:rotate [--update-env]` |
| Routes | `routes/auth.php` | GET/POST `/break-glass-login` (POST throttled `5,1`), POST `/break-glass-logout` |

**Configuration (`.env`):**

| Variable | Default | Purpose |
|---|---|---|
| `BREAK_GLASS_ENABLED` | `false` | Master switch — keep off unless an outage |
| `BREAK_GLASS_USERNAME` | — | Shared emergency username |
| `BREAK_GLASS_PASSWORD` | — | Shared emergency passphrase (hashed check) |
| `BREAK_GLASS_LOGIN_AS` | `superadmin` | Local account the session logs in as |
| `BREAK_GLASS_SESSION_HOURS` | `8` | Maximum session lifetime |
| `BREAK_GLASS_REQUIRE_JUSTIFICATION` | `true` | Require a written justification (audited) |

**Guarantees:**

- **Authentication** is separate from the app's normal login (shared emergency credentials, no account in the `users` auth flow).
- **Every attempt (success and failure)** is written to `audit_logs` (`break_glass_login`, `break_glass_login_failed`, `break_glass_logout`, `break_glass_expired`, `break_glass_credentials_rotated`), capturing username attempted, IP, user-agent, and justification.
- **Session lifetime** is short (default 8h) and force-expired by middleware if exceeded.
- **Justification is mandatory** (min 20 chars) and stored in the audit trail.
- **Rotation** is automated: `php artisan break-glass:rotate --update-env` generates a new passphrase, writes it to `.env`, and records the rotation.

**Operating procedure (during an outage):**

1. Authorised admin obtains approval from the ICT Division Manager.
2. Set `BREAK_GLASS_ENABLED=true` in production `.env` (confirm the credentials are current).
3. Operator signs in at `/break-glass-login` with credentials from the enterprise vault / sealed envelope and enters a justification.
4. A visible banner with a live countdown is shown on every panel page.
5. After resolution, the operator signs out (or the session expires), and **rotate the credentials immediately**:
   `php artisan break-glass:rotate --update-env`
6. Set `BREAK_GLASS_ENABLED=false` once normal authentication is restored.

**Review & audit:** Quarterly review of all `break_glass_*` audit events by ICT Security; credentials rotated every 90 days or after any use.

### 9.11 SSO Troubleshooting Guide (Technical)

> _Applicable after SSO implementation. Pre-filled with common issues based on standard SAML/OIDC integrations._

#### User cannot log in via SSO

1. Verify user exists and is active in the IdP (Azure AD / Okta admin console).
2. Check that the user is a member of the required IdP group (mapped to application role).
3. Inspect the SAML assertion / OIDC token for errors:
   - **SAML:** Use browser developer tools → Network tab → inspect the POST to ACS URL. Decode the `SAMLResponse` base64 payload.
   - **OIDC:** Check the callback URL response for `error` and `error_description` parameters.
4. Verify the application's SP metadata matches the IdP configuration (Entity ID, ACS URL, certificates).
5. Check application logs (`storage/logs/laravel.log`) for SSO-related error entries.

#### "Invalid assertion" / "Invalid token" errors

1. **Clock skew:** Verify the server time matches the IdP time (allowable drift: typically ±5 minutes).
2. **Certificate mismatch:** Confirm the IdP signing certificate in the application matches the current IdP certificate. Check for recent certificate rotation.
3. **Audience mismatch:** Verify the ` AudienceRestriction` (SAML) or `aud` claim (OIDC) matches the configured Audience / Client ID.
4. **Issuer mismatch:** Verify the `Issuer` (SAML) or `iss` claim (OIDC) matches the configured Entity ID / Issuer URL.

#### SSO works for some users but not others

1. Compare group memberships of working vs. non-working users in the IdP admin console.
2. Check for Conditional Access Policies in Azure AD / Okta that may block specific users or locations.
3. Verify user attributes (email, employee ID) are correctly mapped and present in the assertion/token.
4. Check if the user's IdP account is in a locked, disabled, or password-expired state.

#### SSO login loop / redirect loop

1. Verify the ACS URL is correctly registered in the IdP and matches the application's callback URL exactly (including trailing slash).
2. Check for `SameSite` cookie restrictions preventing the session cookie from being set after SSO callback.
3. Verify the application session driver is correctly configured and the session storage path is writable.

---

## 10. Version 2 Features (Roadmap)

> **Status:** Planned (not yet implemented). This section defines the Version 2 feature set for the KR Crew System. It is a functional roadmap: each feature below describes business objectives, high-level design, data/UI impact, integration points, and dependencies on the existing system.

### 10.1 Version 2 Overview & Goals

V2 extends the system from a **recording** platform to a **semi-automated operations platform**. The core goals:

| Goal | V1 (current) | V2 target |
|---|---|---|
| **Sign-in** | Local username/email + password | Corporate **AD / Entra ID SSO** (single sign-on, MFA at IdP) |
| **Driver scheduling** | No train-to-driver assignment concept | **Driver schedules** linking drivers to specific trains/services |
| **Crew status updates** | Manual entry + end-of-day copy | **Automated status transitions** driven by schedules |
| **Rest / leave** | Manual rest tracking with eligibility calculation | **Automated rest & leave notifications** (pre-entitlement, e.g., annual leave) |
| **Notifications** | In-app notifications + **email (v1.4, §8.5)** | **SMS notifications** to crew/staff (phone numbers already stored on `crew_members`) |
| **Training** | Status code `T` (Training) exists, no records | **Training-gap analysis** — identify staff needing training, recertification, or competency-based deployment |

**Business value:** fewer manual entries, lower human-error rate in status recording, faster operational reaction (rest/leave/training alerts), and a single enterprise identity for access.

### 10.2 Feature V2.A — AD / Entra ID SSO (Single Sign-On)

**Objective:** Replace/manage local-password login with corporate Active Directory via Microsoft Entra ID.

| Aspect | Detail |
|---|---|
| **Primary** | OIDC via Entra ID (Azure AD), synced with on-prem AD via Entra Connect |
| **Fallback retained** | Local username/email + password (break-glass) — see §9.10 and Appendix F |
| **Reference** | Full concrete plan already documented in **Appendix F** |
| **Dependency** | `azure_id` migration on `users`; `laravel/socialite`; IdP app registration; MFA via Conditional Access |
| **Effort** | Medium (largest external dependency: IdP/tenant admin) |

**V2 acceptance criteria:**
- Users sign in with corporate credentials; local password becomes optional.
- Entra security groups map to application roles (`role_code`, `is_hq`, `is_super_admin`).
- MFA enforced at IdP; every SSO login logged in `audit_logs`.
- Break-glass access remains operational (Appendix F, §9.10).

### 10.3 Feature V2.B — SMS Notifications

**Objective:** Deliver time-critical operational alerts directly to staff phones. The system already stores `phone` on `crew_members` (PII) and has an in-app `system_notifications` table; V2 adds SMS as an outbound channel.

#### 10.3.1 Notification triggers (candidate set, configurable)

| Trigger | Recipient | Example content (concept) |
|---|---|---|
| **Approaching duty** | Driver/crew member | "You are booked for train KRD-123 departing Nairobi 06:00 tomorrow." |
| **Schedule change** | Affected crew | "Your 12:30 Mombasa run is cancelled; standby." |
| **Rest period started / ended** | Crew member | "Rest period completed at 14:00 — available for duty." |
| **Leave approved** | Staff member | "Your annual-leave request 15–20 Jul is approved." |
| **Training gap / training scheduled** | Staff member / crew admin | "Mandatory recertification due within 30 days — course slots available." |
| **Matter/issue resolution** | Running-room staff | "Matter MTR-2026-0042 resolved." |
| **Auto-checkout notice** | Booking officer / depot | "Crew member X auto-checked out at 14:00." |

#### 10.3.2 Delivery architecture (proposed)

```
Laravel app
  └── NotificationService (app/Services)
        ├── (v1.4, live) email channel ──→ SMTP (config/notifications.php) §8.5
        ├── (new) SmsService  ──→ SMS Gateway (Africas Talking / Twilio / enterprise SMPP)
        ├── (existing) in-app system_notifications table
        └── audit_logs integration (every notification event audited)
```

- **Gateway abstraction:** single `SmsProvider` interface so the provider (Africa's Talking — common in Kenya — Twilio, or an enterprise SMPP aggregator) can be swapped without touching business logic.
- **Queueing:** introduce Laravel queue worker (`QUEUE_CONNECTION=database` or `redis`) so SMS sending is async and retryable; **this is a new infrastructure dependency** for V2.
- **Delivery status:** persist send/attempt/delivery states in a new `sms_messages` table (see 10.3.3).

#### 10.3.3 Proposed data model (new tables)

| Table | Purpose |
|---|---|
| `sms_messages` | Outbound SMS log: id, to_phone, message, status, gateway_ref, attempts, sent_at, delivered_at |
| `notification_preferences` | Per-user opt-in/opt-out and channel selection (sms / in-app) |
| (extend) `system_notifications` | Add `channel` (in-app/sms), `external_ref`, `sent_at` columns |

#### 10.3.4 Compliance & constraints

- **Phone numbers are PII** (Data Protection Act 2019) — see §9.7.3. SMS handling must be added to the DPIA.
- **Opt-in / consent** required; store consent record per number in `notification_preferences`.
- **CA Kenya rules:** if using shortcodes, the Communications Authority content-code / shortcode regulations and Do-Not-Call (DNC) list apply.
- **Message content:** never transmit sensitive personal data (health, national ID) or credentials in SMS body.
- Every SMS send/failure recorded in `audit_logs` (event `sms_sent`, `sms_failed`).

### 10.4 Feature V2.C — Driver Schedules for Trains

**Objective:** Formalize which **driver** (crew member in a driver/train-manning designation) is assigned to which **train service** at a given date/time. This is the backbone that enables automated status changes (V2.D) and rest/leave notifications (V2.E).

#### 10.4.1 Core concepts (mapped to existing master data)

| Concept | Existing entity | Notes |
|---|---|---|
| **Driver** | `crew_members` | A crew member whose `designation_code` is a driver/train-manning designation (e.g., Driver, Driver's Assistant/SLAs) |
| **Train service** | `train_types` + (restored) `routes` | `train_type_code` (passenger/goods/shunting) + `route_code` (origin→destination depots) |
| **Shift** | `shift_templates` | Template start/end times |
| **Assignment** | **NEW** `driver_schedules` / `train_assignments` | Date + train service + driver + shift + depot |

_Note on routing:_ V1 earlier dropped orphaned route tables (`2026_09_04_000002_drop_orphaned_route_tables`) — V2 re-introduces a clean `routes` table (route_code, route_name, origin_depot_code, destination_depot_code, is_active) where needed.

#### 10.4.2 Proposed data model

```
train_services                driver_schedules                 crew_members (driver)
  train_service_code  ──────  id (or UUID)                ──────  record_id
  train_type_code              train_service_code                 staff_number
  route_code                   driver_crew_id                     designation_code
  scheduled_departure          schedule_date                      depot_code
  scheduled_arrival            scheduled_shift_code
  origin_depot_code            assigned_from / assigned_until
  destination_depot_code       status (draft/confirmed/cancelled)
  status                       notes
  (new table)                  (new table)
```

#### 10.4.3 UI workflow (Filament)

- **NEW** `TrainServiceResource` — manage train services (reuses `TrainTypeResource` and a new `RouteResource`).
- **NEW** `DriverScheduleResource` — create/update assignments; table filters by date, depot, train service, driver.
- **Depot/booking officers** confirm schedules daily; managers review via dashboard widgets.

### 10.5 Feature V2.D — Automated Status Changes (driven by schedules)

**Objective:** Reduce manual daily-status entry by deriving recommended status transitions from confirmed driver schedules, with **manual confirmation** retained for auditability.

#### 10.5.1 Proposed automation rules (rule engine in `config/crew.php` + `app/Services/StatusAutomationService`)

| Condition | Recommended transition |
|---|---|
| Driver has a confirmed assignment **today** | Status `BK` (booked) → `SB`/`R` (standby/rest) according to shift window |
| Assignment **departed** (schedule + current time) | `SB` → `BK` (on duty) |
| Assignment **completed** (arrival time passed) | `BK` → `R` (rest starts; rest hours computed from `crew_status_segments`) |
| Next assignment is **tomorrow** and rest requirement met | `R` → `BK` (auto-suggest book for next day) |
| No assignment + today = their day off | `R` / `L` suggestion |
| Rest-period exceeded allowed threshold | `NTB` (not to be called) until rested (existing protection) |

#### 10.5.2 Design principles

- **"Auto-suggest, human confirms":** the engine proposes status + writes to the existing `crew_status_segments`; a booking officer accepts/rejects in bulk or per record (retains accountability and the V1 audit trail).
- **Idempotent & time-window-safe:** respects `CREW_PAST_DAY_EDIT_WINDOW_DAYS`; never mutates locked historical days.
- **Reuses existing services:** `CrewStatusService` for eligibility, `crew_status_segments` for time tracking.
- **Audited:** every suggested and accepted change logged via the V1 audit observers.

#### 10.5.3 Deliverables

- `config/crew.php` extended with automation rules (enable flags, thresholds).
- `StatusAutomationService` generating candidate transitions.
- A Filament page/action "Apply suggested status changes" per depot/date.
- Cron/webhook variant for end-of-day automation (reuse cron-job.org pattern).

### 10.6 Feature V2.E — Leave & Rest Notifications (pre-entitlement awareness)

**Objective:** Move beyond reactive rest tracking to **predictive** alerts and leave-entitlement awareness.

| Capability | Detail |
|---|---|
| **Rest eligibility (existing)** | Cross-checked via `crew_status_segments` end times; V2 adds alerts when a crew member is approaching/overdue rest |
| **Leave entitlement (new)** | `leave_balances` table: staff member, leave_type (annual/sick/compassionate), entitlement_days, taken_days, carryover; computed from `hire_date` on `crew_members` |
| **Leave requests (new)** | `leave_requests` table: staff member, dates, type, status (pending/approved/rejected); approval workflow; integrated with rest rules (cannot book duty during approved leave) |
| **Notifications** | Via SMS (V2.B) + in-app: leave approved, leave expiring, rest overdue, eligibility reached |

**Rule examples:**
- Annual leave accrual tied to `hire_date` (crew_members) — **do not add hire date to `users`**; keep it on `crew_members` per §9.7.3.
- A driver with an approved leave date cannot be proposed a train assignment (cross-check in V2.C).
- Rest overdue (> max resting hours) triggers an immediate alert to depot/booking officers.

### 10.7 Feature V2.F — Training Gap Analysis

**Objective:** Identify staff who **need** training (mandatory recertification, competency for a new train type/route) and those who are **qualified** for deployment.

#### 10.7.1 Proposed data model

```
training_courses                 training_records          (derived) competency matrix
  course_code             ──────  id                        per crew member per course:
  course_name                     crew_member_id                 valid_from / valid_until
  category (safety/ops)           course_code                    status (valid/expiring/expired)
  validity_days                    completed_at
  required_for                      expiry_date
  (new table)                      (new table)                  (computed)
```

#### 10.7.2 Gap-detection logic

| Rule | Detection |
|---|---|
| **Course expiring** | `validity_days` / `valid_until` within N days (default 30) → alert staff + crew admin |
| **Course expired** | `valid_until` in the past → mark crew member non-deployable on that competency |
| **New train type / route** | A course is required for a train type/route that the driver lacks → flagged on assignment proposal (V2.C/V2.D) |
| **Designation requirement** | Certain designations require mandatory courses (e.g., driver certification) → gap report per depot |

#### 10.7.3 Deliverables

- `TrainingCourseResource`, `TrainingRecordResource` (Filament).
- **Training Gap Report** — per depot/designation: who is missing / expiring / expired (reuses `ReportBuilderService`).
- Integration: a driver without a valid required course is blocked (with override for HQ admin) from being auto-proposed in V2.D.

### 10.8 Cross-Cutting Considerations (V2)

| Concern | Approach |
|---|---|
| **Notifications** | Central `NotificationService`; in-app (existing) + SMS (new) + scheduled digests |
| **Auditing** | All V2 events (assignments, leave approvals, SMS, training records, status automation) flow into `audit_logs` (V1 §8.3) |
| **RBAC** | New permissions: `manage_train_services`, `manage_driver_schedules`, `manage_leave`, `manage_training`, `send_sms` (seeded; see Appendix B) |
| **Infrastructure** | Introduces a **queue worker** (database/redis) for SMS/notifications and optionally `redis` for cache — update `DEPLOYMENT_GUIDE.md` sizing accordingly |
| **API/integration** | SMS gateway + IdP (Entra) are the two new external integrations; both behind config + env, no secrets in source |
| **PII** | SMS uses `crew_members.phone`; consent + DPA handling (extend §9.7.3 DPIA) |

### 10.9 Suggested Implementation Sequence

| Phase | Scope | Predecessor |
|---|---|---|
| **V2.1** | AD/Entra SSO (Appendix F) | IdP readiness |
| **V2.2** | Data model foundations: `train_services`, `routes`, `driver_schedules` | V2.1 (optional) |
| **V2.3** | Status automation engine (`StatusAutomationService` + confirmation UI) | V2.2 |
| **V2.4** | Notification service + SMS gateway + preferences + consent | V1 audit/queue infra |
| **V2.5** | Leave entitlements/requests + rest alerts | V2.4 |
| **V2.6** | Training records + gap analysis + reports | V2.4, V2.3 |

### 10.10 Explicitly Out of Scope for V2

- Mobile **native** application (V2 keeps a responsive web/SPA + SMS).
- Real-time train telemetry / GPS-based automatic duty start.
- Payroll, HR, or financial integration.
- Multi-tenant SaaS offering (system remains single-tenant for Kenya Railways).

---

## 11. Glossary

| Term | Definition |
|---|---|
| **ABS** | Absent — crew member is off duty due to absence |
| **ACS** | Assertion Consumer Service — SAML endpoint that receives authentication assertions |
| **BK** | Booked — crew member is booked for duty |
| **CM** | Crew Management (module) |
| **CRON_TOKEN** | Secret token used to authenticate the external cron webhook |
| **DAST** | Dynamic Application Security Testing |
| **Depot** | Operational base where crew members are stationed |
| **HSTS** | HTTP Strict Transport Security |
| **HQ** | Headquarters |
| **IdP** | Identity Provider (e.g., Azure AD, Okta) |
| **MFA** | Multi-Factor Authentication |
| **MTR** | Matter Ticket Reference — running room issue tracking format (`MTR-YYYY-####`) |
| **NTB** | Not To Be called — crew member is on rest and not to be called for duty |
| **OIDC** | OpenID Connect — authentication protocol built on OAuth 2.0 |
| **RBAC** | Role-Based Access Control |
| **R** | Resting — crew member is on mandatory rest period |
| **RM** | Running Room Management (module) |
| **RO** | Running Out — crew member has completed duty and is proceeding to rest |
| **SAML** | Security Assertion Markup Language — XML-based authentication protocol |
| **SAST** | Static Application Security Testing |
| **SB** | Standby — crew member is on standby for duty assignment |
| **SCIM** | System for Cross-domain Identity Management — provisioning protocol |
| **SIEM** | Security Information and Event Management |
| **SLO** | Single Logout — IdP-initiated session termination |
| **SK** | Sick — crew member is off duty due to illness |
| **SP** | Service Provider — the application that consumes IdP authentication |
| **SSO** | Single Sign-On |
| **T** | Training — crew member is on training duty |
| **TDE** | Transparent Data Encryption |
| **TLS** | Transport Layer Security |
| **TO** | Technical Out — crew member is on technical assignment |
| **TOB** | Token-Based authentication (used for cron webhook) |
| **Driver schedule** | V2 — formal assignment linking a crew member (driver) to a specific train service on a date/shift (see §10.4) |
| **Train service** | V2 — a scheduled train run characterized by train type, route, departure/arrival times (see §10.4) |
| **Status automation** | V2 — engine that derives recommended crew-status transitions from confirmed driver schedules (see §10.5) |
| **Leave entitlement** | V2 — tracked annual/sick/compassionate leave balances and requests per staff member (see §10.6) |
| **Training gap** | V2 — identified need for training/recertification based on course validity vs. role/train-type requirements (see §10.7) |
| **SMS gateway** | V2 — external provider (e.g., Africa's Talking, Twilio, SMPP aggregator) used to deliver outbound notifications (see §10.3) |

---

## 12. References & Appendices

### References

| Document | Location |
|---|---|
| Project README | `README.md` |
| Deployment Guide | `docs/DEPLOYMENT_GUIDE.md` |
| Scheduled Tasks Documentation | `docs/scheduled-tasks-cron.md` |
| Crew Configuration | `config/crew.php` |
| Maintenance Configuration | `config/maintenance.php` |
| Break-Glass Configuration | `config/breakglass.php` |
| Notification Configuration | `config/notifications.php` |
| Database Migrations | `database/migrations/` |
| Role & Permission Seeders | `database/seeders/` |
| Application Routes | `routes/web.php`, `routes/auth.php` |

### Appendix A: Example Environment Configuration (`.env.example` — Secrets Removed)

```env
APP_NAME="CM & RM System"
APP_URL=https://cms.krc.co.ke
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_TIMEZONE=UTC

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cms
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

SESSION_DRIVER=file
CACHE_DRIVER=file

# Crew Management
CREW_PAST_DAY_EDIT_WINDOW_DAYS=2

# Cron Webhook
CRON_TOKEN=your-secure-token-here

# Maintenance Mode
MAINTENANCE_USERNAME=admin
MAINTENANCE_LOGIN=maintenance
MAINTENANCE_PASSWORD=your-secure-password
```

### Appendix B: Seeded Roles & Permissions

**Roles:**

| Role | Description |
|---|---|
| `super_admin` | Full system access with all permissions |
| `hq_admin` | Headquarters-level admin with cross-depot access |
| `crew_admin` | Crew-specific management (records, rosters, depots) |
| `station_officer` | Station-level operations (running rooms, check-in/out) |
| `booking_officer` | Daily crew status entry and running room operations |

**Permissions:**

| Permission | Description |
|---|---|
| `manage_depots` | Create, edit, and deactivate depots |
| `manage_users` | Create, edit, and deactivate user accounts |
| `manage_crew` | Create, edit, and manage crew member records |
| `manage_roles` | Assign and modify user roles |
| `manage_rosters` | Create and manage duty rosters |
| `manage_reports` | Generate and export operational reports |

**V2 permissions (planned — see §10.8):**

| Permission | Description |
|---|---|
| `manage_train_services` | Create and manage train service schedules |
| `manage_driver_schedules` | Assign drivers to train services |
| `manage_leave` | Manage leave entitlements and approve/decline requests |
| `manage_training` | Manage training courses and records |
| `send_sms` | Trigger/send SMS notifications |

### Appendix C: Scheduled Tasks

| Command | Frequency | Purpose |
|---|---|---|
| `crew:copy-end-of-day-status` | Daily (automated via cron) | Copies previous day's crew status to today as a starting template |
| `running-rooms:auto-checkout-rested` | Every 5 minutes (automated via cron) | Automatically checks out crew members whose rest period has elapsed |
| `maintenance:auto-deactivate` | Periodic (automated via cron) | Deactivates expired maintenance mode sessions |
| `audit:prune --retention=365` | Daily (automated via scheduler) | Enforces audit log retention (min 12 months) |
| `break-glass:rotate [--update-env]` | Manual / quarterly | Rotates emergency access credentials (audited) |

### Appendix D: Database Migration Summary

| Migration | Purpose |
|---|---|
| `create_crew_records_table` | Core crew daily-status records |
| `create_crew_status_segments_table` | Time-based status segments with composite indexing |
| `create_depots_table` | Depot master data |
| `create_designations_table` | Designation master data |
| `create_regions_table` | Region master data |
| `create_status_codes_table` | Status code definitions (BK, SB, R, etc.) |
| `create_train_types_table` | Train type master data |
| `create_shift_templates_table` | Shift template definitions |
| `create_rooms_table` | Running room definitions |
| `create_room_beds_table` | Individual bed records within rooms |
| `create_attendance_records_table` | Check-in/checkout records |
| `create_matters_table` | Issue/matter tickets |
| `create_matter_photos_table` | Photo attachments for matters |
| `create_duty_rosters_table` | Duty roster definitions (UUID keys) |
| `create_duty_roster_items_table` | Individual roster entries (UUID keys) |
| `create_rest_locations_table` | Rest location definitions |
| `create_report_definitions_table` | Saved report configurations |
| `create_system_notifications_table` | In-app notification storage |
| `create_roles_table` / `create_permissions_table` | RBAC tables |
| `create_running_room_options_table` | Running room configuration options |
| `create_audit_logs_table` | Append-only security audit log (Section 8.3) |

### Appendix E: Filament Resources

| Resource | Model | Purpose |
|---|---|---|
| `CrewMemberResource` | `CrewMember` | Manage crew member records |
| `CrewRecordResource` | `CrewRecord` | Manage daily crew status records |
| `DepotResource` | `Depot` | Manage depot master data |
| `DesignationResource` | `Designation` | Manage designation master data |
| `RegionResource` | `Region` | Manage region master data |
| `RoomResource` | `Room` | Manage running room definitions |
| `RoomBedResource` | `RoomBed` | Manage individual beds |
| `AttendanceRecordResource` | `AttendanceRecord` | Track check-in/checkout |
| `MatterResource` | `Matter` | Manage issue tickets |
| `DutyRosterResource` | `DutyRoster` | Manage duty rosters |
| `RestLocationResource` | `RestLocation` | Manage rest locations |
| `UserResource` | `User` | Manage user accounts |
| `RoleResource` | `Role` | Manage roles and permissions |

### Appendix F: AD / Entra ID (Microsoft) SSO Implementation Plan

> **Status: Planned.** The system currently uses local username/email + password authentication. This appendix is the concrete, actionable plan for moving to enterprise SSO via Microsoft Entra ID (Azure AD), which syncs with on-prem Active Directory. It is the recommended approach because the application runs on **Microsoft shared hosting** where direct on-prem LDAP bind is typically blocked by firewall/egress rules.

#### F.1 Why Entra ID (not direct AD/LDAP bind)

| Approach | Feasibility on shared hosting | Notes |
|---|---|---|
| **Entra ID SSO (SAML/OIDC)** | Viable | The IdP is cloud-hosted; only outbound HTTPS is required |
| Direct on-prem AD LDAP bind (port 389/636) | **Unlikely** | Shared hosting egress firewalls typically block LDAP; also no TLS to on-prem AD from cloud |

#### F.2 Prerequisites (top-level checklist)

| # | Item | Owner |
|---|---|---|
| 1 | Confirm Entra ID tenant + on-prem AD sync (Azure AD Connect / Entra Connect) is active for KR staff | Identity / Platform team |
| 2 | Decide protocol: **OIDC** (simpler, recommended) vs **SAML 2.0** | Security / Platform team |
| 3 | Register the app in Entra ID and obtain Client ID, Tenant ID | Identity / Platform team |
| 4 | Create client secret or app certificate | Identity / Platform team |
| 5 | Register redirect URIs (`prod` + `local`) | Identity / Platform team |
| 6 | Create security groups for role mapping (see F.5) | Security team |
| 7 | Add `azure_id` column to the `users` table (F.3) | Backend developer |
| 8 | Install SSO library and implement callback flow (F.4) | Backend developer |
| 9 | Enforce MFA via Conditional Access (F.6) | Security team |
| 10 | Test + break-glass procedure (F.7) | Backend + Security |

#### F.3 Database change

New migration is required before the SSO callback can link Entra users to local accounts:

```php
// database/migrations/<next>_add_azure_id_to_users_table.php
Schema::table('users', function (Blueprint $table) {
    $table->string('azure_id')->nullable()->unique()->after('username');
});
```

- **Join key:** `azure_id` (Entra object ID) is the stable identifier. **Do not use email as the join key** — emails change and can drift.
- **No `hire_date` on `users`:** the `users` table stores **no** `hire_date` (or any HR fields). `hire_date` exists **only** on `crew_members` (which is crew-specific data, not user account data). Do not add HR/PII fields (hire date, national ID, salary, medical) to the `users` table — keep user accounts minimal and store crew HR/PII on `crew_members` under the §9.7.3 controls.
- Backfill: map existing `users.username`/`email` to Entra object IDs during rollout (`onmicrosoft` – KR domain users).
- Each `users` row may keep `password`/`pw` for break-glass fallback.

#### F.4 Code implementation (OIDC via Socialite) — reference guide

**1. Install packages:**

```bash
composer require laravel/socialite socialiteproviders/microsoft-azure
```

```php
// config/services.php — add
'azure' => [
    'client_id'     => env('AZURE_CLIENT_ID'),
    'client_secret' => env('AZURE_CLIENT_SECRET'),
    'redirect'      => env('AZURE_REDIRECT_URI'),
    'tenant'        => env('AZURE_TENANT_ID'),
],
```

**2. Add routes** (OIDC):

```php
// routes/web.php — inside guest middleware group
Route::get('/auth/redirect', function () {
    return Socialite::driver('azure')->redirect();
})->name('auth.redirect');

Route::get('/auth/callback', function () {
    $azureUser = Socialite::driver('azure')->user();

    // 1. Keep existing local accounts working (break-glass).
    // 2. Find or create the local user by azure_id => email.
    $user = \App\User::firstOrCreate(
        ['azure_id' => $azureUser->getId()],
        [
            'username'   => $azureUser->getEmail(),
            'name'       => $azureUser->getName() ?? $azureUser->getEmail(),
            'email'      => $azureUser->getEmail(),
            'is_active'  => true,
        ]
    );

    if (! $user->is_active) {
        abort(403, 'Your account has been deactivated.');
    }

    // 3. Enforce authorization (roles) here — see F.5.
    \Auth::login($user);
    return redirect('/dashboard');
})->name('auth.callback');
```

**3. Make Filament login SSO-first:** Adjust `app/Filament/Auth/Login.php` (or `routes/auth.php`) so the login page shows "Sign in with Kenya Railways" as primary, with local login retained as fallback.

#### F.5 Role mapping (Entra groups → application roles)

Create security groups in Entra ID (recommended naming) and map them in the SSO callback / `AuthServiceProvider`:

| Entra security group | Local `role_code` | Flags |
|---|---|---|
| `KR-ICT-Admins` | `super_admin` | `is_super_admin=true`, `is_hq=true` |
| `KR-Operations-HQ` | `hq_admin` | `is_hq=true` |
| `KR-Crew-Admins` | `crew_admin` | — |
| `KR-Station-Officers` | `station_officer` | — |
| `KR-Booking-Officers` | `booking_officer` | — |

> **Recommended:** request the **group membership claim** (`groups`) or app roles claim in the Entra app registration, receive them in the ID token, and map them after `firstOrCreate`. Never rely on client-side claims for authorization — enforce server-side.

Default: a user who authenticates but belongs to no mapped group gets **no role** (least privilege) and must be approved before access.

#### F.6 MFA & Conditional Access (enforced at IdP)

- Require MFA for all KR staff sign-ins via an Entra Conditional Access policy targeting the app.
- Methods: Microsoft Authenticator push / TOTP.
- Service accounts and automation must use **certificate-based** or managed-identity auth — do not add them to MFA exemptions casually.

#### F.7 Rollout, testing & break-glass

1. Deploy with SSO **disabled** behind an env flag (`SSO_ENABLED=true`) to all environments first.
2. Backfill `azure_id` for target pilot group; test pilot (e.g., ICT team) sign-in.
3. Verify MFA, group-based role assignment, and audit logging (the SSO callback `firstOrCreate`/role-mapping is *not* automatically audited — add explicit `AuditLogger::record('login' / 'role_synced', ...)` calls in the callback).
4. Gradually enable for all staff; keep local login active for **break-glass**.
5. Rotate client secret on a schedule (recommend 12 months) in the enterprise vault.
6. Break-glass procedure during IdP outage: use `superadmin` local account (Section 9.10).

---

_Document version 1.4 — Kenya Railways Corporation — ICT Division_
