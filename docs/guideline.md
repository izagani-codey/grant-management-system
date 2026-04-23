# STRG System — LLM Development Guidelines

This document is the authoritative context brief for AI assistants (Claude, etc.) working on this codebase. Read it fully before making any changes.

---

## System Overview

**STRG System** is a Laravel-based grant request management platform for a university. Users submit grant requests which flow through a two-stage staff review process before completion.

**Tech stack:** Laravel 11, PHP 8.4, SQLite (dev) / MySQL (prod), Tailwind CSS, Blade, Alpine.js, FPDI 2.6.6, GD (PHP extension), PDF.js (CDN), Laravel Herd (local dev, Windows).

---

## Dev Environment Constraints (CRITICAL — read before adding dependencies)

| Capability | Status |
|---|---|
| GD (image manipulation) | ✅ Available |
| FPDI 2.6.6 (PDF read/write) | ✅ Installed |
| Imagick | ❌ Not available — no DLL for PHP 8.4 on Herd |
| Ghostscript | ❌ Not installed on Windows dev machine |
| TCPDF | ❌ Not installed |
| PDF-to-image (server-side) | ❌ Not possible in dev environment |

**Do NOT install or require Imagick, Ghostscript, or TCPDF without explicitly flagging this constraint and getting confirmation.**

PDF page rendering is handled client-side via PDF.js (CDN). No server-side PDF-to-image conversion exists or is needed.

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

**Removed cases (do not re-add):** `DRAFT (1)`, `DEAN_APPROVED`, `STAFF1_APPROVED`, `REJECTED`

---

## Roles

| Role | Description |
|------|-------------|
| `admission` | End user — submits and edits requests |
| `staff1` | First reviewer — verifies documents, sends to Staff2 or returns/declines. Also marks COMPLETED after printing |
| `staff2` | Final approver — approves with signature, returns, declines, or overrides Staff1. Configures zone designer. |
| `admin` | System administrator — manages users, request types, templates, system settings/branding |

**Removed roles (do not re-add):** `dean`

---

## Key Files

### Core Logic
- `app/Enums/RequestStatus.php` — Status enum with helpers (`isFinal()`, `canBeActionedByStaff1()`, etc.)
- `app/Services/WorkflowTransitionService.php` — Single-entry-point for all status transitions. Opens DB::transaction FIRST, then SELECT FOR UPDATE, then validates.
- `app/Policies/RequestPolicy.php` — Authorization for all request actions.
- `app/Models/Request.php` — Main model. `$fillable` does NOT include `deadline`, `is_priority`, or any `dean_*` fields.

### New Services (added during development)
- `app/Services/PdfInfoService.php` — Reads PDF page count and dimensions using FPDI. No rendering. Used by zone designer controller.
- `app/Services/SettingsService.php` — Static get/set/all methods for system_settings. Uses Cache::rememberForever, clears cache on set.

### Controllers
- `app/Http/Controllers/RequestController.php` — CRUD + status updates + document management
- `app/Http/Controllers/DocumentController.php` — Staff2 per-request document upload/download/delete
- `app/Http/Controllers/Staff2AdminController.php` — Admin panel (user management, request types, stats) + zone designer methods (showZoneDesigner, saveZones, servePdf)
- `app/Http/Controllers/Admin/SettingsController.php` — System settings CRUD at /admin/settings

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
- `resources/views/staff2/zone-designer.blade.php` — PDF zone designer UI (PDF.js + Alpine.js)
- `resources/views/admin/settings.blade.php` — White-label branding settings page

### Models
- `app/Models/SystemSetting.php` — key (primary), value, type, group, label. No timestamps. Cached via SettingsService.

### Services & Repositories
- `app/Services/NotificationService.php` — All notification dispatch
- `app/Services/DocumentSigningService.php` — Stamps signatures + field values onto PDF using FPDI + GD. Uses zones JSON column, falls back to legacy signature_zones/field_zones.
- `app/Repositories/StatisticsRepository.php` — Dashboard stat queries (uses new status enum values)
- `app/Repositories/RequestRepository.php` — Filtered request queries
- `app/View/Components/DashboardFilters.php` — Status filter options per role

---

## Zone Designer — How It Works

Staff2 configures signing zones on PDF templates. The zone designer is at:
`GET /staff2/templates/{document}/zones`

### Flow
1. Staff2 opens zone designer for a template document
2. `showZoneDesigner()` calls `PdfInfoService::getPageCount()` if `pdf_page_count` is null
3. View loads PDF via `GET /staff2/templates/{document}/pdf` (served by `servePdf()`)
4. PDF.js (CDN) renders the PDF to a `<canvas>` client-side
5. Staff2 draws zones by clicking and dragging on the canvas
6. Zones are stored as normalized ratios: `nx, ny, nw, nh` (0.0–1.0)
7. On save, `POST /staff2/templates/{document}/zones` writes to `documents.zones`

### Zone JSON Structure
```json
{
  "0": [{ "id": 1234, "tool": "applicant_signature", "label": "Applicant Sig", "page": 0, "nx": 0.1, "ny": 0.8, "nw": 0.3, "nh": 0.05 }],
  "1": [{ "id": 5678, "tool": "field_project_title", "label": "Project Title", "page": 1, "nx": 0.1, "ny": 0.2, "nw": 0.6, "nh": 0.04 }]
}
```

Keys are 0-based page indexes. `tool` values: `applicant_signature` | `staff2_signature` | `field_{fieldname}`

### Coordinate Conversion (stamping)
- `x = zone.nx × pageWidthMM`
- `y = zone.ny × pageHeightMM`
- `w = zone.nw × pageWidthMM`
- `h = zone.nh × pageHeightMM`
- FPDI uses mm, top-left origin
- PDF points → mm: `mm = pt × 0.352778`

