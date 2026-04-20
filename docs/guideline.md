# STRG System — LLM Development Guidelines

This document is the authoritative context brief for AI assistants (Claude, etc.) working on this codebase. Read it fully before making any changes.

---

## System Overview

**STRG System** is a Laravel-based grant request management platform for a university. Users submit grant requests which flow through a two-stage staff review process before completion.

**Tech stack:** Laravel 11, PHP 8.4, SQLite (dev) / MySQL (prod), Tailwind CSS, Blade, Alpine.js-lite (vanilla JS), DomPDF, Herd (local dev).

---

## Workflow (Source of Truth)

```
USER submits
    │
    ▼
[SUBMITTED]
    │
    ├─ Staff1 returns → [RETURNED] → User re-edits → [SUBMITTED]
    ├─ Staff1 declines → [DECLINED] (final — no resubmit)
    │
    ▼
[STAFF1_REVIEWED]
    │
    ├─ Staff2 returns → [RETURNED] → User re-edits → [SUBMITTED]
    ├─ Staff2 declines → [DECLINED] (final)
    │
    ▼
[STAFF2_APPROVED]
    │  (Staff1 is notified — "ready for manual processing/printing")
    ▼
[COMPLETED] ← Staff1 marks done after manual process

Staff2 override: SUBMITTED → STAFF2_APPROVED directly (if Staff1 absent)
```

---

## Status Enum (`app/Enums/RequestStatus.php`)

| Value | Case | Label | Color | Final |
|-------|------|-------|-------|-------|
| 2 | `SUBMITTED` | Submitted | Orange | ❌ |
| 3 | `STAFF1_REVIEWED` | Checked by Staff 1 | Blue | ❌ |
| 4 | `STAFF2_APPROVED` | Approved by Staff 2 | Green | ❌ |
| 5 | `COMPLETED` | Completed | Teal | ✅ |
| 6 | `RETURNED` | Returned for Revision | Yellow | ❌ |
| 7 | `DECLINED` | Declined | Red | ✅ |

**Removed cases (do not re-add):** `DRAFT (1)`, `DEAN_APPROVED (5)`, `STAFF1_APPROVED`, `REJECTED`

---

## Roles

| Role | Description |
|------|-------------|
| `admission` | End user — submits and edits requests |
| `staff1` | First reviewer — verifies documents, sends to Staff2 or returns/declines. Also marks COMPLETED after printing |
| `staff2` | Final approver — approves with signature, returns, declines, or overrides Staff1 |
| `admin` | System administrator — manages users, request types, templates |

**Removed roles (do not re-add):** `dean`

---

## Key Files

### Core Logic
- `app/Enums/RequestStatus.php` — Status enum with helpers (`isFinal()`, `canBeActionedByStaff1()`, etc.)
- `app/Services/WorkflowTransitionService.php` — Single-entry-point for all status transitions. All workflow changes go through here.
- `app/Policies/RequestPolicy.php` — Authorization for all request actions.
- `app/Models/Request.php` — Main model. `$fillable` does NOT include `deadline`, `is_priority`, or any `dean_*` fields.

### Controllers
- `app/Http/Controllers/RequestController.php` — CRUD + status updates + document management
- `app/Http/Controllers/DocumentController.php` — Staff2 per-request document upload/download/delete
- `app/Http/Controllers/Staff2AdminController.php` — Admin panel (user management, request types, stats)

### Form Requests
- `app/Http/Requests/StoreRequestRequest.php` — VOT is conditional on `$requestType->requires_vot`
- `app/Http/Requests/UpdateRequestRequest.php` — Only authorized when request is `RETURNED`
- `app/Http/Requests/UpdateStatusRequest.php` — Requires `return_reason` for RETURNED, `decline_reason` for DECLINED

### Views
- `resources/views/requests/show.blade.php` — Main request detail page with all action panels
- `resources/views/requests/edit.blade.php` — Edit form for RETURNED requests
- `resources/views/requests/layouts/form-base.blade.php` — Shared create/edit form layout
- `resources/views/components/request-timeline.blade.php` — 4-step timeline with RETURNED/DECLINED branches
- `resources/views/dashboard/staff1.blade.php` — Staff1 queue + "Ready for Processing" alert
- `resources/views/dashboard/staff2.blade.php` — Staff2 queue + export tools
- `resources/views/dashboard/admission.blade.php` — User dashboard with RETURNED banner

### Services & Repositories
- `app/Services/NotificationService.php` — All notification dispatch
- `app/Repositories/StatisticsRepository.php` — Dashboard stat queries (uses new status enum values)
- `app/Repositories/RequestRepository.php` — Filtered request queries
- `app/View/Components/DashboardFilters.php` — Status filter options per role

