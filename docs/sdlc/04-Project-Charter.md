# SDLC 04 — Project Charter

**Project:** KR Crew & Running-Room Management System
**Prepared by:** **Date:** **Version:** 0.1 (template)

> **Status: TO BE COMPLETED BY BUSINESS** — this is a template. Fill in project
> governance facts; the technical baseline is available in `03-System-Analysis.md`.

---

## 1. Charter summary

| Field | Detail |
|---|---|
| Project name | KR Crew & Running-Room Management System |
| Sponsor | *(to fill)* |
| Project manager | *(to fill)* |
| Business owner | *(to fill)* |
| Start date / planned go-live | *(to fill)* |
| Budget reference | *(to fill)* |

## 2. Justification & business case

**TO BE COMPLETED BY BUSINESS** — reference `01-User-Request.md` and `02-Terms-of-Reference.md`.

## 3. Objectives & success criteria

**TO BE COMPLETED BY BUSINESS** — measurable, e.g.:
- 100% of depots recording daily crew status in the system by `<date>`.
- Running-room occupancy visible centrally in real time.
- Matters logged with ticket numbers and closure rate target `<n>%`.

## 4. Scope

- In scope / out of scope: as per `02-Terms-of-Reference.md` §3.

## 5. Deliverables & milestones

| Phase | Deliverable | Owner | Target date |
|---|---|---|---|
| Discovery | ToR + System Analysis signed off | | |
| Build | Code migrated & hardened (v1.6 baseline) | Dev | |
| Environments | Dev / Test / Prod provisioned (Ubuntu 24.x) | ICT | |
| Test | UAT executed & signed off | QA/Business | |
| Training | Training delivered (per depot) | Business/ICT | |
| Commissioning | Go-live + post-implementation review | PM | |

## 6. Team

| Role | Name | Responsibility |
|---|---|---|
| Project manager | | |
| Developer(s) | | |
| QA / testers | | |
| Business analysts | | |
| ICT infrastructure | | |
| Change/training lead | | |

## 7. Budget

| Item | Estimate | Approved |
|---|---|---|
| Servers (Dev/Test/Prod) | See `12-Server-Environment-Requirements.md` | |
| Licences / tools | | |
| Training & rollout | | |
| Support (post go-live) | | |

## 8. Risks & issues (register)

> Seed risks from `02-Terms-of-Reference.md` §7 (no automated tests; webhook token;
> unseeded attendant role; default credentials). **TO BE COMPLETED BY BUSINESS**:

| # | Risk | Likelihood | Impact | Mitigation | Owner |
|---|---|---|---|---|---|
| 1 | No automated test suite in repo | High | Med | Add PHPUnit + CI | Dev |
| 2 | Cron webhook locked if `CRON_TOKEN` unset | Med | Med | Configure in Test/Prod | ICT |
| 3 | Default credentials used in production | Med | High | Enforce rotation & env overrides | ICT/Dev |
| 4 | (add) | | | | |

## 9. Communication & reporting

**TO BE COMPLETED BY BUSINESS** — cadence, channels, steering committee.

## 10. Approvals

| Role | Name | Signature | Date |
|---|---|---|---|
| Sponsor | | | |
| PM | | | |
| ICT | | | |
| Finance | | | |

---
*Next: [05 — Blueprint & Process Flows](05-Blueprint-Process-Flows.md)*