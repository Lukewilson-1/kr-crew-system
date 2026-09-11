# SDLC 12 — Server Environment Requirements (Dev / Test / Production)

**Project:** KR Crew & Running-Room Management System
**Status:** DRAFT — to be approved by ICT/Infrastructure and attached to the official
request (`13-Official-ICT-Request-Note.md`).
**Preferred platform:** **Ubuntu 24.04 LTS (Noble Numbat)** — 64-bit, `x86_64`.

---

## 1. Requirement summary

Provision **three (3) Linux servers**, one per environment, isolated from each other,
running the KR Crew System with the stack below. Minimum security: SSH key-only access,
host firewall, TLS, and backups.

| Server | Purpose | Env role |
|---|---|---|
| **DEV** | Developer integration & fixes | `APP_ENV=local` |
| **TEST** | UAT, training, staging validation (mirrors prod config) | `APP_ENV=test` (or `staging`) |
| **PROD** | Live operations | `APP_ENV=production` |

## 2. Recommended baseline specifications

> Strategy: Test ≈ Production configuration (so UAT is trustworthy); Dev can be lighter.

| Item | DEV | TEST | PROD |
|---|---|---|---|
| vCPU | 2 | 2 | 4 |
| RAM | 4 GB | 4 GB | 8 GB |
| System disk | 40 GB SSD | 60 GB SSD | 80 GB SSD (min) |
| Additional data volume | optional | optional | 50 GB (DB + `matter-photos`) |
| Backups | snapshots (weekly) | snapshots (weekly) | DB + file backups (daily, off-site) |
| Network | office/LAN | LAN | 100 Mbps+; static public IP w/ reverse proxy/TLS |
| OS | Ubuntu 24.04 LTS | Ubuntu 24.04 LTS | Ubuntu 24.04 LTS |

*Tune upward if concurrent depot users > ~200 or asset counts grow; Laravel is
single-app on the box, Eloquent over MySQL — 4 vCPU/8 GB sustains the expected depot
workloads (currently small datasets).*

## 3. Software stack (each server)

| Component | Package | Version / notes |
|---|---|---|
| OS | Ubuntu Server | 24.04.2 LTS (kernel 6.8) |
| Web server | Nginx | 1.24 (Ubuntu repo) |
| PHP | PHP-FPM | **8.3** (native on 24.04) — app requires **^8.2** |
| PHP extensions | cli, fpm, mysql, xml, mbstring, intl, bcmath, curl, zip, gd, opcache | enable OPcache in Test/Prod |
| Database | MySQL Server | 8.0.x |
| Composer | composer | 2.x (`~/.local/bin/composer`) |
| Node.js | Node.js LTS + npm | **20.x LTS** (Vite 8 requires Node ≥20) |
| Git | git | 2.43+ |
| Queue (Prod, optional) | Redis + Supervisor | if async mail/notifications queue chosen |
| Cron | cron | `/etc/cron.d/` entry: `* * * * *` → `php artisan schedule:run` |
| Monitoring/ops | `systemd`, `journalctl`, `htop`, `logwatch`/`mond` (optional) | |

## 4. Network & security requirements

- SSH access **key-based** only; root login disabled.
- Host firewall (UFW): allow 22 (restricted), 80/443; deny 3306 from outside.
- MySQL bound to localhost or private VLAN; app user scoped to its own DB.
- TLS via Let's Encrypt (Certbot) on TEST + PROD; `APP_URL=https://…`.
- Separate unprivileged OS user per app (e.g. `crewsys`) owning `storage/`+`public/`.
- Secret rotation schedule per `11-Credentials.md`.
- Patching: monthly `apt update && apt upgrade`; reboot windows pre-agreed.

## 5. Application-level environment requirements

| | DEV | TEST | PROD |
|---|---|---|---|
| `APP_DEBUG` | true | false | false |
| `APP_ENV` | local | test/staging | production |
| Cache artifacts | none | `config:cache` + `route:cache` | same as TEST |
| Database | dev copies | UAT/training data (refreshed) | production (migrated) |
| Maintenance mode | allowed for testing | allowed | controlled/audited |
| Notifications e-mail | SMTP sandbox | SMTP sandbox → real recipients in UAT | production SMTP |
| Default creds | dev defaults (see `11-Credentials.md`) | **rotated** | **rotated** |

## 6. Provisioning checklist (ICT)

- [ ] Order/allocate 3 VMs per §2 (Ubuntu 24.04).
- [ ] Create OS users + SSH keys (Dev & ICT).
- [ ] Apply firewall + OS hardening (§4).
- [ ] Install stack (§3) per server.
- [ ] Configure MySQL (DB/user per env) + backups.
- [ ] Deploy code from `master`/tag, per DEPLOYMENT_GUIDE.md.
- [ ] Set `.env` per env; rotate secrets; fill `11-Credentials.md`.
- [ ] Enable cron (`schedule:run`) — Production only for auto-tasks; Test optional.
- [ ] TLS on Test/Prod.
- [ ] Smoke test per environment.

## 7. Sizing / capacity note

Current data volumes are small (hundreds of rows). The PROD spec above includes headroom
for monthly registers and photos; revisit when monthly usage exceeds the estimates in
`03-System-Analysis.md` §5.

## 8. Approval

| Role | Name | Signature | Date |
|---|---|---|---|
| ICT infrastructure | | | |
| PM | | | |

---
*Next: [13 — Official ICT Request Note](13-Official-ICT-Request-Note.md)*