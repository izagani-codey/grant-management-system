# AGENTS.md — STRG Workflow System Rules (STRICT)

This file defines how AI must behave when working on this system.

This is a WORKFLOW-DRIVEN system. Do NOT introduce logic outside this workflow.

---

# 1. CORE WORKFLOW (SOURCE OF TRUTH)

The system MUST follow:

USER → STAFF1 → STAFF2 → STAFF1 → COMPLETED

Allowed states:

* SUBMITTED
* STAFF1_REVIEWED
* STAFF2_APPROVED
* COMPLETED
* RETURNED
* DECLINED

---

# 2. SYSTEM PRINCIPLE

This system is NOT a form generator.

* Users submit documents
* Templates are reference/download files
* System may provide input fields, but does NOT generate official documents

---

# 3. ROLE DEFINITIONS

## USER

* Downloads templates (optional)
* Fills documents externally OR via system fields
* Uploads:

  * completed documents
  * supporting documents (multiple allowed)
* Signs if required
* Can ONLY edit when status = RETURNED

---

## STAFF1

* Reviews all submitted documents
* Uses checklist defined by STAFF2

Checklist rules:

* ALL required items must be checked before forwarding
* Only is_active = true items are enforced
* Inactive items must NEVER silently block forwarding
* If ANY item is flagged:
  → must RETURN or DECLINE

Actions:

* RETURN
* DECLINE
* FORWARD → STAFF1_REVIEWED

After STAFF2 approval:

* Prints documents
* Handles manual processing
* Marks COMPLETED

---

## STAFF2

* System owner and final validator

Responsibilities:

* Upload templates
* Define checklist items
* Define required fields
* Define supporting document requirements
* Configure VOT (enabled/disabled per request type)
* Configure signature requirement
* Configure zone designer (signature + field zones on PDF templates)

Actions:

* RETURN
* DECLINE
* APPROVE → STAFF2_APPROVED (requires signature)

Override:

* Can move SUBMITTED → STAFF2_APPROVED directly

---

## ADMIN

* Manages users only
* Manages system-wide branding via /admin/settings
* Does NOT participate in workflow

---

# 4. VOT RULES

* VOT is OPTIONAL and controlled by STAFF2
* VOT must ONLY appear when enabled for that request type
* Do NOT assume VOT is always required

---

# 5. DOCUMENT RULES

Documents are ALWAYS one of:

1. template (uploaded by STAFF2)
2. user_submission (uploaded by USER)
3. staff_attachment (uploaded by STAFF2 per request)
4. signed_document (output of DocumentSigningService after STAFF2_APPROVED)

DO NOT:

* Generate system documents outside DocumentSigningService
* Modify template files automatically
* Store documents outside the documents table

---

# 6. WORKFLOW ENFORCEMENT

ALL status changes MUST go through:
→ WorkflowTransitionService

### Transaction Order (CRITICAL — do not change)

```
DB::transaction(function() {
    $request = Request::lockForUpdate()->findOrFail($id);  // 1. Lock row
    // Re-read status from locked row
    canTransition(...)                                      // 2. Validate
    validateTransitionRequirements(...)                     // 3. Enforce rules
    saveStageSignatures(...)                                // 4. Save signatures
    $request->update(['status_id' => ...])                 // 5. Write status
    AuditLog::create(...)                                  // 6. Audit
    dispatchNotifications(...)                             // 7. Notify
});
```

NEVER:

* Open the transaction after validation
* Call canTransition() before the transaction opens
* Update status directly in a controller
* Put AuditLog::create() or Signature::updateOrCreate() outside the transaction
* Trust frontend logic for status

---

# 7. CHECKLIST RULES

* Checklist is defined by STAFF2 per request type
* Checklist must be enforced in backend
* Only is_active = true items count toward enforcement
* Required items (is_active = true) must be completed before forwarding
* Flagged items block forwarding
* If an item is deactivated after submission it must NOT silently block forwarding

---

