# Code Review - UniKL STRG

Date: 2026-04-13

## Findings

### 1. Critical - request submission and comment fan-out can fail on fresh databases because notification queries depend on a missing `users.is_active` column
Impact: any code path that calls role-based notifications can break at runtime, including request submission, resubmission, and comment fan-out. This is on the main application path, so the blast radius is high.

Evidence:
- [NotificationService.php](/c:/Users/amru2/Herd/my-app/app/Services/NotificationService.php:285) filters by `where('is_active', true)`
- [NotificationService.php](/c:/Users/amru2/Herd/my-app/app/Services/NotificationService.php:270) does the same for system-wide notifications
- [create_users_table.php](/c:/Users/amru2/Herd/my-app/database/migrations/0001_01_01_000000_create_users_table.php:1) does not create `is_active`
- [add_staff_fields_to_users_table.php](/c:/Users/amru2/Herd/my-app/database/migrations/2026_04_01_000001_add_staff_fields_to_users_table.php:1) still does not create `is_active`

Why this is a bug:
- The service assumes schema that is not present in the migrations currently in the repo. On a clean environment, this should fail as soon as a request tries to notify staff.

Test note:
- Missing test for the actual schema/service contract.
- Current automated verification is blocked here because `php` is not callable from this shell.

### 2. High - `/dashboard` is not safe for `admin` users because the controller resolves a view that does not exist
Impact: admin login and navigation can land on a missing-view error instead of a working dashboard. This is a direct break in a primary entrypoint.

Evidence:
- [DashboardController.php](/c:/Users/amru2/Herd/my-app/app/Http/Controllers/DashboardController.php:20) returns `view('dashboard.' . $user->role, ...)`
- [dashboard directory](/c:/Users/amru2/Herd/my-app/resources/views/dashboard) contains `admission`, `staff1`, `staff2`, and `dean`, but no `admin`
- [AuthenticationSystemTest.php](/c:/Users/amru2/Herd/my-app/tests/Feature/AuthenticationSystemTest.php:229) expects admin `/dashboard` to be OK

Why this is a bug:
- The controller implementation and available Blade views do not match for the `admin` role.

Test note:
- Existing tests appear to assert the intended behavior, but the current implementation does not provide the required view.

### 3. High - request authorization is inconsistent: create/index allow access that policy code and tests say should be forbidden
Impact: roles can reach pages they should not be able to use, and admin can list requests even though they may be blocked from viewing an individual request. This creates confusing UX and weakens the permission model.

Evidence:
- [RequestPolicy.php](/c:/Users/amru2/Herd/my-app/app/Policies/RequestPolicy.php:38) allows `create()` only for `admission`
- [RequestPolicy.php](/c:/Users/amru2/Herd/my-app/app/Policies/RequestPolicy.php:30) excludes `admin` from `viewAny()`
- [RequestController.php](/c:/Users/amru2/Herd/my-app/app/Http/Controllers/RequestController.php:72) `create()` does not call `authorize('create', ...)`
- [RequestController.php](/c:/Users/amru2/Herd/my-app/app/Http/Controllers/RequestController.php:29) `index()` does not call `authorize('viewAny', ...)`
- [RequestWorkflowTest.php](/c:/Users/amru2/Herd/my-app/tests/Feature/RequestWorkflowTest.php:178) expects non-admission roles to be forbidden from `/requests/create`
- [UserManagementTest.php](/c:/Users/amru2/Herd/my-app/tests/Feature/UserManagementTest.php:161) also expects non-admission roles to be forbidden from request creation

Why this is a bug:
- The route/controller behavior does not enforce the policy contract that the codebase and tests are already expressing.

Test note:
- Covered, but currently likely failing or stale relative to implementation.

### 4. High - Staff 2 override mode is represented in the UI and user model, but the workflow engine does not actually require it
Impact: Staff 2 can bypass Staff 1 even with override mode disabled. That undermines approval integrity and makes the UI’s safety messaging misleading.

Evidence:
- [WorkflowTransitionService.php](/c:/Users/amru2/Herd/my-app/app/Services/WorkflowTransitionService.php:28) allows `SUBMITTED -> STAFF2_APPROVED` for `staff2`
- [WorkflowTransitionService.php](/c:/Users/amru2/Herd/my-app/app/Services/WorkflowTransitionService.php:76) marks the path as an override but never checks `override_enabled`
- [RequestController.php](/c:/Users/amru2/Herd/my-app/app/Http/Controllers/RequestController.php:611) exposes `toggleOverrideMode()`
- [staff2 dashboard](/c:/Users/amru2/Herd/my-app/resources/views/dashboard/staff2.blade.php:76) presents override mode as the gate for bypass behavior
- [OverrideSystemTest.php](/c:/Users/amru2/Herd/my-app/tests/Feature/OverrideSystemTest.php:130) expects disabled override mode to block the bypass

Why this is a bug:
- The transition engine authorizes the bypass independently of the override toggle, so the toggle is currently cosmetic from an enforcement perspective.

Test note:
- Covered by tests in intent, but the current code does not implement the expected guard.

### 5. High - the dean action UI exposes two different return paths, but both collapse to the same backend outcome
Impact: users are told they can return a request to Staff 1 or Staff 2, but the system stores only a generic `RETURNED` state. The action distinction is lost immediately.

