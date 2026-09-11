# SDLC 05 — Blueprint & Process Flows

**Project:** KR Crew & Running-Room Management System
**Status:** **DRAFTED FROM CODEBASE** — flows below mirror implementation
(`file:line` anchors). Diagrams are Mermaid (render on GitHub; also printable).

---

## 1. Blueprint overview

Top-level flow of how the system is operated, from identity to daily running.

```mermaid
flowchart LR
    A[User logs in] --> B{Which portal?}
    B -->|Crew / Running Rooms web app| C[Roles routed via canAccessCrewSystem / canAccessRunningRooms]
    B -->|Admin panel /admin| D[Filament panel - canAccessPanel]
    C --> E[Daily operations: statuses, running rooms, matters]
    D --> F[Config: users, depots, rooms, reports, roles]
    E --> G[Auto tasks: end-of-day copy, auto-checkout, maintenance, audit prune]
    F --> G
    G --> H[Notifications & reports]
```

## 2. Access & identity flow

1. Credentials resolved by **email or username**; password verified with bcrypt.
2. On success: session regenerated, `last_login_at` stamped.
3. Portal routing:
   - Crew System: any active user.
   - Running Rooms: `booking_officer`/`station_officer`/global.
   - Admin Center (Administration & national dashboard): **global access** only.
   - Operations Console: non-global users with ≥1 panel permission.
4. Failed attempts: generic message; `/mysql/login` rate-limited 5/60 s.

```mermaid
flowchart TD
    L[Login form] --> V{both email & username checked}
    V -->|match + bcrypt ok| S[Session + last_login_at]
    V -->|fail| G[Generic invalid-credentials message]
    S --> P{Portal}
    P -->|/admin| A{canAccessPanel?}
    A -->|global or has permission| D[Dashboard global widgets only if isGlobalAccess]
    A -->|no| X[403 / redirect]
    P -->|running-rooms| R{canAccessRunningRooms?}
    R -->|yes| W[Room console scoped to visible rooms]
```

## 3. Crew daily-status flow

1. Booking officer opens crew SPA and works on **today's** status grid for the depot.
2. Changing status writes: `crew_records.payload`, normalized `crew_status_history`,
   and a closed/open segment in `crew_status_segments` (rest entries carry
   `restStarted`, `awayDepot`, `monthly[day]`).
3. **End-of-day copy** (`crew:copy-end-of-day-status`, 00:01): if today's segment is
   absent, copy yesterday's last status into today (`sort_order=100`, note
   "Auto-copied…"); fallback to `payload.monthly[day]`.
4. Past-day edits allowed within `CREW_PAST_DAY_EDIT_WINDOW_DAYS` (2 days).

```mermaid
sequenceDiagram
    participant O as Booking Officer
    participant W as Crew SPA (/crew-*)
    participant DB as crew_records / status tables
    O->>W: Update status for member/day
    W->>DB: Save payload + append status history + segment
    Note over DB: dailyAt 00:01 - crew:copy-end-of-day-status
    DB-->>W: Today's segment pre-filled from yesterday
```

## 4. Running-room check-in / rest / auto-check-out flow

1. Attendant/booking officer checks in crew by **staff number**.
2. Validations: room manageable; member active; designation **room-eligible**; not
   already checked in; bed available (`B1..Bn`); capacity free that day.
3. On success, if designation is **rest-eligible**:
   - Crew status → `R`; `restStarted` = arrival (UTC); `awayDepot` set when room depot
     ≠ crew depot; segment recorded "Running room: …".
   - Non-rest-eligible (shunter, PSA): recorded as in-room only, no `R` status.
4. **Rest duration:** 12 h at home depot, 10 h away.
5. **Auto-check-out** (`running-rooms:auto-checkout-rested`, every 5 min): any `in`
   guest whose `restStarted + restHours` has passed is checked out (`out`),
   `rrCheckedOut=true`; crew returned to `SB`; notifications sent.
6. Manual check-out / deletion also performs the crew sync.

