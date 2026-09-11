# SYSTEM IMPLEMENTATION DESIGN (SID) — KR Crew & Running-Room Management System

> **Completes the Kenya Railways ICT Division SID Template v1.1**
> Technical submission document prepared by the Rail Operations Department (developed
> internally by Kenya Railways Corporation).
> Fields marked **[Insert]** are to be completed by the submitting department where
> information is environment/contact-specific.

Template version: **1.1**
Issued by: **Kenya Railways — ICT Division (Network and Security)**

---

## SECTION 1 — PROJECT DETAILS

| Field | Description |
|---|---|
| Project Name | **KR Crew & Running-Room Management System** |
| Tender / Contract Reference | Internal project (no external tender/contract) |
| Vendor / Implementer Name | Kenya Railways Corporation — developed internally |
| Prepared By | Rail Operations Department |
| Date of Submission | 11 September 2026 |
| Version | **1.0** |
| System Owner | **Kenya Railways Corporation** |

---

## SECTION 2 — PROJECT OVERVIEW

### 2.1 Purpose and Objectives

The system (Laravel 11 web application with a Filament 4 admin panel) replaces manual
registers for Kenya Railways' crew duty status and running-room management. It:

- Maintains a **crew register and daily duty status** per member (statuses with start
  date/times, editing restricted to a 2-day window; end-of-day baseline copied daily
  at 00:01).
- Tracks **running-room occupancy** (rooms, beds, check-in/check-out) per depot with
  automatic rest enforcement — **12-hours rest at home depot, 10-hours away** — with
  unattended auto-check-out every 5 minutes and notifications.
- Records **matters arising** with unique ticket numbers (`MTR-YYYY-NNNN`), photos
  (≤ 5 MB), status lifecycle (open / hold / resolved / reopened).
- Provides **reports** (operational portal reports plus 13 decision-support reports:
  operational, workforce, matters, and governance — Secure Access, PII Audit, Login
  Security) with depot-level visibility scoping.
- Enforces **role-based access control**, an **append-only audit log**, **hard-lockdown
  maintenance mode**, and a **break-glass emergency access** capability.

Expected outcomes: improved visibility of crew availability at depots, reduced
unattended check-outs/errors, auditability for HR/Operations compliance, and reduced
manual reporting effort.

### 2.2 Scope of Implementation

| In-Scope Components | Out-of-Scope Components |
|---|---|
| Crew master + daily status register | SMS gateway / mobile app |
| Running rooms: rooms, beds, attendance records, rest rules | Biometrics / card readers integration |
| Matters arising management (tickets + photos) | Payroll or HRMS integration |
| Portal reports + decision-support reports | Public-facing kiosks / external portals |
| Notifications (in-app + e-mail via SMTP) | LDAP/SSO single sign-on (roadmap item) |
| RBAC, audit logging, maintenance lockdown, break-glass | Legacy plaintext password bridge (removed) |
| Scheduled automation (end-of-day copy, auto-check-out, auto-deactivate, audit prune) | Long-term archives beyond retention policy (audit pruned at 365 days) |

**System boundaries:** standalone internal web application; no external SaaS or public
API consumers. E-mail notifications are the only outbound integration.

### 2.3 Stakeholders

| Role | Name / Entity | Contact | Responsibility |
|---|---|---|---|
| Developer / Technical Lead | Lukewilson Simiyu — ICT Assistant | [Insert] | Delivery, technical documentation, Go-Live support |
| Technical Support (nominated) | Florence Ngusi, Sam Mutiku | [Insert] | Nominated technical support for the project |
| User Team (nominated) | Sagala Philemona, Emmanuel Kahindi | [Insert] | Business users; UAT execution and user acceptance |
| ICT Coordinator | George Muia | [Insert] | Coordinator from ICT; ensures full ICT support |
| KR ICT Project Manager (ICTM) | [Insert] | [Insert] | Acceptance, change management approval |
| System Owner / Business Sponsor | Rail Operations Department | [Insert] | Business requirements, UAT sign-off |
| System Administrator | [Insert] | [Insert] | Day-to-day admin, PAM provisioning, backups |
| Helpdesk | KR Helpdesk | [Insert] | Incident/change logging and closure |

