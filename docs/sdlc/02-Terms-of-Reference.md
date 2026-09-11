# SDLC 02 — Terms of Reference (ToR)

**Project:** KR Crew & Running-Room Management System
**Prepared by:** **Date:** **Version:** 0.1 (draft)

> Status: **PARTIALLY DRAFTED** — objectives and scope below are drafted from the
> delivered system; business-approval fields are **TO BE COMPLETED BY BUSINESS**.

---

## 1. Background

Kenya Railways staff (crew) booking, running-room occupancy and matters-arising
processes are being consolidated into a single secure web platform: **KR Crew System**
(CM & RM System). The system is delivered as a Laravel 11 / Filament 4 application and
currently supports crew records, daily statuses, running rooms, matters, reports,
notifications, audit logging, break-glass and maintenance-mode access.

## 2. Objectives (drafted)

1. Single, role-based system for crew and running-room operations across depots.
2. Reliable rest tracking (10 h away / 12 h at home depot) with automatic check-out
   and notifications.
3. Complete audit trail for user, crew, access and maintenance events.
4. Secure, recoverable access: break-glass, maintenance lockdown, credential rotation.
5. Decision-support reporting across Operations, Workforce, Matters and Governance.

**TO BE COMPLETED BY BUSINESS** — add management objectives, e.g. target adoption
dates, reduction targets for paper/Excel processes, defined KPIs.

## 3. Scope summary

- **In scope (delivered):** see §3 "Requested scope" of `01-User-Request.md`.
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