Evidence:
- [requests/show.blade.php](/c:/Users/amru2/Herd/my-app/resources/views/requests/show.blade.php:610) renders separate `return_staff1` and `return_staff2` actions
- [requests/show.blade.php](/c:/Users/amru2/Herd/my-app/resources/views/requests/show.blade.php:623) still submits only a single `status_id`
- [RequestController.php](/c:/Users/amru2/Herd/my-app/app/Http/Controllers/RequestController.php:421) ignores `dean_action`
- [WorkflowTransitionService.php](/c:/Users/amru2/Herd/my-app/app/Services/WorkflowTransitionService.php:204) only understands one `returned` transition outcome

Why this is a bug:
- The UI claims a distinction that the data model and workflow engine do not preserve.

Test note:
- Missing test for the dean return-target distinction.

### 6. High - template-management UI and routing rules disagree about who can mutate templates
Impact: Staff users are shown upload/delete controls that route middleware forbids. This creates visible broken actions and makes the permission model hard to trust.

Evidence:
- [routes/web.php](/c:/Users/amru2/Herd/my-app/routes/web.php:86) allows template index to `staff1,staff2,dean`
- [routes/web.php](/c:/Users/amru2/Herd/my-app/routes/web.php:124) restricts template create/delete to `admin`
- [form-templates/index.blade.php](/c:/Users/amru2/Herd/my-app/resources/views/form-templates/index.blade.php:8) shows upload UI for `staff1` and `staff2`
- [form-templates/index.blade.php](/c:/Users/amru2/Herd/my-app/resources/views/form-templates/index.blade.php:176) shows delete UI for `staff2`

Why this is a bug:
- The frontend promises actions the backend will reject with `403`.

Test note:
- Existing template tests appear stale or incompatible with current routing. Several tests expect endpoints like download, activate/deactivate, analytics, and admission access patterns that are not present in current `routes/web.php` and `FormTemplateController`.

### 7. Medium - local-only dev-switcher rendering is inconsistently guarded and can break non-local dashboards
Impact: some dashboards can fail in non-local environments because the partial assumes `dev.login` exists, while the route is only defined in local.

Evidence:
- [routes/web.php](/c:/Users/amru2/Herd/my-app/routes/web.php:18) defines `dev.login` only in local
- [dashboard/admission.blade.php](/c:/Users/amru2/Herd/my-app/resources/views/dashboard/admission.blade.php:23) includes the dev switcher unconditionally
- [dashboard/staff1.blade.php](/c:/Users/amru2/Herd/my-app/resources/views/dashboard/staff1.blade.php:22) includes it unconditionally
- [dashboard/dean.blade.php](/c:/Users/amru2/Herd/my-app/resources/views/dashboard/dean.blade.php:156) includes it unconditionally
- [dashboard/staff2.blade.php](/c:/Users/amru2/Herd/my-app/resources/views/dashboard/staff2.blade.php:30) is the only one that guards with `app()->environment('local') && Route::has('dev.login')`
- [AuthenticationSystemTest.php](/c:/Users/amru2/Herd/my-app/tests/Feature/AuthenticationSystemTest.php:217) already contains skip logic referencing a dev-switcher route issue

Why this is a bug:
- Route availability and view rendering are environment-dependent, but only one dashboard defends against that condition.

Test note:
- Covered indirectly by fragile tests; current tests are signaling the problem instead of reliably verifying behavior.

### 8. Medium - dashboard search composition is logically unsafe because applicant search is added as a top-level `OR`
Impact: filtered dashboards can return rows outside the intended filter set when the applicant relation matches the search term.

Evidence:
- [RequestRepository.php](/c:/Users/amru2/Herd/my-app/app/Repositories/RequestRepository.php:20) applies filters first
- [RequestRepository.php](/c:/Users/amru2/Herd/my-app/app/Repositories/RequestRepository.php:46) `applyRoleSpecificSearch()` appends `orWhereHas('user', ...)`

Why this is a bug:
- The relation search is not grouped with the original search clause, so it can escape the earlier constraints.

Test note:
- Missing targeted tests for combined filters plus applicant-name/email search.

### 9. Medium - the test suite has materially drifted from the implementation, reducing its value as a safety net
Impact: even if the suite were runnable, a meaningful portion of it appears to validate a different system shape. That makes failures noisy and passes less trustworthy.

Evidence:
- [TemplateSystemTest.php](/c:/Users/amru2/Herd/my-app/tests/Feature/TemplateSystemTest.php:328) expects activate/deactivate endpoints not present in current routes
- [TemplateSystemTest.php](/c:/Users/amru2/Herd/my-app/tests/Feature/TemplateSystemTest.php:516) expects `/admin/templates/analytics`, which is not defined
- [TemplateSystemTest.php](/c:/Users/amru2/Herd/my-app/tests/Feature/TemplateSystemTest.php:300) expects template download and pagination/search flows not implemented in the current controller
- [OverrideSystemTest.php](/c:/Users/amru2/Herd/my-app/tests/Feature/OverrideSystemTest.php:7) imports `OverrideLog`, which is not used in the current runtime implementation

Why this is a risk:
- The repo contains strong signals that tests and app code are out of sync. That raises the cost of debugging and lowers confidence in future refactors.

Test note:
- Existing tests appear stale/incompatible in several subsystems.

## Assumptions

- This review is based on the current repository state, not a pull request diff.
- I treated the current code as source of truth and used older docs only to avoid repeating prior wording.
- I could not run `php artisan test` from this terminal because `php` is not available on `PATH`.

## Residual Risk

- The highest-risk areas remain workflow authorization, environment-sensitive dashboard rendering, and schema/service drift in notifications.
- The repo also needs a test-suite triage pass before automated verification can be trusted as a release gate.
