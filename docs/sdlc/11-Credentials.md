# SDLC 11 — Credentials Register

**Project:** KR Crew & Running-Room Management System
**Status:** TEMPLATE — dev defaults documented; **Test/Production values must NOT be
stored in this repository**.

> ⚠️ **Policy:** production/test secrets live in the ICT vault / `~/.env` on the servers
> and are shared outside this repo. Never commit real passwords. This register only
> records *where* credentials exist, rotation policy, and non-secret identifiers.

---

## 1. Credential inventory

| System | Identifier (non-secret) | Where stored/runtime | Rotation | Owner |
|---|---|---|---|---|
| DB (MySQL) | `DB_HOST/DB_USERNAME` per env | `.env` on each server | On first provision + quarterly | ICT |
| App key | `APP_KEY` | `.env` | Never (keygen on setup) | ICT |
| Maintenance account | username `site@maintenance.com` / login `sitemaintenance` | `config/maintenance.php` + `MAINTENANCE_*` env | **90 days** via `maintenance:rotate --update-env` | ICT |
| Break-glass | `BREAK_GLASS_USERNAME/PASSWORD` | `config/breakglass.php` + env | **90 days** via `break-glass:rotate --update-env` | ICT |
| Cron webhook | `CRON_TOKEN` | env (no shipped config — must set) | On provisioning | ICT |
| SMTP | `MAIL_PASSWORD` | `.env` | Mailbox policy | ICT |
| Super-admin user | username `superadmin` (USERS) | RAN Accounts got `superadmin123` default **dev only** | Immediately at first login | Admin |
| Alert recipients | `ALERT_EMAIL_RECIPIENTS` | env (non-secret) | As team changes | ICT |

## 2. Environment matrix (values NOT listed here)

| Env | Server | DB name | APP_ENV | APP_DEBUG | Production values |
|---|---|---|---|---|---|
| Dev | `…` (see `12-…`) | _(to fill)_ | local | true | n/a — dev `superadmin123` / maintenance `n0!!Pass10` defaults apply |
| Test/UAT | `…` | _(to fill)_ | test | false | **from vault** |
| Prod | `…` | _(to fill)_ | production | false | **from vault** |

## 3. Rotation procedure references

- Maintenance & break-glass rotation:
  `php artisan maintenance:rotate --update-env` /
  `php artisan break-glass:rotate --update-env` (audited).
- See `DEPLOYMENT_GUIDE.md` §7 for the full procedure.

## 4. Break-glass & emergency access rules (drafted)

- Break-glass **disabled by default**; enable only via config, with justification
  (20–2000 chars) and 8 h session; all events audited.
- Maintenance lockdown wipes sessions on activation; operator re-enters via
  `/maintenance-login`.

## 5. Acknowledge & custody log (TO BE COMPLETED)

| Credential owner | Receiving role | Purpose | Date issued/rotated |
|---|---|---|---|
| | | | |

---
*Next: [12 — Server Environment Requirements](12-Server-Environment-Requirements.md)*