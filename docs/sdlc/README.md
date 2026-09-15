# KR Crew System — SDLC Document Pack

**Project:** MGR Crew Management System (CMS) — KR Crew & Running-Room Management System (Kenya Railways)
**Repo:** `github.com/Lukewilson-1/kr-crew-system` — branch `master`
**Pack created:** 2026-09-11 · Based on codebase state (docs v1.6, `d86c4a5b`)
**URD v1.0 added:** 2026-09-14 (business review draft)

This folder contains the official SDLC process documents. Documents whose content
could be drafted from the codebase are marked **"Drafted from codebase"** in their
header. Documents requiring business input contain section-level placeholders marked
**"TO BE COMPLETED BY <owner>"** — fill these in during the relevant phase.

## Document index (lifecycle order)

| # | Document | Status | Source of truth in repo |
|---|----------|--------|-------------------------|
| 01 | [User Requirements (MGR-CMS)](01-MGR-CMS%20User%20Requirements%20Document.md) | **Draft for Review** (v1.0) | URD — business (challenges, objectives, UR/OR requirements) |
| 02 | [Terms of Reference](02-Terms-of-Reference.md) | Draft | User Requirements + System Analysis |
| 03 | [System Analysis](03-System-Analysis.md) | **Drafted from codebase** | `app/`, `routes/`, migrations, SYSTEM_DOCUMENTATION.md |
| 04 | [Project Charter](04-Project-Charter.md) | Template | — (business) |
| 05 | [Blueprint & Process Flows](05-Blueprint-Process-Flows.md) | **Drafted from codebase** | Controllers, Kernel schedule, observers |
| 06 | [System Architecture](06-System-Architecture.md) | **Drafted from codebase** | composer.json, config, deployment guide |
| 07 | [UAT (incl. test-case inventory)](07-UAT.md) | Draft plan + inventory | Controllers, business rules documented in §3/§5 |
| 08 | [Training](08-Training.md) | Template | SYSTEM_DOCUMENTATION.md §4 |
| 09 | [Commissioning (Go-Live)](09-Commissioning.md) | Template + checklist | DEPLOYMENT_GUIDE.md |
| 10 | [System Manuals](10-System-Manuals.md) | Index | SYSTEM_DOCUMENTATION.md |
| 11 | [Credentials Register](11-Credentials.md) | Template (dev defaults only) | config files + seeders |
| 12 | [Server Environment Requirements (Dev/Test/Prod)](12-Server-Environment-Requirements.md) | **Drafted** | composer.json, requirements, Ubuntu 24.x |
| 13 | [Official ICT Request Note](13-Official-ICT-Request-Note.md) | Template | — (to submit) |
| 14 | [System Implementation Design (SID v1.1)](14-System-Implementation-Design-SID.md) | **Completed against KR template** | Template v1.1 + this pack (full 11-section submission with Appendices A–F) |

## Companion documents (outside this pack)

- `../SYSTEM_DOCUMENTATION.md` — master system documentation (v1.6): user guide,
  developer guide, architecture, security, appendices.
- `../DEPLOYMENT_GUIDE.md` — deployment, environments, maintenance rotation.
- `../scheduled-tasks.md` — scheduled tasks and cron webhook reference.
- `../disaster-recovery.md` — DR runbook (MySQL replication, semi-annual drill,
  failover/fallback); scripts in `scripts/mysql/`.

## Version control of this pack

Keep this pack in sync with the codebase. When a release changes behaviour:

1. Update the relevant documents (§ with **Drafted from codebase**).
2. Update the UAT inventory and re-run affected cases.
3. Record the change in the change tracker of `02-Terms-of-Reference.md`.