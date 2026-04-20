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

Actions:

* RETURN
* DECLINE
* APPROVE → STAFF2_APPROVED (requires signature)

Override:

* Can move SUBMITTED → STAFF2_APPROVED directly

---

## ADMIN

* Manages users only
* Does NOT participate in workflow

---

# 4. VOT RULES

* VOT is OPTIONAL and controlled by STAFF2
* VOT must ONLY appear when enabled for that request type
* Do NOT assume VOT is always required

---

# 5. DOCUMENT RULES

Documents are ALWAYS:

1. template (uploaded by STAFF2)
2. user_submission (uploaded by USER)
3. staff_attachment (uploaded by STAFF2 per request)

DO NOT:

* Generate system documents
* Modify template files automatically
* Store documents outside the documents table

---

# 6. WORKFLOW ENFORCEMENT

ALL status changes MUST go through:
→ WorkflowTransitionService

Before changing status:

* Validate current status
* Validate user role
* Validate checklist completion (Staff1 → Staff2)

NEVER:

* Update status directly in controller
* Trust frontend logic

---

# 7. CHECKLIST RULES

* Checklist is defined by STAFF2 per request type
* Checklist must be enforced in backend
* Required items must be completed before forwarding
* Flagged items block forwarding

---

# 8. BEFORE ANY CODE CHANGE

AI MUST first output:

1. What will be changed
2. Which files are affected
3. What logic is impacted
4. Risks introduced

DO NOT write code before this plan is approved.

---

# 9. CODE RULES

* Make minimal changes
* Do NOT refactor unrelated code
* Do NOT introduce new abstractions unless required
* Prefer clarity over complexity

---

# 10. SECURITY RULES

* Validate all inputs server-side
* Validate file uploads (type, size)
* Do NOT trust frontend state
* Remove any dev/debug routes

---

# 11. RESPONSE STYLE

* Be direct and technical
* Focus on THIS system only
* Do NOT give generic Laravel explanations
* Highlight risks when present

---

# FINAL RULE

If anything is unclear:
→ STOP and ask before proceeding