---

## Important Constraints

### What was REMOVED — never add back
- `deadline` field on requests
- `is_priority` / priority system
- `dean_signature_data`, `dean_signed_at`, `dean_approved_by`, `dean_approved_at`, `dean_notes`, `dean_rejection_reason` columns
- `snapshot_requires_dean_signature` column
- `DeanController`, `dean.blade.php` dashboard, `dean` role
- `RequestTypeWorkflowPolicy` model and table
- `DRAFT`, `DEAN_APPROVED`, `REJECTED`, `STAFF1_APPROVED` enum cases
- Scope methods: `trulyApproved()`, `notTrulyComplete()`, `pendingDeanReview()`
- Request model methods: `isUrgent()`, `daysUntilDeadline()`, `updatePriorityFromDeadline()`, `requiresDeanSignature()`

### What is Optional (by request type)
- **VOT items** — `requires_vot` flag on `RequestType`. Show VOT section only when true. Validated conditionally.
- **Dean signature** — Done manually by Staff1, not in system.

### Signature Flow
- Users sign on `create`/`edit` forms using canvas-based pad (stored as base64 in `signatures` table).
- Staff2 must provide signature when approving (`STAFF2_APPROVED`).
- Both stored in the normalized `signatures` table, not as raw base64 on the request.

### Documents
- **System-wide templates**: `form_templates` table, per `RequestType` via pivot. Shown to users on dashboard/create page.
- **Per-request documents** (Staff2 uploads): `documents` table (`request_id`, `uploaded_by`, `uploader_role`, `file_path`, `original_name`, `is_template`). Managed via `DocumentController`.
- **User uploaded files**: Stored in `payload.additional_documents[]` (JSON array of storage paths).

---

## Database Notes

### Migration History
The revamp migration (`2026_04_20_000001_revamp_workflow_and_add_documents_table.php`) dropped all dean/deadline columns and created the `documents` table. Run `php artisan migrate` if setting up fresh.

### SQLite Quirk (Dev)
When dropping columns that have indexes, use try/catch around `dropIndex()` before dropping the column — SQLite doesn't support direct column drops with named indexes.

---

## Code Style Rules

- **No comments** unless the WHY is non-obvious.
- **No unnecessary abstractions** — three similar lines beats a premature helper.
- **Blade views**: use `@if`/`@php` for conditional rendering. Avoid inline JS data passing via hidden inputs when AJAX is available.
- **Status checks in views**: always use enum cases (`RequestStatus::STAFF1_REVIEWED->value`) not hardcoded integers.
- **Notifications**: all notification logic lives in `NotificationService`. Do not dispatch notifications from controllers directly.
- **Audit logs**: all status transitions are logged via `AuditLog::create()` inside `WorkflowTransitionService`.

---

## Common Mistakes to Avoid

1. **Using old enum cases**: `STAFF1_APPROVED`, `DEAN_APPROVED`, `REJECTED`, `DRAFT` — these don't exist. Use `STAFF1_REVIEWED`, `STAFF2_APPROVED`, `COMPLETED`, `DECLINED`.
2. **Accessing `$request->deadline`** — column removed.
3. **Accessing `$request->is_priority`** — column removed.
4. **Calling `trulyApproved()` / `notTrulyComplete()` scopes** — removed. Use `where('status_id', RequestStatus::COMPLETED->value)` directly.
5. **Hardcoding priority badge / deadline in views** — both removed from all dashboard tables.
6. **Using `rejection_reason`** — renamed to `return_reason` (for RETURNED) and `decline_reason` (for DECLINED).
7. **Allowing `UpdateRequestRequest` when not RETURNED** — the `authorize()` method checks `isReturned()`.

---

## Testing Checklist

When making changes to the workflow, verify:

1. User submits → status `SUBMITTED`, Staff1 queue updated
2. Staff1 returns → status `RETURNED`, user sees banner + edit button
3. User resubmits → status back to `SUBMITTED`, `revision_count` incremented
4. Staff1 declines → status `DECLINED`, user sees reason, cannot edit
5. Staff1 approves → status `STAFF1_REVIEWED`, Staff2 queue updated
6. Staff2 returns/declines → same user behavior as Staff1
7. Staff2 approves (with signature) → status `STAFF2_APPROVED`, Staff1 notified
8. Staff2 override → `SUBMITTED → STAFF2_APPROVED` directly
9. Staff1 marks complete → status `COMPLETED`
10. Timeline component shows correct step for each status
11. VOT section hidden for request types with `requires_vot = false`
12. Staff2 can upload/delete per-request documents; user can download them