---

## SECTION 3 — SYSTEM ARCHITECTURE DESIGN

### 3.1 Logical Architecture

- **Users** access the web UI over HTTPS from the **KR LAN** using a standard browser.
  No public exposure.
- The application is a **Laravel 11 (PHP)** monolith: server-rendered web/JSON routes
  for crew + running rooms, plus the **Filament 4** admin panel (`/admin`).
- **Database:** MySQL 8.x (32 tables) accessed by the app under a least-privilege DB
  user; a **database activity monitoring (DAM)** integration point is provided for KR
  DAM (see §6.3).
- **Automation:** Laravel scheduler runs via cron `* * * * *`: end-of-day status copy
  (00:01), running-room auto-check-out (every 5 min), maintenance auto-deactivate
  (every minute), audit-log prune (daily, retention 365 days).
- **Notifications:** in-app records + e-mail through the KR SMTP relay
  (`notifications:test-email` verifies delivery).
- **Authentication & data flow:** login by e-mail **or** username → bcrypt
  verification (throttled 5/min on `/mysql/login`), session cookie + CSRF; every
  request passes RBAC policies; panel access requires an **active** account with
  global access **or** at least one panel permission. Data mutations are audited
  through observers into the append-only `audit_logs` table with secret-value
  redaction.

```mermaid
flowchart LR
    U[LAN users: attendants, booking officers, station officers, HQ, admins] -->|HTTPS 443| NG[nginx + PHP-FPM (Laravel 11)]
    NG --> WB[Web / JSON routes: crew, running rooms]
    NG --> AD[Filament 4 admin panel /admin]
    NG --> CR[cron: schedule:run every minute]
    CR --> T1[crew:copy-end-of-day-status 00:01]
    CR --> T2[running-rooms:auto-checkout-rested 5 min]
    CR --> T3[maintenance:auto-deactivate 1 min]
    CR --> T4[audit:prune retention 365 d]
    NG --> DB[(MySQL 8 - 32 tables)]
    NG --> FA[Storage app + public/matter-photos]
    NG --> SM[SMTP relay - notifications]
    DB --> DAM[KR DAM integ. point]
    A[ICT admins] -->|KR PAM| SSH[SSH admin - key only]
```

### 3.2 Physical Architecture

- All three environments (**Development**, **Testing/UAT**, **Production**) are
  **virtual machines in the KR LAN zone** (on-premises data centre). No DMZ hosting —
  the system is **internal-facing**.
- If remote/external access is ever required, it will be exposed **only through the KR
  Web Application Firewall (WAF)** and restricted to HTTPS.
- **No internet access** in Development/Testing environments.
- Apply **KR PAM** for all administrative/server access; each environment has its own
  MySQL instance (isolated data).

### 3.3 Architecture Diagram

Attach labeled logical + physical diagrams in **Appendix A** (PNG/SVG exported from the
mermaid source shown in §3.1; physical diagram in Appendix A.2).

---

## SECTION 4 — SERVER AND INFRASTRUCTURE REQUIREMENTS

### 4.1 Server Environment (vendor input)

All servers = **virtualized VMs** on KR hosting; OS **Ubuntu Server 24.04 LTS**;
database **MySQL 8.x**.

