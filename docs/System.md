# SYSTEM.md — STRG System Blueprint

This document describes how the system ACTUALLY works.
It is the source of truth for understanding, debugging, and AI-assisted development.

---

# 1. CORE IDEA

This is a WORKFLOW SYSTEM, not a feature system.

Everything revolves around moving a request through a strict pipeline:

USER → STAFF1 → STAFF2 → STAFF1 (COMPLETED)

---

# 2. WORKFLOW STATES

Valid states:

* SUBMITTED
* STAFF1_REVIEWED
* STAFF2_APPROVED
* COMPLETED
* RETURNED
* DECLINED

Rules:

* RETURNED → goes back to USER
* DECLINED → final (no resubmit)
* COMPLETED → final (after manual processing)

---

# 3. ROLE RESPONSIBILITIES

## USER (admission)

* Creates request
* Fills forms (inline or manual)
* Uploads supporting documents (multiple)
* Signs if required
* Can edit ONLY when status = RETURNED

## STAFF1

* Reviews user submission
* Uses checklist to validate documents
* Can:

  * RETURN
  * DECLINE
  * FORWARD → STAFF1_REVIEWED
* Marks COMPLETED after manual processing

## STAFF2

* System owner
* Configures:

  * request types
  * templates
  * checklist items
  * zone designer (signature + field zones on PDF templates)
* Reviews after STAFF1
* Can:

  * RETURN
  * DECLINE
  * APPROVE → STAFF2_APPROVED
* Can override:

  * SUBMITTED → STAFF2_APPROVED

## ADMIN

* Manages users only
* Manages system-wide branding/settings via /admin/settings
* Does NOT participate in workflow

---

# 4. REQUEST LIFECYCLE (IMPORTANT)

## A. User Submission

Route:
POST /requests

Flow:

* RequestController@store
* Validate input (StoreRequestRequest)
* Create Request (status = SUBMITTED)
* Store documents (user_submission)
* Store signature (if required)
* Create audit log

---

## B. Staff1 Review

* Loads request + documents + checklist
* Checklist items come from request type

Rules:

* ALL required items must be checked
* If ANY item is flagged:
  → must RETURN or DECLINE

Action:

* FORWARD → STAFF1_REVIEWED

---

## C. Staff2 Review

* Reviews request + checklist result

Actions:

* RETURN → back to USER
* DECLINE → final
* APPROVE → STAFF2_APPROVED (requires signature)

Override:

* Can approve directly from SUBMITTED

---

## D. Completion

* STAFF1 marks request as COMPLETED
* This represents manual real-world processing

---

# 5. DATA MODEL (SIMPLIFIED)

## requests

* id
* user_id
* request_type_id
* status_id
* reference_id

## documents

* id
* request_id OR request_type_id
* document_type:

  * template
  * user_submission
  * staff_attachment
  * signed_document
* zones (JSON, nullable) — zone designer output, keyed by page index
* pdf_page_count (integer, nullable) — stored on upload via PdfInfoService
* signature_zones (JSON, nullable) — legacy, kept for fallback only
* field_zones (JSON, nullable) — legacy, kept for fallback only

## checklist_items

* defined by STAFF2 per request type
* only is_active = true items are enforced
* inactive items must NOT block forwarding

## checklist_reviews

* per request
* status: checked | flagged

## signatures

* user + staff2 signatures (normalized table)
* legacy columns on requests table kept as fallback only
* getSignatureImageForRole() prefers normalized table, falls back to legacy

## system_settings

* key (primary), value, type, group, label
* drives white-label branding (app name, logo, colors, institution name)
* cached via SettingsService, cache cleared on every update

---

# 6. SYSTEM RULES

* No deadlines
* No priority system
* No dean role or logic
* No system-generated forms

Documents are ALWAYS:

* templates (staff2)
* user submissions
* staff attachments
* signed_documents (output of DocumentSigningService after STAFF2_APPROVED)

---

# 7. PDF ZONE DESIGNER

Staff2 configures where signatures and field values are stamped on PDF templates.

## How It Works

* Staff2 uploads a PDF template
* PdfInfoService reads page count + dimensions via FPDI (no rendering)
* Zone designer view loads the PDF client-side via PDF.js (CDN)
* Staff2 draws zones on top of the rendered PDF canvas
* Zones saved as normalized ratios (nx, ny, nw, nh — values 0.0 to 1.0)
* On STAFF2_APPROVED, DocumentSigningService stamps the PDF using FPDI

## Zone Structure (stored in documents.zones)

