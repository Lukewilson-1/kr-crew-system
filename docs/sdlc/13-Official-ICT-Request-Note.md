# SDLC 13 — Official Request Note (to ICT / Infrastructure)

> Use this page as the official request to provision the **KR Crew System** server
> environment. Fill the bracketed `[…]` fields before submitting.

---

**TO:** ICT / Infrastructure & Systems — [Organisation name]

**FROM:** [Your name], [Role / Unit — e.g. PM, Crew Operations]

**DATE:** [DD/MM/YYYY]

**REF:** [Request reference, e.g. ICT-REQ-2026-XXX]

---

## REQUEST FOR PROVISIONING — KR CREW & RUNNING-ROOM MANAGEMENT SYSTEM

### 1. Purpose

I request the provisioning of **three (3) production-grade Linux servers** to run the
KR Crew & Running-Room Management System through its full lifecycle:
**Development, Test (UAT), and Production**.

The system centralises crew booking, running-room occupancy, rest tracking, matters
arising and reports for Kenya Railways staff operations, and is ready for controlled
deployment.

### 2. Preferred platform & environment

- **Operating system:** Ubuntu Server **24.04 LTS (x86_64)** — preferred.
- One server per environment, fully isolated:

| Env | vCPU | RAM | Disk | Purpose |
|---|---|---|---|---|
| **DEV** | 2 | 4 GB | 40 GB SSD | Developer integration & fixes |
| **TEST** | 2 | 4 GB | 60 GB SSD | UAT, training, staging |
| **PROD** | 4 | 8 GB | 80 GB SSD (+50 GB data/backups) | Live operations |

*(Full specifications and rationale: `docs/sdlc/12-Server-Environment-Requirements.md`.)*

### 3. Software stack requested to be installed

Nginx, **PHP-FPM 8.3** (with required extensions + OPcache), **MySQL 8.0**,
**Composer 2**, **Node.js 20 LTS** (npm), Git, cron scheduler. Optional on PROD:
Redis + Supervisor (async queue), monitoring agents. Let’s-Encrypt TLS on TEST & PROD.

### 4. Connectivity & security

- SSH **key-based** access for [list usernames].
- Host firewall (22 restricted; 80/443 open; 3306 closed externally).
- MySQL bound to localhost / private VLAN.
- Access via office LAN for DEV/TEST; controlled public access for PROD with TLS.

### 5. Services requested

1. Provision servers & OS hardening.
2. Install application stack per §3.
3. Network/firewall + TLS.
4. **Backups** (DB + `matter-photos`/storage); PROD daily off-site.
5. Provide connection details (hostnames/IPs, non-secret access) to:
   [developer / PM e-mail].
6. Support patching per ICT schedule.

### 6. Justification

- Required for a compliant SDLC (Dev → Test → Prod) per the project charter
  (`docs/sdlc/04-Project-Charter.md`).
- Test environment is a prerequisite for UAT (`docs/sdlc/07-UAT.md`) and training
  (`docs/sdlc/08-Training.md`), ahead of go-live (`docs/sdlc/09-Commissioning.md`).

### 7. Expected handover date

**Requested by:** [DD/MM/YYYY] — to meet go-live target of [DD/MM/YYYY].

### 8. Approvals

| Role | Name | Signature | Date |
|---|---|---|---|
| Requester | | | |
| ICT / Infrastructure lead | | | |
| Sponsor / Manager | | | |

---

*Attach: `12-Server-Environment-Requirements.md` (detailed specs), `13-…` back-end
credentials handling via `11-Credentials.md`.*