| Environment | Purpose | OS | Database | CPU / RAM / Storage | Network Zone | Backup | Notes |
|---|---|---|---|---|---|---|---|
| **Development** | Developer integration & fixes | Ubuntu 24.04 LTS | MySQL 8.x | 2 vCPU / 4 GB / 40 GB SSD | **LAN** | Y (weekly snapshot) | Internet access **disabled** |
| **Testing / UAT** | UAT + training; mirrors production config (`APP_DEBUG=false`) | Ubuntu 24.04 LTS | MySQL 8.x | 2 vCPU / 4 GB / 60 GB SSD | **LAN** | Y (weekly) | Mirrors production setup |
| **Production** | Live operations | Ubuntu 24.04 LTS | MySQL 8.x | 4 vCPU / 8 GB / 80 GB SSD + 50 GB data volume | **LAN** (not public) | Y (daily, off-site) | If public exposure later: published via KR **WAF** |
| **DR Site** | Disaster recovery | Ubuntu 24.04 LTS | MySQL 8.x | 2 vCPU / 4 GB / 80 GB SSD | **LAN** | Y (replication + drill) | **Committed plan**: MySQL GTID replication to DR VM + semi-annual drill — see `disaster-recovery.md` & `scripts/mysql/` (§4.3); RPO ≤ 5 min, RTO ≤ 1 h |

### 4.2 Administration and Access Control

1. All **server-level** administrative access (SSH) is via **KR Privileged Access
   Management (PAM)** — no direct admin login without PAM approval; session recording
   and approval workflows apply.
2. **Application-level** admins are separate service accounts governed by in-app RBAC
   (roles `super_admin`, `hq_admin`, `crew_admin`, `booking_officer`, `station_officer`),
   audited in `audit_logs`.
3. Emergency app access uses the **break-glass** mechanism (disabled by default, 8-hour
   session, justification required, audited) and the maintenance lockdown login —
   both passwords rotated via audited artisan commands (`--update-env`, 90 days).

### 4.3 Hosting and Redundancy

- **Hosting model:** On-premises (KR data centre), VM-based, no public cloud dependency.
- **Redundancy & DR (committed plan):** MySQL **replication to the DR VM** (GTID
  row-based, SSL, `read_only` replica) is part of Production provisioning, with a
  **semi-annual DR drill** to prove restore-ability. The DR topology, setup scripts,
  drill, and failover/fallback procedures are in **`docs/disaster-recovery.md`** and
  implemented under **`scripts/mysql/`** (`enable-mysql-source.sh`,
  `setup-mysql-replica.sh`, `dr-drill.sh`). Targets: **RPO ≤ 5 min, RTO ≤ 1 h**.
- **Backups:** `mysqldump` + `storage/` + `public/matter-photos`; Production daily
  off-site; restore validated monthly.

---

## SECTION 5 — NETWORK AND CONNECTIVITY DESIGN

### 5.1 Network Zones

- **LAN Zone:** the KR Crew System (all components) and its user community — the system
  is an **internal business system**, i.e. **LAN**.
- **DMZ Zone:** **Not applicable** to this deployment. Any future public-facing
  component **must** reside in the DMZ behind the KR WAF.

### 5.2 Connectivity Matrix (full matrix in **Appendix B**)

| Source | Destination | Zone | Port / Protocol | Purpose | Security Control |
|---|---|---|---|---|---|
| User workstation | nginx / PHP-FPM (web) | LAN | 443 / TCP (HTTPS TLS 1.2+) | Web UI (app + admin) | KR firewall; HTTPS; app RBAC + session auth |
| nginx | PHP-FPM | LAN (localhost) | 9000 / TCP | PHP processing | Loopback only |
| Application | MySQL | LAN (localhost) | 3306 / TCP | Data access (least-privilege DB user) | Loopback; app-specific DB credentials |
| Application | KR SMTP relay | LAN | 465 (or 587) / TCP TLS | E-mail notifications | Allowlist to relay only |
| Application | KR DAM (if applicable) | LAN | Per KR DAM policy | DB activity monitoring | Per KR DAM Policy |
| cron | artisan (`schedule:run`) | LAN | local process | Scheduled tasks | OS user `crewsys` |
| ICT admin | Server SSH | LAN | 22 / TCP | Server administration | **KR PAM** only, key-based |
| (optional) App | Redis | LAN (localhost) | 6379 / TCP | Async queue (Prod) | Loopback; AUTH password |