### Routes Added
```
GET  /staff2/templates/{document}/zones     → showZoneDesigner   → staff2.zones.edit
POST /staff2/templates/{document}/zones     → saveZones          → staff2.zones.save
GET  /staff2/templates/{document}/pdf       → servePdf           → staff2.zones.pdf
```

---

## White-Label Settings System

All branding is driven by the `system_settings` table. No hardcoded university names in views.

### Settings Keys
| Key | Type | Group | Purpose |
|-----|------|-------|---------|
| app_name | text | general | Title/nav system name |
| institution_name | text | branding | Org name in views |
| institution_tagline | text | branding | Optional tagline |
| app_logo | image | branding | Uploaded logo path |
| app_favicon | image | branding | Uploaded favicon path |
| primary_color | color | branding | CSS var --color-primary |
| accent_color | color | branding | CSS var --color-accent |
| footer_text | text | general | Footer content |
| support_email | text | general | Contact email |
| mail_from_name | text | email | Email sender name |

### In Blade Views
```blade
{{ $settings['app_name']->value ?? config('app.name') }}
```

### In PHP
```php
SettingsService::get('app_name', config('app.name'))
```

### CSS Variables (injected in layout <head>)
```html
<style>
  :root {
    --color-primary: {{ $settings['primary_color']->value ?? '#1d4ed8' }};
    --color-accent:  {{ $settings['accent_color']->value  ?? '#7c3aed' }};
  }
</style>
```

### Routes Added
```
GET  /admin/settings  → SettingsController@index
POST /admin/settings  → SettingsController@update
```

---

## Known Fixed Issues (do not reintroduce)

### 1. Race Condition on Concurrent Transitions
`WorkflowTransitionService::executeTransition()` now:
- Opens `DB::transaction()` FIRST
- Acquires `Request::lockForUpdate()->findOrFail()` inside the transaction
- Re-reads status from locked row before calling `canTransition()`
- All validation, signatures, audit log, notifications are inside the transaction

### 2. Direct Status Write Bypass
`RequestController::update()` (resubmission) no longer writes `status_id` directly.
All RETURNED → SUBMITTED transitions go through `WorkflowTransitionService`.

### 3. Signature + AuditLog Outside Transaction
In `store()` and `update()`, `Signature::updateOrCreate()` and `AuditLog::create()` are inside the DB transaction.

### 4. Inactive Checklist Items Blocking Forwarding
`hasAllRequiredItemsChecked()` filters to `is_active = true` items only.
Inactive items do not appear in enforcement and do not silently block forwarding.

### 5. Blank Signature Bypass
Server-side GD pixel analysis rejects signatures where >98% of pixels are near-white (RGB > 245, 245, 245). `empty()` check alone is not sufficient.

---

## Database Notes

### documents table additions
- `zones` (json, nullable) — zone designer output
- `pdf_page_count` (integer, nullable) — populated by PdfInfoService on upload
- `signature_zones` and `field_zones` kept as legacy fallback columns

### system_settings table
- `key` (string, primary), `value` (text, nullable), `type` (string), `group` (string), `label` (string)

### Migration History
The revamp migration (`2026_04_20_000001_revamp_workflow_and_add_documents_table.php`) dropped all dean/deadline columns and created the `documents` table.

### SQLite Quirk (Dev)
When dropping columns that have indexes, use try/catch around `dropIndex()` before dropping the column.

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
- **VOT items** — `requires_vot` flag on `RequestType`. Show VOT section only when true.
- **Signature requirement** — `requires_signature` flag on `RequestType`.

### Signature Flow
- Users sign on `create`/`edit` forms using canvas-based pad
- Staff2 must provide signature when approving (`STAFF2_APPROVED`)
- Both stored in the normalized `signatures` table
- Legacy columns on `requests` table kept as fallback only
- Server-side blank signature detection via GD pixel analysis

### Documents
- **System-wide templates**: `form_templates` table, per `RequestType` via pivot
- **Per-request documents** (Staff2 uploads): `documents` table managed via `DocumentController`
- **User uploaded files**: Stored in `payload.additional_documents[]` (JSON array of storage paths)
- **Signed documents**: output of `DocumentSigningService`, `document_type = signed_document`

---

## Common Mistakes to Avoid

1. **Using old enum cases**: `STAFF1_APPROVED`, `DEAN_APPROVED`, `REJECTED`, `DRAFT` — these don't exist
2. **Accessing `$request->deadline`** — column removed
3. **Accessing `$request->is_priority`** — column removed
4. **Calling `trulyApproved()` / `notTrulyComplete()` scopes** — removed
5. **Hardcoding university name in views** — use `$settings['institution_name']->value`
6. **Using `rejection_reason`** — renamed to `return_reason` (RETURNED) and `decline_reason` (DECLINED)
7. **Allowing `UpdateRequestRequest` when not RETURNED** — `authorize()` checks `isReturned()`
8. **Changing status_id directly in a controller** — always use WorkflowTransitionService
9. **Putting Signature::updateOrCreate() or AuditLog::create() outside DB::transaction()** — both must be inside
10. **Counting inactive checklist items in hasAllRequiredItemsChecked()** — filter is_active = true only
11. **Using empty() alone for signature validation** — must also run GD pixel blank check
12. **Installing Imagick/Ghostscript/TCPDF without flagging the dev environment constraint**
13. **Hardcoding zone coordinates** — always use normalized nx/ny/nw/nh ratios

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
13. Zone designer saves zones and reloads them correctly on revisit
14. Stamped PDF has signature/fields at correct position matching zone placement
15. Blank signature rejected server-side (not just client-side)
16. Settings changes reflect immediately after save (cache cleared)
17. Two concurrent Staff1 transitions on same request — only one succeeds