```mermaid
flowchart TD
    A[Staff number scanned] --> B{Member found & active?}
    B -->|no| E[422 - not found / inactive]
    B -->|yes| C{Room-eligible designation?}
    C -->|no| E2[422 - designation not room-eligible]
    C -->|yes| D{Already in? / bed free? / capacity?}
    D -->|fail| E3[422 - already checked in / full / no bed]
    D -->|ok| F[Check-in status in]
    F --> G{Rest-eligible?}
    G -->|yes| H[Status R + restStarted + segment]
    G -->|no| I[Room attendance only, no rest]
    H --> J[Auto-checkout every 5 min]
    I --> J
    J -->|restStarted + 10/12h passed| K[Check-out to SB + notify HQ & booking officers]
```

## 5. Matters arising flow

1. Create matter against a room: category, description, reporter (default current
   user), optional photos (≤5 MB, images only).
2. System assigns ticket **`MTR-YYYY-NNNN`** (per-year sequence) and status `open`.
3. Update allowed: rescue→`open`, resolve→`resolved` (records `resolved_date`, kept on
   further edits, cleared if reopened).
4. Printable matters report: date/status/room/category filters, per-room/per-category
   stats, photos embedded ≤2 MB.

```mermaid
stateDiagram-v2
    [*] --> open : create (MTR-YYYY-NNNN assigned)
    open --> resolved : mark resolved
    resolved --> open : reopen (resolved_date cleared)
    open --> [*] : delete (files removed)
    resolved --> [*] : delete
```

## 6. Report & access flow

1. Portal reports and decision-support index (`/reports/system`) list enabled reports.
2. Visibility scoping: inactive user → none; global → all depots; depot user → own
   depot; attendant → own room.
3. Governance reports (Secure Access, PII Audit, Login Security) → `isGlobalAccess()`
   only.
4. Filters (date ranges, depots, status) → generated rows; printable KR-letterhead HTML.

## 7. Notification flow

1. Event (auto-check-out) → persist `system_notifications` row (recipient, type, title,
   body, data).
2. Best-effort e-mail via SMTP when `notifications.email.enabled=true`; success stamps
   `email_delivered_at`.
3. In-app: bell lists latest 50, unread count, mark-read.
4. Admin alerts e-mailed to `ALERT_EMAIL_RECIPIENTS`.

## 8. Maintenance / break-glass flow

1. **Maintenance:** control page `/maintenance` (maintenance credentials) → activate
   (optional scheduled end, `Africa/Nairobi`) → hard lockdown wipes all sessions +
   remember tokens, operator re-signs in via `/maintenance-login`; banner shown.
   Scheduled end → `maintenance:auto-deactivate` (every minute) brings site back.
2. **Break-glass:** enabled only when configured; requires username+password +
   justification (20–2000 chars); gives 8 h session as `login_as` target; auto-expired
   by middleware with audit entries.

```mermaid
flowchart TD
    A[Maintenance requested] --> B[Activate via /maintenance]
    B --> C{lockdown? default yes}
    C -->|yes| D[Revoke all sessions + tokens]
    C -->|no| E[Issue bypass cookie 12h]
    D --> F[Operator logs in via /maintenance-login]
    F --> G[Scheduled end passed?]
    G -->|yes, scheduler runs| H[Auto-deactivate + audit]
    G -->|manual| I[Deactivate via /maintenance]
```

## 9. Scheduled tasks (blueprint of the automation layer)

| Task | Cadence | Command |
|---|---|---|
| End-of-day status copy | daily 00:01 | `crew:copy-end-of-day-status` |
| Running-room auto-check-out | every 5 min | `running-rooms:auto-checkout-rested` |
| Maintenance auto-deactivate | every min | `maintenance:auto-deactivate` |
| Audit log retention | daily | `audit:prune --retention=365` |
| Webhook fallback | 5 min (external) | `GET /running-rooms/cron/auto-checkout?token=` |

---

## Appendix A — Process-flow review sign-off (TO BE COMPLETED)

| Reviewer | Area reviewed | Findings | Sign-off | Date |
|---|---|---|---|---|
| | Crew status flow | | | |
| | Running rooms flow | | | |
| | Matters flow | | | |
| | Reports / access flow | | | |
| | Maintenance / break-glass | | | |

---
*Next: [06 — System Architecture](06-System-Architecture.md)*