### 5.3 Integration Points

| Integration | Direction | Authentication | Protocol | Data flow description | Status |
|---|---|---|---|---|---|
| KR SMTP relay (mail) | Outbound | SMTP credentials | SMTP/SMTPS | In-app + e-mail notifications on check-outs, issues, maintenance events | In use (test-email command) |
| KR DAM (DB monitoring) | Outbound (to DAM) | Per KR DAM Policy | Per KR DAM Policy | DB activity streams of operational/personal data | **To be enabled** per §6.3 |
| KR PAM (server admin) | — | PAM approval + session recording | SSH session | PAM-mediated server access | **Required** for commissioning |
| Directory/SSO (LDAP) | Inbound | — | — | Future SSO single sign-on | Roadmap (not in v1.6) |

No third-party SaaS or public API integrations are present.

---

## SECTION 6 — SECURITY ARCHITECTURE

### 6.1 Overview

- **Confidentiality:** TLS in transit; passwords hashed (bcrypt); RBAC + policies
  enforce least privilege; secret values (passwords) redacted in audit output; secrets
  held in `.env` (never committed; rotation commands supported).
- **Integrity:** CSRF protection on web forms (two maintenance endpoints exempt and
  controlled); append-only `audit_logs` with actor attribution; edit windows on crew
  history (2 days); ticket/sequence enforcement; database-level uniqueness on active
  running-room records.
- **Availability:** hard-lockdown maintenance mode with scheduled auto-recovery;
  break-glass for emergencies; backup + restore; monitoring + log review (see §9).

### 6.2 Web Application Firewall (WAF)

- The system is **internal (LAN)**, so the KR WAF is **not applicable** to the current
  deployment.
- **Commitment:** if the system is ever published for external/remote access, it will
  be hosted in the DMZ and published **through the KR WAF** per the KR WAF Policy
  (SSL/TLS settings, WAF mode, and rule policies will be configured by KR Network &
  Security at that time).

### 6.3 Database Activity Monitoring (DAM)

- **Applicable:** the database holds **operational and personal data** (crew/staff
  records, attendance, matters). DAM therefore applies.
- Integration will follow the **KR DAM Policy** against the MySQL instances. In
  addition to DAM, the application maintains its own audit log of attribute-level data
  changes (`audit_logs`, 365-day retention, secret redaction), which supports the KR
  DAM review cycle.
- **Action (ICT):** confirm DAM architecture/placement for on-prem MySQL and open the
  required connectivity per Appendix B.

### 6.4 Server Hardening and Security

- KR-approved hardening baseline applied: unnecessary services disabled; `apt`
  updates applied; local firewall (UFW) enforcing §5.2; SSH key-based, PAM-mediated.
- **No internet access** on Development/Testing environments.
- Endpoint protection per KR standard installed on all servers.
- Web app hardening: `APP_DEBUG=false` + OPcache in Test/Prod; uploads validated by
  extension/size; throttling on authentication and mutation endpoints.

### 6.5 Vulnerability and Patch Management

- **Quarterly vulnerability assessments** by KR Network & Security (schedule in §9).
- Patches managed through the **KR Change Management Process** (§8) with rollback plans.
- Critical vulnerabilities remediated within timelines agreed with KR ICT at
  commissioning.

---

## SECTION 7 — IMPLEMENTATION AND DEPLOYMENT PLAN

1. **Pre-installation checks & environment setup**
   - Provision VMs per §4.1; apply hardening (§6.4); install stack (Nginx, PHP-FPM
     8.3, MySQL 8, Composer, Node 20 LTS, git, cron); enable PAM + backups.
   - Baseline restore + smoke test in Test.
