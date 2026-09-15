# MGR-CMS User Requirements Document

**Kenya Railways Corporation · Crew Management System (CMS)**

| Field | Value |
|---|---|
| **Document Title** | User Requirements Document (URD) – Crew Management System (CMS) |
| **Prepared For** | MGR – Crew Management & Operations |
| **Document Status** | Draft for Review |
| **Version** | 1.0 |
| **Date** | 14 September 2026 |

---

## Contents

1. [Introduction](#1-introduction)
2. [Challenges and Gaps in the Current Process](#2-challenges-and-gaps-in-the-current-process)
3. [Objectives of the Proposed Crew Management System](#3-objectives-of-the-proposed-crew-management-system)
4. [Expected Benefits and Outcomes](#4-expected-benefits-and-outcomes)
5. [Key User and Operational Requirements](#5-key-user-and-operational-requirements)
6. [Assumptions and Constraints](#6-assumptions-and-constraints)
7. [Document Review and Approval](#7-document-review-and-approval)

---

## 1. Introduction

### 1.1 Purpose of this Document

This User Requirements Document (URD) defines the business challenges, objectives,
expected benefits, and user and operational requirements for the proposed Crew
Management System (CMS) at MGR. It is intended to guide system design,
vendor/developer engagement, and stakeholder review and approval, ensuring that the
delivered solution meets the operational needs of crew scheduling and supervision.

### 1.2 Background

Crew management at MGR is currently managed manually through a counter book and
Microsoft Excel. While functional, this approach has become increasingly inadequate
as operational demands have grown, resulting in inefficiencies, fatigue-management
gaps, and limited real-time oversight. A Crew Management System has been proposed to
digitise and centralise crew booking, rest-time tracking, real-time crew status
visibility, and running-room management.

---

## 2. Challenges and Gaps in the Current Process

The current manual, paper- and spreadsheet-based crew booking process presents the
following key challenges and gaps:

| Area | Challenge / Gap Identified |
|---|---|
| Manual booking (counter book & Excel) | Slow, error-prone, duplicated effort; no single source of truth for who is on/off duty. |
| Uneven crew allocation | Some crew are rostered repeatedly while others remain idle, leading to perceptions of unfairness and inefficient use of the workforce. |
| No systematic rest-time tracking | Rest hours are not automatically computed or enforced, creating a fatigue risk and potential non-compliance with rest-period regulations. |
| Under-utilised staff | Lack of visibility into crew availability means some qualified crew are left idle instead of being deployed. |
| No real-time visibility for supervisors | Supervisors cannot see, at a glance, who is resting, on standby, on leave, off duty, or absent – decisions are made on outdated or incomplete information. |
| Weak running-room management | Allocation of running-room/rest facilities is handled informally, with no linkage to crew status or booking records. |
| Limited auditability and reporting | Paper and spreadsheet records are difficult to consolidate, audit, or analyse for trends (e.g. fatigue incidents, overtime, absenteeism). |

---

## 3. Objectives of the Proposed Crew Management System

The Crew Management System is being proposed to address the challenges above. Its
core objectives are to:

1. Digitise crew on-duty and off-duty booking, replacing the counter book and Excel
   process with a single, centralised system.
2. Automatically calculate and monitor crew rest times to reduce fatigue risk and
   support compliance with duty/rest regulations.
3. Provide real-time visibility of crew positions and status (resting, standby, on
   leave, off duty, absent) to supervisors and controllers.
4. Enable balanced, fair, and efficient allocation of crew duties across the
   available workforce.
5. Digitise and streamline running-room management and booking, linked to crew duty
   status.
6. Create an auditable, reliable data record to support reporting, planning, and
   continuous improvement of crew operations.

---

## 4. Expected Benefits and Outcomes

Implementing the Crew Management System is expected to deliver the following benefits:

| Benefit Area | Expected Outcome |
|---|---|
| Safety & compliance | Accurate, automated rest-time tracking reduces fatigue-related risk and supports compliance with duty/rest-hour regulations. |
| Operational efficiency | Balanced, data-driven allocation of crew reduces idle time and prevents over-use of specific individuals. |
| Real-time visibility | Supervisors can instantly see crew status (resting, standby, on leave, off duty, absent) for faster, better-informed decisions. |
| Improved running-room management | Automated linkage between crew status and running-room occupancy improves space utilisation and reduces conflicts. |
| Transparency & fairness | A single, auditable system for booking on/off duty reduces disputes and builds crew confidence in the roster process. |
| Better planning | Historical and real-time data support forecasting, leave planning, and identification of staffing gaps. |
| Reduced administrative burden | Eliminates duplicate manual entries into counter books and spreadsheets, freeing supervisor time for operational duties. |
| Reporting & accountability | Digital records enable audit trails, management reporting, and performance/utilisation analysis. |

---

## 5. Key User and Operational Requirements

The following requirements describe what the system must (and should) do from the
perspective of its users – crew members, crew controllers/schedulers, supervisors,
and administrators – and the operational conditions under which it must perform.
Priority follows the MoSCoW convention (Must Have, Should Have, Could Have).

### 5.1 User Requirements

| ID | Requirement | Priority |
|---|---|---|
| UR-1 | The system shall allow booking of crew by the Running Shift Foreman digitally, replacing the counter book and Excel process. | Must Have |
| UR-2 | The system shall provide supervisors with a real-time dashboard showing each crew member's current status: on duty, resting, standby, on leave, off duty, or absent. | Must Have |
| UR-3 | The system shall automatically calculate and track crew rest hours between duties and flag any crew approaching or breaching minimum rest-time rules. | Must Have |
| UR-4 | The system shall support crew rostering/allocation based on availability, qualification, and rest status, to ensure even distribution of duties. | Must Have |
| UR-5 | The system shall manage running-room bookings, showing occupancy, availability, and linking room allocation to crew duty/rest status. | Must Have |
| UR-6 | The system shall maintain a searchable, auditable log of all bookings, changes, and approvals (who booked what, when, and by whom). | Must Have |
| UR-7 | The system shall allow role-based access: crew members, crew controllers/schedulers, supervisors, and administrators, each with appropriate permissions. | Must Have |
| UR-8 | The system shall generate reports on crew utilisation, rest compliance, absenteeism, and running-room occupancy over selectable periods. | Should Have |
| UR-9 | The system shall allow supervisors to record and manage leave and absence requests within the same platform. | Should Have |
| UR-10 | The system shall be accessible on desktop and mobile/tablet devices for use at crew booking points and running rooms. | Should Have |
| UR-11 | The system shall support data export (e.g. to Excel/PDF) for management reporting and regulatory submissions. | Should Have |
| UR-12 | The system shall integrate, where feasible, with existing HR/attendance or train-operations systems to avoid duplicate data entry. | Could Have |

### 5.2 Operational Requirements

| ID | Requirement | Priority |
|---|---|---|
| OR-1 | The system shall be available 24/7 given round-the-clock crew operations, with defined uptime targets and a fallback manual procedure for outages. | Must Have |
| OR-2 | The system shall ensure data accuracy and integrity, preventing double-booking of crew or running-room resources. | Must Have |
| OR-3 | The system shall provide real-time synchronisation so that updates made at any booking point are immediately visible to supervisors. | Must Have |
| OR-4 | The system shall enforce configurable rest-time and duty-hour rules aligned to MGR's operating policies and applicable regulations. | Must Have |
| OR-5 | The system shall retain historical booking and rest-time data for a defined retention period to support audits and investigations. | Should Have |
| OR-6 | The system shall be simple and intuitive enough for crew booking staff with limited IT experience to use with minimal training. | Should Have |
| OR-7 | The system shall provide user authentication and data security controls appropriate to operational and personal data handled. | Must Have |
| OR-8 | The system shall support scalability to accommodate growth in crew numbers, depots, or running rooms over time. | Should Have |

---

## 6. Assumptions and Constraints

- The system will initially serve MGR crew operations and may later be extended to
  other depots/locations.
- Rest-time and duty-hour rules will be configurable to reflect MGR's current
  operating policies and any applicable regulatory requirements.
- Reliable network/connectivity at crew booking points and running rooms is required
  for real-time functionality; a documented fallback procedure will be needed for
  outages.
- User training and change management will be required to transition staff from the
  manual counter book/Excel process to the new system.
- Data migration of any relevant historical records from Excel will be scoped
  separately during system design.

---

## 7. Document Review and Approval

This document is submitted for review and approval by the stakeholders below prior
to proceeding to system design and development.

| Name | Role | Signature | Date |
|---|---|---|---|
| Emmanuel Kahindi | Prepared By |  |  |
| Philemon sagala | Reviewed By |  |  |
|  | Approved By |  |  |

---

*Next: [02 — Terms of Reference](02-Terms-of-Reference.md)*