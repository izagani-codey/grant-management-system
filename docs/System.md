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
* Reviews after STAFF1
* Can:

  * RETURN
  * DECLINE
  * APPROVE → STAFF2_APPROVED
* Can override:

  * SUBMITTED → STAFF2_APPROVED

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

## checklist_items

* defined by STAFF2 per request type

## checklist_reviews

* per request
* status: checked | flagged

## signatures

* user + staff2 signatures

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

---

# 7. CRITICAL ENFORCEMENT POINTS

## Workflow

* ALL status changes must go through:
  → WorkflowTransitionService

## Checklist

* Must be validated BEFORE Staff1 forwards

## Authorization

* Controlled via RequestPolicy

---

# 8. DEBUGGING MAP (USE THIS)

When something breaks:

1. What action triggered it?
2. Which controller ran?
3. What status changed?
4. What database row changed?
5. What service handled logic?

Trace:
Route → Controller → Service → Model → DB

---

# 9. COMMON FAILURE POINTS

* Status updated without validation
* Checklist not enforced properly
* Documents missing or mismatched
* User editing when not RETURNED
* Staff bypassing workflow
* File upload inconsistencies

---

# 10. DEVELOPMENT RULE

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
