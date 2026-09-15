# SDLC 02 — Terms of Reference (ToR)

**Project:** MGR Crew Management System (CMS) — KR Crew & Running-Room Management System
**Prepared by:** **Date:** **Version:** 0.2 (updated — aligned with URD v1.0)

> Status: **PARTIALLY DRAFTED** — objectives and scope below are drafted from the
> delivered system and aligned to URD v1.0 (§5 UR/OR requirements); business-approval
> fields are **TO BE COMPLETED BY BUSINESS**.

---

## 1. Background

Kenya Railways staff (crew) booking, running-room occupancy and matters-arising
processes are being consolidated into a single secure web platform. The business
requirements are defined in the **URD v1.0** — see
[`01-MGR-CMS User Requirements Document.md`](01-MGR-CMS%20User%20Requirements%20Document.md).
The system is delivered as a Laravel 11 / Filament 4 application and currently
supports crew records, daily statuses, running rooms, matters, reports,
notifications, audit logging, break-glass and maintenance-mode access.

## 2. Objectives (drafted)

The business objectives in this ToR map to §3 of the [URD v1.0](01-MGR-CMS%20User%20Requirements%20Document.md):

1. Digitise crew on/off-duty booking (UR-1) into a single, role-based system.
2. Reliable rest tracking (10 h away / 12 h at home depot) with automatic check-out
   and notifications (UR-3, OR-4).
3. Real-time crew status visibility for supervisors and controllers (UR-2, OR-3).
4. Complete audit trail for user, crew, access and maintenance events (UR-6, OR-7).
5. Secure, recoverable access: break-glass, maintenance lockdown, credential rotation.
6. Decision-support reporting across Operations, Workforce, Matters and Governance
   (UR-8, UR-11).

**TO BE COMPLETED BY BUSINESS** — add management objectives, e.g. target adoption
dates, reduction targets for paper/Excel processes, defined KPIs.

## 3. Scope summary

- **Requirements baseline:** the UR-1…UR-12 and OR-1…OR-8 requirements in §5 of the
  [URD v1.0](01-MGR-CMS%20User%20Requirements%20Document.md).
- **In scope (delivered):** crew database & status registers, rosters/shifts,
  running rooms & rest countdown, matters register, reports, users/roles/permissions,
  audit trail, secure access (SSO-ready, break-glass, maintenance mode).
- **Out of scope (explicitly):** AD/Entra ID SSO, SMS notifications, driver train
  schedules, automated status changes from schedules, training-gap analysis — tracked
  as V2 roadmap in SYSTEM_DOCUMENTATION.md §10.
- **Leave/absence management:** handled via status codes only; not a full HR leave engine.

## 4. Governance & methodology

- Delivery: incremental sprints; release notes from the change history in
  SYSTEM_DOCUMENTATION.md (v1.0 → v1.6).
- Environments: Development → Test → Production (Ubuntu 24.x) — see
  `12-Server-Environment-Requirements.md`.
- Approval gates at: System Analysis sign-off, UAT sign-off, Commissioning sign-off.

**TO BE COMPLETED BY BUSINESS** — project manager, steering committee, reporting cadence.

## 5. Roles & responsibilities (RACI — TO BE COMPLETED)

| Activity | Sponsor | PM | Business | Dev | QA/Lab | Ops/ICT |
|---|---|---|---|---|---|---|
| Approve scope | | | | | | |
| System Analysis | | | | | | |
| Build & test | | | | | | |
| UAT | | | | | | |
| Training | | | | | | |
| Commissioning | | | | | | |

## 6. Resources, budget & timelines (TO BE COMPLETED)

| Item | Detail |
|---|---|
| Budget reference | |
| Timeline (start → go-live) | |
| Team / roles | |
| Servers (Dev/Test/Prod) | See `12-…` |
| Dependencies / stakeholders | See SYSTEM_DOCUMENTATION.md §3.2 |

## 7. Change & risk control

- Technical risks identified from codebase (feed into Project Charter risk register):
  - No automated test suite currently in the repository (`tests/` empty, no PHPUnit).
  - Cron webhook token (`config('cron.token')`) has no shipped config file — webhook
    returns 401 until `CRON_TOKEN` is set in production/Test.
  - `attendant` and `admin`-named roles are referenced by code but not seeded; must be
    created via the admin console where used.
  - Defaults `superadmin/superadmin123` and maintenance passphrase `n0!!Pass10` must be
    overridden outside development.
- Formal change control: **TO BE COMPLETED BY BUSINESS** (CAB process, CR register).

## 8. Sign-off (TO BE COMPLETED)

| Role | Name | Signature | Date |
|---|---|---|---|
| Business sponsor | | | |
| Project manager | | | |
| ICT / Infrastructure | | | |
| Quality / QA | | | |

---
*Next: [03 — System Analysis](03-System-Analysis.md)*