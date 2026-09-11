# SDLC 10 — System Manuals

**Project:** KR Crew & Running-Room Management System
**Status:** INDEX — manuals already drafted in the repository are **Drafted**;
remainder **TO BE COMPLETED**.

---

## 1. Manual index

| Manual | Covered in | Status |
|---|---|---|
| **User Guide** (web app + admin panel) | `../SYSTEM_DOCUMENTATION.md` §4 (Getting started, workflows, permissions, how-tos, troubleshooting) | Drafted |
| **Developer Guide** | `../SYSTEM_DOCUMENTATION.md` §5 (stack, repo structure, local setup, build/CI, config, API, data model, security, testing, conventions) | Drafted |
| **System Architecture** | `../SYSTEM_DOCUMENTATION.md` §6 + `06-System-Architecture.md` | Drafted |
| **Operations & Support Manual** | `../SYSTEM_DOCUMENTATION.md` §7 (monitoring, logging, incident, backup/DR) | Drafted |
| **Security Manual** | `../SYSTEM_DOCUMENTATION.md` §8–9 (data classification, access control, audit, break-glass, SSO/roadmap) | Drafted |
| **Deployment Manual** | `../DEPLOYMENT_GUIDE.md` | Drafted |
| **Scheduled Tasks Manual** | `../scheduled-tasks.md` + §11 Appendices | Drafted |
| **Database Manual (schema)** | `../SYSTEM_DOCUMENTATION.md` Appendix D + `03-System-Analysis.md` §5 | Drafted |
| **Field/user quick-reference cards** (check-in, rest, matters, maintenance) | From `05-Blueprint-Process-Flows.md` §4–5 | TO BE COMPLETED |
| **Super-user / admin runbook** (credential rotation, maintenance ops) | `DEPLOYMENT_GUIDE.md` §7 + `11-Credentials.md` | TO BE COMPLETED |

## 2. Manual maintenance rules

1. Every release updates the relevant manual(s) and the changelog in
   SYSTEM_DOCUMENTATION.md §1.
2. Manuals live with the code in the repo (single source of truth).
3. Sign-off of manuals happens at Commissioning (`09-Commissioning.md`).

## 3. Release sign-off (TO BE COMPLETED)

| Release | Manuals updated | Reviewer | Date |
|---|---|---|---|
| v1.6 baseline | | | |

---
*Next: [11 — Credentials Register](11-Credentials.md)*