```json
{
  "0": [
    { "id": 1234, "tool": "applicant_signature", "label": "Applicant Sig", "nx": 0.1, "ny": 0.8, "nw": 0.3, "nh": 0.05 }
  ],
  "1": [
    { "id": 5678, "tool": "field_project_title", "label": "Project Title", "nx": 0.1, "ny": 0.2, "nw": 0.6, "nh": 0.04 }
  ]
}
```

Keys are page indexes (0-based). tool values: applicant_signature | staff2_signature | field_{name}

## Coordinate System

* Zones stored as ratios (0-1) of the PDF.js rendered canvas size
* On stamping: nx * pageWidthMM = x position in mm for FPDI
* FPDI uses mm, top-left origin
* PDF points from getTemplateSize() convert to mm: mm = pt × 0.352778

## Environment Constraints (Dev — Laravel Herd, Windows, PHP 8.4)

* Imagick: NOT available (no DLL for PHP 8.4 on Herd)
* Ghostscript: NOT installed
* GD: available
* FPDI 2.6.6: installed
* TCPDF: NOT installed
* PDF-to-image conversion is NOT possible server-side in dev
* PDF.js handles all client-side rendering — no server image conversion needed
* Do NOT add Imagick, Ghostscript, or TCPDF dependencies without flagging this constraint

---

# 8. WHITE-LABEL SETTINGS SYSTEM

All branding is driven by the system_settings table, not hardcoded in views.

## Available Settings Keys

* app_name — system name shown in title/nav
* institution_name — university/org name
* institution_tagline — optional tagline
* app_logo — uploaded image path
* app_favicon — uploaded image path
* primary_color — hex color for CSS variable --color-primary
* accent_color — hex color for CSS variable --color-accent
* footer_text — footer content
* support_email — contact email
* mail_from_name — email sender name

## How It Works

* SettingsService::all() returns all settings keyed by key
* ViewComposer in AppServiceProvider shares $settings with every view
* Layout injects CSS variables into <head> from primary_color + accent_color
* Admin manages settings at /admin/settings
* Cache is cleared on every save via SettingsService::set()

## In Views

```blade
{{ $settings['app_name']->value ?? config('app.name') }}
{{ $settings['institution_name']->value ?? '' }}
```

## In PHP (non-blade)

```php
SettingsService::get('app_name', config('app.name'))
```

---

# 9. CRITICAL ENFORCEMENT POINTS

## Workflow

* ALL status changes must go through:
  → WorkflowTransitionService

## Race Condition Protection

* WorkflowTransitionService::executeTransition() opens DB::transaction FIRST
* Then acquires SELECT FOR UPDATE lock on the request row
* Then re-reads status_id from locked row
* Then calls canTransition() and validateTransitionRequirements()
* All validation, signature saves, audit log, notifications are INSIDE the transaction

## Checklist

* Must be validated BEFORE Staff1 forwards
* Only is_active = true checklist items are counted
* Inactive items must never silently block forwarding

## Signature Validation

* empty() check alone is NOT sufficient
* Server-side pixel analysis via GD must confirm signature is not a blank white canvas
* Threshold: >98% of pixels RGB > 245,245,245 = blank, reject

## Authorization

* Controlled via RequestPolicy
* Every controller action must call $this->authorize() or abort_if()

---

# 10. DEBUGGING MAP (USE THIS)

When something breaks:

1. What action triggered it?
2. Which controller ran?
3. What status changed?
4. What database row changed?
5. What service handled logic?

Trace:
Route → Controller → Service → Model → DB

---

# 11. COMMON FAILURE POINTS

* Status updated without validation
* Checklist not enforced properly (check is_active filter)
* Documents missing or mismatched
* User editing when not RETURNED
* Staff bypassing workflow
* File upload inconsistencies
* Zone coordinates wrong after save (check nx/ny normalization)
* PDF.js canvas offset bug (getBoundingClientRect on wrong element)
* FPDI stamping misaligned (check pt→mm conversion + Y axis)
* Settings cache stale (run php artisan cache:clear)
* Signature record outside transaction (must be inside DB::transaction)

---

# 12. TWO-VERSION NOTE

This system exists in two versions:

* 4-user version: admission, staff1, staff2, admin
* 5-user version: different role structure

Both share the same core workflow and architecture.
Both are published as separate GitHub repositories.
All hardcoded university branding has been replaced by the settings system.
Neither version references the original institution by name in code.

---

# 13. DEVELOPMENT RULE

Before changing anything:

Ask:

* Does this affect workflow?
* Does this affect status?
* Does this affect data integrity?

If YES → be careful

---

# FINAL NOTE

This system must remain:

* predictable
* enforceable
* workflow-driven

Do NOT optimize or extend unless it respects the workflow.