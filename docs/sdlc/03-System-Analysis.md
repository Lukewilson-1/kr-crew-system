# SDLC 03 — System Analysis

**Project:** KR Crew & Running-Room Management System
**Status:** **DRAFTED FROM CODEBASE** (code state v1.6, 2026-09-11)
**Boilerplate note:** all functional statements below are verifiable in the code
(`file:line` anchors where useful). Statements marked *[verify]* need UAT evidence.

---

## 1. Purpose & scope of analysis

This document describes the **as-delivered** system in terms a reviewer can verify:
functional requirements, user classes, data, interfaces, and non-functional
requirements. It is the baseline against which UAT (`07-UAT.md`) and commissioning
(`09-Commissioning.md`) are executed.

## 2. System summary

Laravel 11 application (PHP ^8.2, Filament 4 admin panel) providing:
1. **Crew module** — records, roster, daily/monthly status registers, end-of-day copy.
2. **Running rooms module** — check-in/out with bed management, rest timing, occupancy,
   matters arising, auto-check-out.
3. **Admin/Filament module** — users, depots, regions, designations, status codes,
   shift templates, train types, rooms, rest/duty-rosters, reports, permissions, roles.
4. **Reports** — classic portal reports + 13 built-in decision-support reports.
5. **Notifications** — in-app bell + email for auto-check-out events.
6. **Security operations** — audit log, break-glass, maintenance mode, credential rotation.

## 3. User classes & roles

| Role code | Description | Key abilities (permissions) |
|---|---|---|
| `super_admin` | Full system control | All permissions |
| `hq_admin` | HQ operations | Global data visibility |
| `station_officer` | Station operations | `manage_running_rooms`, `manage_duty_rosters`, `manage_rest_locations` |
| `booking_officer` | Crew booking & registers | `manage_running_rooms`, `manage_duty_rosters`, `manage_rest_locations` |
| `crew_admin` | Crew maintenance & lookup | `manage_duty_rosters` |
| `attendant`¹ | Running-room attendant (own room only) | Room-scoped console |
| `admin`¹ | Room-depot administrator | `isRoomAdmin()` |

¹ Referenced by code (`User::isAttendant()/isRoomAdmin()`) but **not seeded** — create
via admin console where used.

Global access (`isGlobalAccess()`): `is_super_admin` OR `is_hq` OR `role_code=hq_admin`
OR `depot_code=HQ`. Governance reports (Secure Access, PII Audit, Login Security) are
restricted to `is_active && isGlobalAccess()`.

## 4. Functional requirements (FR)

### 4.1 Authentication & access (FR-AUTH)

| ID | Requirement | Status |
|----|-------------|--------|
| AUTH-1 | Login by **email or username**, password checked via bcrypt (`User::passwordMatches()`) | Implemented |
| AUTH-2 | Legacy in-app `POST /mysql/login` accepts username+password, rate-limited 5/60 s per `username|ip` (429 with minutes remaining) | Implemented |
| AUTH-3 | Session regenerated on login; remember-me token optional; `NoStore` cache headers on admin panel | Implemented |
| AUTH-4 | Admin panel (`/admin`) reachable only by `is_active` accounts with global access or ≥1 panel permission (`canAccessPanel()`) | Implemented |
| AUTH-5 | Break-glass access: disabled by default; enabled → username+password (bcrypt compare), justification 20–2000 chars, 8 h session; events audited (`break_glass_login`, `_failed`, `_logout`, `_expired`) | Implemented |
| AUTH-6 | Maintenance mode sign-in (`/maintenance-login`) works only while site is down; maintenance credentials; announces and issues bypass cookie | Implemented |
| AUTH-7 | Failed auth is deliberately generic ("invalid credentials") to avoid user enumeration | Implemented |

### 4.2 Roles, permissions & users (FR-RBAC)

| ID | Requirement | Status |
|----|-------------|--------|
| RBAC-1 | Roles/permissions seeded: 5 roles, 9 base permissions (add `manage_running_rooms`→ station_officer/booking_officer; `manage_duty_rosters`→ + crew_admin; `manage_rest_locations`→ station_officer/booking_officer) | Implemented |
| RBAC-2 | Permission resolution: super_admin→true; then JSON `permissions`; then `user_permissions`; then `role_permissions` via primary + extra roles | Implemented |
| RBAC-3 | Per-resource policies enforce permissions in Filament (see `app/Policies/*`; full permission map in SYSTEM_DOCUMENTATION.md Appendix E) | Implemented |
| RBAC-4 | `superadmin` user and `super_admin` role protected from edit/delete | Implemented |
| RBAC-5 | Users CRUD via admin console; depot default `HQ`, role default `booking_officer`; passwords re-hashed when plaintext | Implemented |