# 8. SIGNATURE RULES

* empty() alone is NOT sufficient to validate a signature
* Server-side GD pixel analysis MUST confirm the canvas is not blank
* Blank threshold: >98% of pixels with RGB > 245, 245, 245 = blank → reject
* Both applicant and staff2 signatures stored in normalized signatures table
* Legacy columns on requests table are fallback only — do not write to them directly

---

# 9. ZONE DESIGNER RULES

* Zones are stored as normalized ratios: nx, ny, nw, nh (floats 0.0–1.0)
* Keys in the zones JSON are 0-based page indexes
* PDF rendering is client-side via PDF.js (CDN) — no server-side image conversion
* Do NOT attempt to render PDFs to images server-side in this environment
* Coordinate conversion for stamping: nx × pageWidthMM = x in mm (FPDI uses mm, top-left origin)
* PDF points → mm: pt × 0.352778
* Minimum zone size: 20px wide, 10px tall (enforced in JS before saving)
* Zones must be re-displayed correctly when the zone designer is reopened (load from documents.zones)

---

# 10. WHITE-LABEL SETTINGS RULES

* No university name, logo, or branding may be hardcoded in any view or PHP file
* All branding comes from system_settings via SettingsService
* In Blade: `{{ $settings['key']->value ?? 'fallback' }}`
* In PHP: `SettingsService::get('key', 'fallback')`
* Cache must be cleared on every settings save — SettingsService::set() handles this
* Image uploads for logo/favicon go to storage/public/branding/
* CSS color variables are injected in the layout <head> from primary_color and accent_color

---

# 11. DEV ENVIRONMENT RULES (Laravel Herd, Windows, PHP 8.4)

* Imagick: NOT available
* Ghostscript: NOT installed
* TCPDF: NOT installed
* GD: available
* FPDI 2.6.6: installed

BEFORE suggesting any new dependency, state:

1. What the dependency does
2. Whether it conflicts with the Herd/Windows/PHP 8.4 environment
3. Whether GD + FPDI can handle the same task

Do NOT silently add composer require for Imagick, Ghostscript, or TCPDF.

---

# 12. BEFORE ANY CODE CHANGE

AI MUST first output:

1. What will be changed
2. Which files are affected
3. What logic is impacted
4. Risks introduced

DO NOT write code before this plan is approved.

---

# 13. CODE RULES

* Make minimal changes
* Do NOT refactor unrelated code
* Do NOT introduce new abstractions unless required
* Prefer clarity over complexity
* No comments unless the WHY is non-obvious
* Status checks in views: always use enum cases, never hardcoded integers
* Notifications: always through NotificationService, never dispatched from controllers directly

---

# 14. SECURITY RULES

* Validate all inputs server-side
* Validate file uploads (type AND mime, not just extension)
* Do NOT trust frontend state
* Remove any dev/debug routes before publishing
* All zone designer routes require staff2 authorization
* All settings routes require admin authorization
* servePdf() must verify document_type = 'template' before serving

---

# 15. REMOVED — NEVER ADD BACK

* deadline field on requests
* is_priority / priority system
* dean role, DeanController, dean dashboard
* dean_* columns (signature_data, signed_at, approved_by, approved_at, notes, rejection_reason)
* snapshot_requires_dean_signature column
* DRAFT, DEAN_APPROVED, REJECTED, STAFF1_APPROVED enum cases
* trulyApproved(), notTrulyComplete(), pendingDeanReview() scopes
* isUrgent(), daysUntilDeadline(), updatePriorityFromDeadline(), requiresDeanSignature() methods
* RequestTypeWorkflowPolicy model and table
* Hardcoded university name/logo/branding in any file

---

# 16. RESPONSE STYLE

* Be direct and technical
* Focus on THIS system only
* Do NOT give generic Laravel explanations
* Highlight risks when present
* Show diffs, not full file rewrites, unless the full file is small

---

# FINAL RULE

If anything is unclear:
→ STOP and ask before proceeding