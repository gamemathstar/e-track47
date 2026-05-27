# Data Entry & Performance Tracking Approval Workflow

This document describes the **end-to-end workflow** for KPI performance tracking in the PDCU system, as implemented in the application. The flow follows this sequence:

**PDCU (initial setup) → Data Admin → Sector Head → Facilitator → PDCU (final verification / Coordinator)**

---

## Overview

| Stage | Primary roles (application) | Core object |
|-------|-----------------------------|-------------|
| 1 | PDCU staff (`Coordinator`, `Deputy Coordinator`, `Facilitator`, legacy `Delivery Department` — grouped as `isDeliveryUnit()` in code) | `performance_trackings` row created with **milestone** |
| 2 | **Data Admin** (per sector) | **Actual value**, dates, remarks, evidence |
| 3 | **Sector Head** (per sector) | Bulk **approval** of submitted tracking |
| 4 | **Facilitator** (assigned to sector) | **Accept** or **Reject**; delivery-department fields on accept |
| 5 | **PDCU** (again — typically **Coordinator / Deputy Coordinator** for final sign-off) | **Verify** modal: **Confirmed** or **Rejected** |

**Database fields** that drive the pipeline (among others):

- `confirmation_status` — workflow state (see [Status values](#confirmation-status-values)).
- `sector_head_approved_at` / `sector_head_approved_by`
- `facilitator_confirmed_at` / `facilitator_confirmed_by`, `facilitator_decision`, `facilitator_rejection_reason`
- `delivery_department_value` / `delivery_department_remark` (often filled at Facilitator accept)
- `coordinator_confirmed_at` / `coordinator_confirmed_by` (reserved on model; final UI path below uses verify modal + `confirmation_status`)

---

## Confirmation status values

The system uses an enumerated `confirmation_status` including (non-exhaustive for all edge paths):

| Status | Meaning (typical) |
|--------|-------------------|
| `Not Confirmed` | Milestone set by PDCU; Data Admin has not yet submitted, or record awaiting earlier steps |
| `Pending Sector Head Approval` | Data Admin has submitted **actual** performance; awaiting Sector Head |
| `Pending Facilitator` | Sector Head has approved; awaiting Facilitator |
| `Pending Coordinator` | Defined in schema; used where intermediate coordinator queue is represented |
| `Confirmed` | Final positive outcome (after PDCU verify modal **Confirmed**) |
| `Rejected` | Rejected path (Facilitator reject or verify modal **Rejected**) |

---

## Stage 1 — PDCU (initial data entry)

**Who:** Users with PDCU “delivery unit” roles in code: **Coordinator**, **Deputy Coordinator**, **Facilitator**, or legacy **Delivery Department** (see `User::isDeliveryUnit()`).

### Responsibilities / actions

- **Create** performance tracking for a KPI / quarter / year with a **milestone** (and optional early fields).
- **Update milestone** on existing rows that do **not** yet have actual values from Data Admin (`KpiController::storeTracking` — PDCU branch).
- **KPI targets** and other PDCU-only KPI maintenance (`KpiController::saveTarget`, KPI create/update/delete where applicable).
- UI: **“Set milestone”** / add tracking modals on the KPI page (`resources/views/pages/sector/kpis.blade.php`).

### Typical `confirmation_status` after this stage

- **`Not Confirmed`** while milestone exists and **no** meaningful `actual_value` from Data Admin yet.

### What triggers the next stage

- **Data Admin** submits **actual value**, **tracking date**, and related fields for that tracking row → status moves toward **Sector Head queue** (see Stage 2).

---

## Stage 2 — Data Admin

**Who:** **Data Admin** for the sector (`User::isDataAdmin()`).

### Responsibilities / actions

- Submit **Add / Update Performance Tracking** with:
  - **Tracking date** (required on update path)
  - **Actual value** (required on update path)
  - **Remarks**, optional **file attachments**
- **Cannot change milestone** — milestone remains whatever PDCU set; server-side logic does not apply Data Admin–submitted milestone updates (`KpiController::storeTracking` — Data Admin branch).
- May **re-edit** after **Facilitator reject** (facilitator fields cleared on resubmit when decision was `Reject`).

### Status transition

- If the record is **not** yet approved by Sector Head (`sector_head_approved_at` / `sector_head_approved_by` absent), after save:
  - `confirmation_status` → **`Pending Sector Head Approval`**

### What triggers the next stage

- **Sector Head** runs **approval** for the relevant year/quarter (Stage 3).

### Notifications

- `Notification::notifySectorHeadForApproval($tracking)` — informs **Sector Head** (and Data Admin copy) that submission is ready for review.

---

## Stage 3 — Sector Head approval

**Who:** **Sector Head** for the sector (`User::isSectorHead()`).

### Responsibilities / actions

- **Bulk approve** all pending tracking for the sector for a selected **year** and optional **quarter** where:
  - `actual_value` is present and non-zero
  - Sector head has **not** yet approved (`sector_head_approved_by` is null)
- Implemented in `KpiController::approveData()`.

### On approve (per record)

- Sets `sector_head_approved_at`, `sector_head_approved_by`
- Sets `confirmation_status` → **`Pending Facilitator`**

### Decision flow

- This stage is an **approve** path for all matching pending rows (no per-row reject in `approveData`; rejections happen later at Facilitator or final verify modal).

### What triggers the next stage

- Successful approval updates rows to **Pending Facilitator** and notifies facilitators.

### Notifications

- `Notification::notifyFacilitatorAfterSectorHeadApproval($tracking)` — notifies **Facilitators** linked to the sector (via `facilitator_sectors` / active facilitator roles).

---

## Stage 4 — Facilitator

**Who:** **Facilitator** (`User::isFacilitator()`), sector-assigned.

### Responsibilities / actions

- Open **Facilitator confirmation** action (`KpiController::facilitatorConfirm()`).
- Decision: **`Accept`** or **`Reject`**.
- **Accept (required when accepting):**
  - `delivery_department_value`
  - `delivery_department_remark`
- **Reject (required when rejecting):**
  - `facilitator_rejection_reason`
- Sets facilitator audit fields: `facilitator_confirmed_at`, `facilitator_confirmed_by`, `facilitator_decision` (and related fields per decision).

> **Implementation note:** The controller comment states `confirmation_status` is **not** changed inside `facilitatorConfirm`; downstream UI and the verify step rely on facilitator fields + later **Confirmed** / **Rejected** from the verify modal.

### Preconditions

- Record must already have **`sector_head_approved_by`** set; otherwise facilitator action is rejected with an error message.

### Decision flow

| Decision | Result |
|----------|--------|
| **Accept** | Notifies **Coordinators / Deputy Coordinators** (`Notification::notifyCoordinatorAfterFacilitatorConfirmation`) — “final approval” queue from PDCU perspective. |
| **Reject** | Notifies **Data Admin(s)** for the sector (`Notification::notifyDataAdminAfterFacilitatorRejection`) with reason; Data Admin can correct and resubmit (loop back to Stage 2). |

### What triggers the next stage

- **Accept** → PDCU **Coordinator / Deputy** (and other `isDeliveryUnit()` users who can see the record) proceed to **Verify Performance Tracking** (Stage 5).
- **Reject** → Data Admin corrects data → back to **Pending Sector Head Approval** path after resubmit (Sector Head may need to approve again depending on rules in place).

---

## Stage 5 — PDCU Coordinator / final PDCU verification

**Who:** PDCU users with **`isDeliveryUnit()`** again — in practice **Coordinator** or **Deputy Coordinator** perform final **verification** using the **“Verify Performance Tracking”** modal on the KPI page. The modal posts to `DeliverableController::storeTracking` (`deliverable.tracking.save` route).

### Responsibilities / actions

- Review milestone, actual value, quarter, remarks, evidence.
- Enter **delivery department remark** (and related fields per form).
- Choose **Status** in the modal: **`Confirmed`** or **`Rejected`** (stored in `confirmation_status`).
- **Visibility rule:** PDCU users may be blocked from acting until the record is **visible to PDCU** (`PerformanceTracking::isVisibleToPDCU()` — requires Sector Head approval timestamp).

### Preconditions (controller)

- `DeliverableController::storeTracking` enforces that **PDCU** users may only verify records that are **approved by Sector Head** (aligned with `isVisibleToPDCU()`).

### Decision flow

| Modal outcome | Effect |
|---------------|--------|
| **Confirmed** | `confirmation_status` = `Confirmed` — record treated as finalized for locking checks that depend on `confirmation_status` + coordinator timestamp where applicable (`PerformanceTracking::isLockedFromSectorModification()`). |
| **Rejected** | `confirmation_status` = `Rejected` — negative terminal path from this modal. |

### What triggers “workflow complete”

- **`Confirmed`** (and related timestamps where the application sets them) — downstream reporting and “locked” behaviour treat **Coordinator-confirmed** tracking as immutable in several controllers (e.g. commitment/deliverable/KPI mutation guards).

---

## Approval & decision summary (quick reference)

| Step | Actor | Approve / decide | Next queue |
|------|-------|------------------|------------|
| 1 | PDCU | N/A (data entry) | Data Admin |
| 2 | Data Admin | N/A (submission) | Sector Head (`Pending Sector Head Approval`) |
| 3 | Sector Head | **Approve** (bulk) | Facilitator (`Pending Facilitator`) |
| 4 | Facilitator | **Accept** / **Reject** | Coordinator notification **or** Data Admin (reject) |
| 5 | PDCU (Coordinator / Deputy) | **Confirmed** / **Rejected** (verify modal) | End / corrections |

---

## Key code references

| Concern | Location |
|---------|----------|
| Save tracking (PDCU vs Data Admin) | `App\Http\Controllers\KpiController::storeTracking` |
| Sector Head bulk approve | `App\Http\Controllers\KpiController::approveData` |
| Facilitator accept/reject | `App\Http\Controllers\KpiController::facilitatorConfirm` |
| Final verify modal submit | `App\Http\Controllers\DeliverableController::storeTracking` |
| Notifications | `App\Models\Notification` (`notifySectorHeadForApproval`, `notifyFacilitatorAfterSectorHeadApproval`, `notifyCoordinatorAfterFacilitatorConfirmation`, `notifyDataAdminAfterFacilitatorRejection`) |
| Visibility / lock helpers | `App\Models\PerformanceTracking` (`isVisibleToPDCU`, `isLockedFromSectorModification`, …) |
| KPI UI (modals, buttons) | `resources/views/pages/sector/kpis.blade.php` |

---

## Document scope

This report reflects **application behaviour** as wired through controllers, models, and the KPI performance UI. If your organisation uses different role titles in policy (e.g. “PDCU Coordinator” only for Stage 5), map them to **`Coordinator` / `Deputy Coordinator`** accounts in the system for this final verification step.

*Generated from codebase review — adjust if business rules change outside the repository.*