### 4.3 Crew module (FR-CREW)

| ID | Requirement | Status |
|----|-------------|--------|
| CREW-1 | Crew records stored per depot (`crew_records` JSON + normalized `crew_members`); staff-number lookup for running rooms | Implemented |
| CREW-2 | Daily status per crew-member day with coded statuses (BK, SB, R, L, SK, ABS, T, NTB, TO), rest segments tracked (`crew_status_segments`) | Implemented |
| CREW-3 | End-of-day copy: at 00:01, today's segment created from yesterday's last status; skipped if today already exists | Implemented |
| CREW-4 | Past-day edit window = 2 trailing days (`CREW_PAST_DAY_EDIT_WINDOW_DAYS`) | Implemented |
| CREW-5 | Shift assignment per member; history trail per change | Implemented |

### 4.4 Running rooms (FR-RR)

| ID | Requirement | Status |
|----|-------------|--------|
| RR-1 | Check-in validates: room manageable by user; crew member active & **room-eligible** (designation rule); not already checked in; bed capacity available | Implemented |
| RR-2 | Beds `B1..Bn` maintained per room; capacity integer ≥ 1; occupancy enforced; DB unique indexes prevent double check-in of same staff or bed | Implemented |
| RR-3 | Rest rule: **12 h at home depot, 10 h away** (crew depot ≠ room depot) | Implemented |
| RR-4 | Rest-eligible check-in sets crew status `R` with `restStarted`; non-eligible (e.g. shunter, PSA) records room attendance only | Implemented |
| RR-5 | Auto-check-out: every 5 min, guests whose `restStarted + restHours` has passed and who are still `in` are checked out (status `out`), crew returned to `SB`, HQ + booking officers notified | Implemented |
| RR-6 | Designation rules: drivers ⇒ rest+room; shunters ⇒ room-only; LIO/office designations ⇒ neither; PSA ⇒ room-only | Implemented |
| RR-7 | Matters: create/open→resolved with `MTR-YYYY-NNNN` ticket numbers (per-year sequence); photo uploads (jpeg/jpg/png/gif/webp ≤5 MB); printable report embeds photos ≤2 MB | Implemented |
| RR-8 | Settings: bed counts and option categories admin-gated (`isRoomAdmin`) | Implemented |
| RR-9 | Data window default: 6 rolling months across payload/matters | Implemented |

### 4.5 Reports (FR-REP)

| ID | Requirement | Status |
|----|-------------|--------|
| REP-1 | Portal reports (daily status, monthly register, utilization, absence/NTB, printable register) | Implemented |
| REP-2 | Generic report builder (columns/filters/group_by/layout), hard 500-row cap | Implemented |
| REP-3 | 13 decision-support reports in 4 categories (Operational 4, Workforce 3, Matters 3, Governance 3); governance gated to global access | Implemented |
| REP-4 | Report visibility scoped: inactive→none; global→all depots; else own depot; attendants→own room | Implemented |
| REP-5 | Printable KR-letterhead HTML with print/download | Implemented |

### 4.6 Notifications (FR-NOT)

| ID | Requirement | Status |
|----|-------------|--------|
| NOT-1 | In-app notifications (bell, latest 50, unread count, mark-read) | Implemented |
| NOT-2 | Email delivery of notifications when enabled (SMTP; `email_delivered_at` stamped); admin alerts to `ALERT_EMAIL_RECIPIENTS` | Implemented |
| NOT-3 | Auto-check-out notification to HQ admins + booking officers of crew depot & room depot | Implemented |

### 4.7 Security operations (FR-SEC)

| ID | Requirement | Status |
|----|-------------|--------|
| SEC-1 | Append-only `audit_logs` (actor, IP, UA, entity, before/after diff, metadata) with redaction of secrets | Implemented |
| SEC-2 | Audited events: login/access + `maintenance_*`, `break_glass_*`, model create/update/delete/restore | Implemented |
| SEC-3 | Maintenance control page: activate (optional scheduled end anchored `Africa/Nairobi`) / deactivate; hard **lockdown** default (all sessions + remember tokens revoked) | Implemented |
| SEC-4 | Credential rotation commands (`maintenance:rotate`, `break-glass:rotate`) with env update + audit | Implemented |
| SEC-5 | Auto-deactivate maintenance once scheduled end passes (every minute) | Implemented |
| SEC-6 | Audit retention: delete events older than `--retention` (default 365 d, min 1 d), run daily | Implemented |
| SEC-7 | CSRF exempted ONLY for `maintenance-login` + `maintenance` (passphrase-gated, audited); all other forms protected | Implemented |

