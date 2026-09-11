# SDLC 07 — UAT Plan & Test Cases

**Project:** KR Crew & Running-Room Management System
**Status:** PLAN + INVENTORY DRAFTED FROM CODEBASE (execute in **Test** environment)
**Execution & sign-off:** **TO BE COMPLETED BY BUSINESS/QA**

---

## 1. Objectives & scope

- Verify the functional requirements in `03-System-Analysis.md` behave as specified in
  the **Test** environment (see `12-Server-Environment-Requirements.md`).
- Confirm business workflows in `05-Blueprint-Process-Flows.md` are acceptable.
- Record defects (priority: High/Med/Low) and obtain sign-off.

**Out of scope:** unit/automated testing (no suite yet — open item), performance
benchmarking, V2 features.

## 2. Test environment & data

- **Server:** Test environment (Ubuntu 24.x), restored to a known baseline.
- **Credentials:** dedicated UAT users per role (booking officer, station officer,
  HQ admin, super admin, maintenance, break-glass); use real-ish depot data (ELD, HQ).
- **Test data prep:** seed one depot with crew, rooms with beds, designations, a past
  running-room stay and a matter (to test lists, reports, auto-check-out).

## 3. Roles & responsibilities (TO BE COMPLETED)

| Name | Role in UAT | Organisation |
|---|---|---|
| | UAT coordinator | |
| | Business testers (booking officers, attendants) | |
| | QA | |
| | Dev fix support | |

## 4. Test-case inventory

> Reference FR IDs from `03-System-Analysis.md`. Record result = Pass / Fail / Blocked.

### 4.1 Authentication & access (AUTH / RBAC)

| ID | Scenario | Expected | FR |
|---|---|---|---|
| AUTH-01 | Login with email | Session created; no error | AUTH-1 |
| AUTH-02 | Login with username | Session created | AUTH-1 |
| AUTH-03 | Wrong password ×5 on `/mysql/login` | 429 with minutes remaining | AUTH-4 |
| AUTH-04 | Inactive user login | Generic invalid-credentials message | AUTH-7 |
| AUTH-05 | rsf-style booking officer opens `/admin` | Entry allowed (has perms) but **no national dashboard widgets** | RBAC-3 |
| AUTH-06 | User with zero permissions opens `/admin` | Blocked (canAccessPanel fails) | AUTH-4 |
| AUTH-07 | Global (HQ/superadmin) dashboard shows admin widget set | Present | AUTH-4 |
| AUTH-08 | Hub tile shows "Admin Center" for global, "Operations Console" for permissioned depot user | Correct labels | RBAC-3 |
| AUTH-09 | Break-glass when disabled | Generic failure + audit `break_glass_login_failed` | AUTH-5 |
| AUTH-10 | Break-glass enabled: short/long justification rejected; valid accepted; 8 h session; expiry page after window | As expected | AUTH-5 |

### 4.2 Crew (CREW)

| ID | Scenario | Expected | FR |
|---|---|---|---|
| CREW-01 | Change member status → persisted across `crew_records`/history/segments | Reflected on reload | CREW-1/2 |
| CREW-02 | End-of-day copy: run command with today's segment absent | Today's segment copied from yesterday (sort 100, "Auto-copied") | CREW-3 |
| CREW-03 | End-of-day copy: today's segment exists | Skipped (no duplicate) | CREW-3 |
| CREW-04 | Edit a day older than edit window | Rejected (2-day window) | CREW-4 |

### 4.3 Running rooms (RR)