2. **Installation and configuration**
   - Deploy tagged release per `DEPLOYMENT_GUIDE.md`;
     `composer install --no-dev`, `npm ci && npm run build`, `php artisan migrate --force`.
   - Env configuration: `APP_ENV`, `APP_DEBUG`, `APP_URL`, DB, SMTP, `CRON_TOKEN`
     (webhook auth), alert recipients.
   - Seed baseline data (roles, permissions, default reports, super-admin) and rotate
     defaults per `11-Credentials.md`.
3. **Integration with KR authentication and monitoring tools**
   - **PAM** enabled for all admin/SSH access (approval + session recording).
   - **KR monitoring tools** pointed at app logs (`storage/logs`) and the documented
     health checks; e-mail alert recipients configured and test email sent.
   - **KR DAM** enabled per §6.3.
4. **User Acceptance Testing (UAT)**
   - Execute `docs/sdlc/07-UAT.md` (40 devised cases: auth/RBAC, crew, running rooms,
     reports, notifications, security ops) in the **Test** environment.
   - Defects logged to KR Helpdesk; retest until exit criteria met.
5. **Go-live and migration strategy**
   - Business data-entry freeze; final backups; migrations applied; cutover per
     `09-Commissioning.md`; smoke tests; go-live communication.
   - Legacy register backfill (crew master) assessed at commissioning (import method
     agreed with ICT/business).
6. **Rollback / fallback plan**
   - Restore last verified DB backup and previous application tag (target ≤ 1 hour).
   - Trigger criteria: critical data loss, blocking security defect, or > 2 h sustained
     outage. Communications plan pre-agreed with KR ICTM.
7. **Post-implementation verification**
   - Post-change verification attached to the Helpdesk record; hypercare for 2 weeks;
   - PIR with open-defect closure and lessons learned.

Production rollout is coordinated with **KR ICT** to ensure **PAM, monitoring, and
backups are active** before the switch.

---

## SECTION 8 — GOVERNANCE AND CHANGE MANAGEMENT

### 8.1 Policy Application

All project changes follow the **KR ICT Change Management Policy**; this SID and its
appendices are the baseline technical reference.

### 8.2 Change Request Process

1. All change requests are **logged in the KR Helpdesk System**, including change
   description, risk assessment, rollback plan, and impact analysis (Appendix D).
2. **Approval by ICTM** is required before implementation.
3. Changes are tested in Test/UAT for any release-level change; production changes
   occur in agreed maintenance windows.

### 8.3 Verification and Closure

- Post-change verification results attached to the Helpdesk record.
- **Closure only after validation by KR ICT.**

---

## SECTION 9 — MONITORING AND MAINTENANCE

| Activity | Frequency | Responsible Party | Tool / Platform |
|---|---|---|---|
| System Health Monitoring | Continuous | KR ICT | KR Monitoring Tools (Laravel `storage/logs`, process/site checks) |
| Log Review | Weekly | KR ICT Security | KR Security tools + application audit log |
| Backup Validation | Monthly | Developer/ICT Assistant + KR ICT | Restore a sample DB + storage snapshot; validate |
| Vulnerability Scan | Quarterly | KR Network & Security | KR VA platform |
| DR Drill | Semi-Annual | Developer/ICT Assistant + KR ICT | DR VM replication test + restore drill (runbook: `disaster-recovery.md`) |

**Application maintenance:** scheduled tasks (end-of-day copy, auto-check-out,
auto-deactivate, audit prune) run under cron; no-cloud, on-premise.

---

## SECTION 10 — COMPLIANCE AND POLICY ALIGNMENT