### 4.8 Admin/configuration (FR-CFG)

| ID | Requirement | Status |
|----|-------------|--------|
| CFG-1 | Depots, regions, designations, status codes, shift templates, train types, rest locations, duty rosters maintained in Filament | Implemented |

## 5. Data requirements

Live database has **32 tables** (verified). Key data entities:

- **People & org:** `users`, `crew_members`, `crew_records`, `crew_shift_assignments`,
  `crew_status_history`, `crew_status_segments`, `depots`, `regions`, `designations`,
  `status_codes`, `shift_templates`, `train_types`, `rest_locations`
- **Running rooms:** `rooms`, `room_beds`, `attendance_records`, `matters`, `matter_photos`, `running_room_options`
- **Rosters:** `duty_rosters`, `duty_roster_items`
- **Security:** `roles`, `permissions`, `role_permissions`, `user_roles`, `user_permissions`,
  `audit_logs`, `password_reset_tokens`
- **Other:** `reports`, `system_notifications`, `admin_meta`, `migrations`

Data volume is small (tens-to-hundreds of rows currently); indexes exist on all hot
paths (9 indexes on `attendance_records`, 11 on `crew_status_segments`, etc.).

## 6. Interfaces

| Interface | Notes |
|---|---|
| Web UI (SPA) `/crew-*`, `/running-rooms` | auth cookie sessions |
| Admin panel `/admin` | Filament 4 |
| JSON APIs `/mysql/*`, `/running-rooms/api/*` | auth + throttle 120/min on mutations |
| Cron webhook `GET /running-rooms/cron/auto-checkout?token=` | locked (401) unless `CRON_TOKEN` configured |
| SMTP e-mail | Laravel mail (SMTP); `notifications:test-email` |
| Scheduler | `php artisan schedule:run` via system cron: 00:01 daily, every 5 min, every min, daily |

## 7. Non-functional requirements (NFR)

| ID | Requirement | Target |
|----|-------------|--------|
| NFR-1 | Availability | Business hours; maintenance mode for planned work; auto-recovery for scheduled end |
| NFR-2 | Security | TLS, bcrypt, CSRF, throttle, audit, no-store headers, RBAC, secret redaction |
| NFR-3 | Performance | Fits current data volumes; index-backed queries; recommend `APP_DEBUG=false` + OPcache in Test/Prod |
| NFR-4 | Time zones | Business time `Africa/Nairobi`; stored UTC (Laravel default) |
| NFR-5 | Backups | DB + storage (`storage/app`, `public/matter-photos`) — see `09-Commissioning.md` |
| NFR-6 | Compliance | Audit trail, data classification (§8 SYSTEM_DOCUMENTATION.md), retention ≥12 months |
| NFR-7 | Testability | **Gap:** no automated tests in repo (see open items) |

## 8. Assumptions & constraints

- Ubuntu 24.x servers preferred for Dev/Test/Prod (`12-Server-Environment-Requirements.md`).
- Production credentials and secret tokens are managed outside the repository.
- No AD/SSO yet; break-glass/maintenance provide emergency access. SSO is V2 roadmap.

## 9. Open items / gaps (tracked)

| # | Gap | Action owner |
|---|-----|--------------|
| 1 | No PHPUnit tests (`tests/` empty, no `phpunit.xml`) | Dev |
| 2 | `CRON_TOKEN` must be set in Test/Prod or webhook stays 401 | Ops |
| 3 | `attendant` role not seeded (needs admin-console creation if used) | Ops/Dev |
| 4 | Dev defaults (`superadmin/superadmin123`, maintenance `n0!!Pass10`) to be overridden outside dev | Ops |
| 5 | Automated test suite + CI to be added | Dev |

## 10. Acceptance criteria (baseline for UAT)

System is accepted when every **Implemented** FR above is demonstrably working in the
Test environment against documented scenarios in `07-UAT.md`, and open-item #2/#4 are
resolved in Test/Prod configurations.

## 11. Sign-off (TO BE COMPLETED)

| Role | Name | Signature | Date |
|---|---|---|---|
| Business analyst | | | |
| System analyst / Dev lead | | | |
| QA | | | |
| Business sponsor | | | |

---
*Next: [04 — Project Charter](04-Project-Charter.md)*