| ID | Scenario | Expected | FR |
|---|---|---|---|
| RR-01 | Check-in unknown staff number | 422 "No crew member found…" | RR-1 |
| RR-02 | Check-in inactive member | 422 not active | RR-1 |
| RR-03 | Check-in with non-room-eligible designation (e.g. LIO) | 422 not eligible | RR-1/RR-6 |
| RR-04 | Check-in shunter/PSA | Allowed; **no** rest status, room attendance only | RR-4/RR-6 |
| RR-05 | Check-in rest-eligible member (home depot room) | Status `R`, restStarted set, segment written | RR-4 |
| RR-06 | Check-in same member twice while active | 422 already checked in | RR-1 |
| RR-07 | Occupancy: fill all beds | Next check-in 422 capacity | RR-2 |
| RR-08 | Check-out manually | Status `out`, departure set, crew → `SB` | — |
| RR-09 | Auto-check-out: guest at home depot with restStarted 12 h ago | Auto `out`, `rrCheckedOut=true`, crew → SB, notifications sent | RR-5 |
| RR-10 | Auto-check-out: away-depot guest (10 h rule) | Auto `out` after 10 h | RR-5 |
| RR-11 | Delete active record | Also performs crew check-out sync | — |
| RR-12 | Matter create | Ticket `MTR-YYYY-NNNN` assigned, status `open` | RR-7 |
| RR-13 | Matter resolve/reopen | `resolved_date` set then cleared on reopen | RR-7 |
| RR-14 | Matter photos upload/reject | Images ≤5 MB accepted; other files rejected | RR-7 |
| RR-15 | Beds update below current occupancy | Warning returned; occupancy preserved | RR-8 |

### 4.4 Reports (REP)

| ID | Scenario | Expected | FR |
|---|---|---|---|
| REP-01 | Portal reports render with seeded data | Each export renders | REP-1 |
| REP-02 | Decision-support reports list (13) | All 13 present for global user | REP-3 |
| REP-03 | Governance reports for depot (non-global) user | 403/absent | REP-4 |
| REP-04 | Report builder with misconfigured columns | 422 validation | REP-2 |
| REP-05 | Printable report with photo matter ≤2 MB | Photo embedded; oversized skipped with note | REP-5 |
| REP-06 | Depot user sees only own depot rows in operational report | Scoped | REP-4 |

### 4.5 Notifications (NOT)

| ID | Scenario | Expected | FR |
|---|---|---|---|
| NOT-01 | Trigger auto-check-out | In-app row + e-mail (if SMTP enabled) with `email_delivered_at` stamped | NOT-2/3 |
| NOT-02 | Read notifications | Unread count decreases; mark-read idempotent | NOT-1 |
| NOT-03 | `notifications:test-email` | Mail arrives | NOT-2 |

### 4.6 Security operations (SEC)

| ID | Scenario | Expected | FR |
|---|---|---|---|
| SEC-01 | Activate maintenance with lockdown | All sessions/remember tokens revoked; operator redirected to `/maintenance-login` | SEC-3 |
| SEC-02 | Activate maintenance w/o scheduled end | Site stays down; banner shows "No scheduled end" | SEC-3 |
| SEC-03 | Scheduled end in past + scheduler run | Auto-deactivate + audit `maintenance_deactivated` | SEC-5 |
| SEC-04 | Scheduled end in future + scheduler run | Remains down | SEC-5 |
| SEC-05 | Non-maintenance account on `/maintenance` | Rejected/redirected | SEC-3 |
| SEC-06 | Rotation `maintenance:rotate --update-env` | Password changes, env updated, audit `maintenance_credentials_rotated` | SEC-4 |
| SEC-07 | Ordinary form w/o CSRF token | 419 (still protected) | SEC-7 |
| SEC-08 | Audit log captures a user edit (personally-identifiable data) without password values | `actor_username`/diff recorded; passwords redacted | SEC-1/2 |
| SEC-09 | Audit prune dry run | No rows younger than retention removed | SEC-6 |

## 5. Defect log (TO BE COMPLETED DURING EXECUTION)

| ID | TC ref | Severity | Description | Reporter | Status |
|---|---|---|---|---|---|
| | | | | | |

## 6. Entry / exit criteria

- **Entry:** Test environment baseline restored; test data ready; UAT users provisioned.
- **Exit:** All High/Med cases Pass or signed-off with agreed workaround; defects logged.

## 7. UAT sign-off sheet

| Area | Tester | Date | Result | Sign-off |
|---|---|---|---|---|
| Authentication & access | | | | |
| Crew | | | | |
| Running rooms | | | | |
| Reports | | | | |
| Notifications | | | | |
| Security operations | | | | |

| Role | Name | Signature | Date |
|---|---|---|---|
| Business UAT lead | | | |
| QA | | | |
| Dev lead | | | |

---
*Next: [08 — Training](08-Training.md)*