| Policy / Framework | Applicability | Compliance (Yes/No) | Remarks |
|---|---|---|---|
| KR Web Application Firewall (WAF) Policy | If public-facing | **N/A** (yes if external) | Internal/LAN system today; committed to WAF if published externally |
| KR Database Activity Monitoring (DAM) Policy | If the system has a critical DB | **Yes** (to be enabled) | DB holds operational + personal data; integration per §6.3 |
| KR ICT Change Management Policy | Always | **Yes** | All changes via KR Helpdesk + ICTM approval (§8) |
| KR ICT Security Policy | Always | **Yes** | Hardening, patch, vulnerability schedule (§6.4–6.5) |
| KR PAM Access Control Policy | Always | **Yes** | All server admin via KR PAM (§4.2); app RBAC separate |
| KR Data Protection Policy | Where applicable | **Yes** | Crew/staff PII handled; audit, PII reports, 365-day retention |

---

## SECTION 11 — SIGN-OFFS

**Prepared and submitted by (Developer — Rail Operations):**

Name: **Lukewilson Simiyu**
Designation: **ICT Assistant**
Signature: __________________________
Date: __________________________

**Reviewed and accepted by Kenya Railways:**

Name: __________________________
Designation: Project Manager (ICTM)
Signature: __________________________
Date: __________________________

---
---

# Appendices

## Appendix A — Architecture Diagrams (Mandatory)

### A.1 Logical Architecture

*Diagram source (export to PNG/SVG for attachment):*

```mermaid
flowchart LR
    U[LAN users: attendants, booking officers, station officers, HQ, admins] -->|HTTPS 443| NG[nginx + PHP-FPM (Laravel 11)]
    NG --> WB[Web / JSON routes: crew, running rooms]
    NG --> AD[Filament 4 admin panel /admin]
    NG --> CR[cron: schedule:run every minute]
    CR --> T1[crew:copy-end-of-day-status 00:01]
    CR --> T2[running-rooms:auto-checkout-rested 5 min]
    CR --> T3[maintenance:auto-deactivate 1 min]
    CR --> T4[audit:prune retention 365 d]
    NG --> DB[(MySQL 8 - 32 tables)]
    NG --> FA[Storage: app + public/matter-photos]
    NG --> SM[KR SMTP relay - notifications]
    DB --> DAM[KR DAM integration point]
    ADM[ICT admins] -->|KR PAM| SSH[SSH server admin - key based]
```

### A.2 Physical Architecture

**LAN Zone (single data centre):**

```
        KR LAN
  [Users] --443--> [VM: Production]  nginx+PHP-FPM 8.3 + Laravel app
                          |--localhost--> [MySQL 8 (data volume)]
                          |--> [storage/matter-photos]
  [VM: Testing/UAT]  (mirrors production; no internet)
  [VM: Development]  (no internet)
  [VM: DR Site]      (MySQL GTID replica; semi-annual drill - runbook: disaster-recovery.md)
  [KR DAM + KR Monitoring + PAM]  <-- monitored centrally by KR ICT
```

## Appendix B — Network Port & Flow Matrix (Mandatory)

| # | Source | Destination | Zone | Port / Protocol | Purpose | Security Control |
|---|---|---|---|---|---|---|
| 1 | User workstation | nginx web server | LAN→LAN | 443/TCP HTTPS (TLS 1.2+) | Web UI (app + admin) | KR firewall; HTTPS; app RBAC |
| 2 | nginx | PHP-FPM socket | local | 9000/TCP (loopback) | PHP processing | Loopback only |
| 3 | Laravel app | MySQL | local | 3306/TCP (loopback) | Data access | App DB user; least privilege |
| 4 | Laravel app | KR SMTP relay | LAN→LAN | 465|587/TCP TLS | E-mail notifications | Relay allowlist + creds in `.env` |
| 5 | Laravel app | KR DAM | LAN | per KR DAM Policy | DB activity monitoring | Per KR DAM Policy |
| 6 | cron | `php artisan schedule:run` | local | process | Scheduled tasks | OS service account `crewsys` |
| 7 | ICT admins | Server (SSH) | LAN→LAN | 22/TCP | Administration | **KR PAM**; key-based auth only |
| 8 | Laravel app (Prod opt.) | Redis | local | 6379/TCP | Async queue | Loopback + AUTH |

