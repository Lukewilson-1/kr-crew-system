# SDLC 08 — Training

**Project:** KR Crew & Running-Room Management System
**Status:** PLAN TEMPLATE — audiences & content drafted; schedule/participants **TO BE COMPLETED BY BUSINESS/ICT**

---

## 1. Purpose

Equip the operator community (booking officers, station officers, running-room
attendants) and administrators to use the system safely, before go-live in each depot.

## 2. Audiences (drafted from roles)

| Audience | Roles | System surface | Priority |
|---|---|---|---|
| Station operations | `booking_officer`, `station_officer` | Crew SPA, Running Rooms, Operations Console | High |
| Running-room attendants | `attendant` (own room) | Running Rooms (scoped) | High |
| Depot/HQ administrators | `hq_admin`, `super_admin`, `crew_admin` | Admin panel incl. dashboards, reports, config | High |
| ICT operations | ICT team | Maintenance, break-glass, rotation, backups, scheduled tasks | High |

## 3. Training content (index to manuals)

- **Module 1 — Getting started & login** → SYSTEM_DOCUMENTATION.md §4.1–4.2
- **Module 2 — Crew daily status & registers** → §4.3 (Crew workflows), §4.5
- **Module 3 — Running rooms (check-in/out, beds, matters)** → §4.3 (Running Rooms)
- **Module 4 — Reports & exports** → §4.5, §8.6–8.7
- **Module 5 — Administration (users, roles, depots, rooms, designations)** → §4.4
- **Module 6 — Safety of access** → §9 (break-glass), maintenance banner & login
- **Module 7 — Incident & support contact** → §7 (Operations & Support)

## 4. Delivery plan (TO BE COMPLETED)

| Session | Audience | Mode (classroom / online / manual) | Trainer | Venue | Date/time | Materials ref |
|---|---|---|---|---|---|---|
| M1–M2 | Booking officers | | | | | |
| M3 | Attendants | | | | | |
| M4 | Reporting users | | | | | |
| M5 | Admin group | | | | | |
| M6–M7 | All + ICT | | | | | |

## 5. Training environment

- Use the **Test** environment with training data (never production).
- Provide each participant a sandbox account (e.g. `train_<role>_<depot>`).
- Supervised practice scenarios mirror UAT case IDs in `07-UAT.md`.

## 6. Attendance & competence (TO BE COMPLETED)

| Session | Participant name | Depot | Attended | Competence confirmed (Y/N) | Date |
|---|---|---|---|---|---|

## 7. Training materials sign-off

| Item | Owner | Status | Date |
|---|---|---|---|
| Participant handout (M1–M3) | | | |
| Admin handout (M4–M7) | | | |
| Recorded walk-throughs | | | |
| Quick-reference cards (check-in, rest, matters) | | | |

---
*Next: [09 — Commissioning](09-Commissioning.md)*