## Appendix C — Server Configuration Sheet (Mandatory)

| Attribute | Development | Testing / UAT | Production | DR Site |
|---|---|---|---|---|
| Hostname | [Insert] | [Insert] | [Insert] | [Insert] |
| IP address | [Insert] | [Insert] | [Insert] | [Insert] |
| Type (physical/VM/container) | VM | VM | VM | VM |
| OS | Ubuntu 24.04 LTS | Ubuntu 24.04 LTS | Ubuntu 24.04 LTS | Ubuntu 24.04 LTS |
| Web/App | Nginx + PHP-FPM 8.3 | Nginx + PHP-FPM 8.3 | Nginx + PHP-FPM 8.3 | Restore target |
| Database | MySQL 8.x | MySQL 8.x | MySQL 8.x | MySQL 8.x (replica) |
| CPU / RAM / Storage | 2 vCPU / 4 GB / 40 GB | 2 vCPU / 4 GB / 60 GB | 4 vCPU / 8 GB / 80+50 GB | 2 vCPU / 4 GB / 80 GB |
| Network zone | LAN | LAN | LAN | LAN |
| Internet access | **Disabled** | **Disabled** | Restricted (SMTP-only) | Restricted |
| Backup | Weekly snapshot | Weekly snapshot | Daily off-site | MySQL GTID replication + semi-annual drill |
| PAM access | Required | Required | Required | Required |
| Endpoint protection | Yes | Yes | Yes | Yes |
| APP_ENV / APP_DEBUG | local / true | test / false | production / false | — |

## Appendix D — Change Management Workflow (Mandatory)

```
[Identify/Request Change] --(KR Helpdesk ticket)--> [Risk & Impact Analysis]
   --> [Rollback plan defined] --> [ICTM approval? No -> Reject/Revise]
   --> yes --> [Schedule change window] --> [Implement + Test (Test env for releases)]
   --> [Post-change verification - attach result to ticket]
   --> [KR ICT validation] --> [Closure]
```

Controls: no production change without an approved Helpdesk change reference; every
release to Production passes Test/UAT first (per §7).

## Appendix E — Patch & Vulnerability Log Template (Optional)

| Date | Server/Component | Patch/VA Ref | Type (Patch/Vuln) | Severity | Remediated (Y/N) | Closed (date) | Approved change ref |
|---|---|---|---|---|---|---|---|
| [Insert] | [Insert] | [Insert] | Patch | High | Yes/No | | |

## Appendix F — Version Control Log (Mandatory)

| Version | Date | Author | Change description | Status |
|---|---|---|---|---|
| 1.0 | 11 September 2026 | Lukewilson Simiyu (ICT Assistant) | First submission for review | Draft / Under review / Approved |

---
---

## SUBMISSION CHECKLIST

| Item | Description | Status (Yes/No) |
|---|---|---|
| All sections completed | All applicable fields filled or marked N/A | |
| Architecture diagrams attached | Appendix A (logical + physical) | |
| Network & server tables filled | Appendices B & C | |
| PAM access compliance indicated | §4.2, §6.4, Appendix B/C | |
| Change management workflow attached | Appendix D | |
| Signatures (Developer & ICTM) included | §11 | |
| Electronic copy (Word & PDF) submitted | Convert this markdown file to both formats | |

## SUBMISSION DETAILS

- **Submit electronically** to the Kenya Railways ICT Division: **ict@krc.co.ke**
  (or as per project instructions).
- Hardcopy submissions must be bound and signed after final review.

---
*Companion project documents: `docs/sdlc/` pack (System Analysis, System Architecture,
UAT, Commissioning, Credentials, Server Environment Requirements) and
`docs/DEPLOYMENT_GUIDE.md`.*