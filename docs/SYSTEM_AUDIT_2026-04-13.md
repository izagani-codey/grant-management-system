# System Audit - UniKL STRG

Date: 2026-04-13

Scope: Laravel 13 backend, Blade/Tailwind/Vite frontend, workflow domain, auth, data integrity, notifications, templates, and runtime operability.

Verification status:
- Static inspection completed across routes, controllers, requests, services, models, migrations, views, and tests.
- Automated verification is partially blocked in this shell because `php` is not callable from `PATH`.
- Result: this report is grounded in current code, but the full test suite was not executed from this terminal.

## System map

### Backend flow
- Entry routes live in `routes/web.php`.
- Main request lifecycle is handled in `app/Http/Controllers/RequestController.php`.
- Dean-specific actions also exist in `app/Http/Controllers/DeanController.php`.
- Validation sits in `app/Http/Requests/StoreRequestRequest.php`, `UpdateRequestRequest.php`, and `UpdateStatusRequest.php`.
- Workflow rules are centralized in `app/Services/WorkflowTransitionService.php`.
- Persistence is split across `app/Models/Request.php`, `AuditLog.php`, `Notification.php`, `Signature.php`, `RequestType.php`, and related migrations.
- Dashboards are served by `app/Http/Controllers/DashboardController.php` and `app/Services/DashboardService.php`.

### Frontend flow
- Layout shell is Blade-based under `resources/views/layouts/`.
- Request pages are in `resources/views/requests/`.
- Shared form rendering is in `resources/views/requests/layouts/form-base.blade.php`.
- Dynamic client logic is minimal and mostly inline in Blade, especially `resources/views/requests/partials/form-scripts.blade.php` and `resources/views/requests/show.blade.php`.
- JS boot entry is `resources/js/app.js`; CSS entry is `resources/css/app.css`.

### Workflow model
- Status enum: `app/Enums/RequestStatus.php`
- Transition engine: `app/Services/WorkflowTransitionService.php`
- Side effects:
  - notifications via `NotificationService` and direct `Notification::createForUser`
  - PDFs via `RequestPdfService`
  - signatures via legacy request columns plus normalized `signatures` table

## Findings - Now

### 1. Critical - runtime verification is blocked because PHP is not available from this shell
- Affected area: environment / operability
- Evidence:
  - `scripts/qa_check.ps1`
  - `README.md`
  - shell result: `php artisan test` fails with `php : The term 'php' is not recognized`
- Root cause:
  - The repo assumes `php` is globally callable, but this terminal does not have a usable PHP executable on `PATH`.
- User-visible impact:
  - Local debugging, migrations, tests, and artisan tooling fail immediately from this shell.
- How to reproduce manually:
  1. Run `php artisan test`
  2. Observe the command-not-found failure
- Exact fix approach:
  - Add the active Herd PHP binary to `PATH`, or invoke PHP through its absolute path.
  - After that, run `php artisan test`, `php artisan route:list`, and `php artisan migrate:status`.
  - Document the exact local PHP path in `README.md` if the team relies on Herd.
- Regression risk:
  - Low once fixed, but high operational friction until resolved.

### 2. Critical - submission and comment notifications depend on a `users.is_active` column that is not created by migrations
- Affected area: notifications / request submission
- Evidence:
  - `app/Services/NotificationService.php` uses `User::where('role', $role)->where('is_active', true)`
  - `database/migrations/0001_01_01_000000_create_users_table.php`
  - `database/migrations/2026_04_01_000001_add_staff_fields_to_users_table.php`
- Root cause:
  - Notification queries assume `users.is_active` exists, but the current migrations shown in this repo do not create that column.
- User-visible impact:
  - Any path calling `sendRoleNotification()` can fail at runtime on a fresh or correctly migrated database.
  - High-risk paths include request submission, resubmission, and comment notification fan-out.
- How to reproduce manually:
  1. Use a fresh database from current migrations.
  2. Submit a request as admission.
  3. The request reaches `NotificationService::sendRoleNotification()` and should error on the missing column.
- Exact fix approach:
  - Choose one:
    - Add a migration for `users.is_active` with a safe default and index.
    - Or remove the `is_active` filter and replace it with a status model that actually exists.
  - Then add a feature test covering admission submission plus notification creation.
- Regression risk:
  - High, because this sits on the main happy path.

### 3. High - admin users are routed to a dashboard view that does not exist
- Affected area: auth redirect / admin UX
- Evidence:
  - `app/Http/Controllers/DashboardController.php` returns `view('dashboard.' . $user->role, ...)`
  - available dashboard views under `resources/views/dashboard/` are `admission`, `staff1`, `staff2`, `dean`, `_dev-switcher`
  - no `resources/views/dashboard/admin.blade.php`
- Root cause:
  - Generic dashboard routing assumes every role has a matching Blade view, but admin does not.
- User-visible impact:
  - An admin hitting `/dashboard` after login or redirect can trigger a missing-view error.
- How to reproduce manually:
  1. Log in as an admin user.
  2. Visit `/dashboard`.
  3. Observe the missing `dashboard.admin` view failure.
- Exact fix approach:
  - Route admins explicitly to `admin.dashboard`, or add a real `dashboard.admin` view.
  - Align the login redirect and tests with the chosen behavior.
- Regression risk:
  - Medium-high, because it affects basic admin navigation.

### 4. High - dev quick-switch partial is included on non-local dashboards without guarding the route
- Affected area: frontend / environment-specific rendering
- Evidence:
  - unguarded includes in:
    - `resources/views/dashboard/admission.blade.php`
    - `resources/views/dashboard/staff1.blade.php`
    - `resources/views/dashboard/dean.blade.php`
  - guarded include exists in `resources/views/dashboard/staff2.blade.php`
  - `_dev-switcher` uses `route('dev.login')`
  - `routes/web.php` only defines `dev.login` inside `if (app()->environment('local'))`
- Root cause:
  - Local-only UI is rendered in views that can also be used outside local environments.
- User-visible impact:
  - Admission, staff1, and dean dashboards can crash in non-local environments when the route is absent.
  - Existing tests already hint at this with skip logic around a "dev switcher route issue".
- How to reproduce manually:
  1. Run the app with a non-local environment.
  2. Visit the admission, staff1, or dean dashboard.
  3. Blade attempts to resolve `route('dev.login')` and fails.
- Exact fix approach:
  - Guard all `_dev-switcher` includes with `app()->environment('local') && Route::has('dev.login')`.
  - Prefer moving the guard into the partial itself as a second safety layer.
- Regression risk:
  - High in staging/production, low once guarded consistently.

### 5. High - override mode is advertised as required, but backend transition rules allow the bypass even when override mode is off
- Affected area: workflow correctness / authorization
- Evidence:
  - `app/Services/WorkflowTransitionService.php`
    - `staff2` is allowed to move `SUBMITTED -> STAFF2_APPROVED`
    - `executeTransition()` marks this as override but never checks `user.override_enabled`
  - `resources/views/dashboard/staff2.blade.php` says override mode "allows you to bypass normal workflow restrictions"
  - `app/Http/Controllers/RequestController.php` exposes `toggleOverrideMode()`
- Root cause:
  - The transition map bakes the override path in permanently; the override toggle is not used as an authorization guard.
- User-visible impact:
  - Staff 2 can bypass Staff 1 regardless of override mode state.
  - UI and backend behavior diverge, creating false operational assumptions.
- How to reproduce manually:
  1. Create a request in `SUBMITTED`.
  2. Log in as staff2 with `override_enabled = false`.
  3. Patch `/requests/{id}/status` to `STAFF2_APPROVED` with a signature.
  4. The transition is accepted by current workflow logic.
- Exact fix approach:
  - Remove the direct `SUBMITTED -> STAFF2_APPROVED` transition from the default staff2 map.
  - Reintroduce it only when `user.override_enabled` is true and an override reason is supplied.
  - Add tests for both enabled and disabled override mode.
- Regression risk:
  - High, because this changes approval integrity.

### 6. High - request creation and request listing do not enforce the policy model consistently
- Affected area: authorization / route-controller-policy consistency
- Evidence:
  - `app/Policies/RequestPolicy.php`
    - `create()` allows only `admission`
    - `view()` allows only owner admission or staff1/staff2/dean
    - `viewAny()` excludes `admin`
  - `app/Http/Controllers/RequestController.php`
    - `create()` returns the form without calling `authorize('create', ...)`
    - `index()` returns all requests for non-admission users without `authorize('viewAny', ...)`
- Root cause:
  - Route access is `auth`-only for create/index, while policy restrictions are only partially enforced deeper in the controller.
- User-visible impact:
  - Roles that should be blocked can still open `/requests/create`.
  - Admin can list requests in `/requests` but cannot open a specific request because `show()` does enforce `view`.
- How to reproduce manually:
  1. Log in as `admin` or `staff1`.
  2. Visit `/requests/create`; the form renders.
  3. Log in as `admin` and visit `/requests`; requests list.
  4. Open one request; `show()` should deny access.
- Exact fix approach:
  - Add policy enforcement in `create()` and `index()`.
  - Decide whether admin should have request visibility; then update `RequestPolicy`, routes, and tests together.
- Regression risk:
  - Medium-high, because this affects both UX and access control.

### 7. High - dean UI offers two distinct return paths, but backend cannot represent or process the difference
- Affected area: workflow UX / domain correctness
- Evidence:
  - `resources/views/requests/show.blade.php`
    - buttons for `return_staff1` and `return_staff2`
    - both submit `status_id = RETURNED`
    - JS writes `dean_action`
  - `app/Http/Controllers/RequestController.php::updateStatus()`
    - payload ignores `dean_action`
  - `app/Services/WorkflowTransitionService.php`
    - no concept of return target
- Root cause:
  - The frontend models two distinct outcomes, but the domain model only has one `RETURNED` state with no return destination metadata.
- User-visible impact:
  - The two dean buttons are functionally identical.
  - Staff cannot tell whether the dean intended a return to Staff 1 or Staff 2.
- How to reproduce manually:
  1. Move a request to `STAFF2_APPROVED`.
  2. Open the dean action panel.
  3. Submit "Return to Staff 1" and then repeat with "Return to Staff 2" on a comparable request.
  4. Observe that both flows produce the same stored status and no routing metadata.
- Exact fix approach:
  - Choose one:
    - simplify the UI to a single dean return action
    - or add an explicit `return_target` field plus workflow handling and audit logging
  - Update notification text and request detail rendering to expose the target if you keep the split action.
- Regression risk:
  - Medium-high, because users can act on misleading UI today.

### 8. High - form template management UI exposes staff actions that the routes forbid
- Affected area: frontend/backend consistency / admin tooling
- Evidence:
  - `routes/web.php`
    - `GET /form-templates` is available to `staff1,staff2,dean`
    - `POST /form-templates` and `DELETE /form-templates/{id}` are admin-only
  - `resources/views/form-templates/index.blade.php`
    - upload UI is shown to `staff1` and `staff2`
    - delete button is shown to `staff2`
- Root cause:
  - The view grants actions by role checks that do not match the route middleware.
- User-visible impact:
  - Staff can see upload/delete controls and then hit 403 responses.
  - Admin can mutate templates through direct routes but has no matching index route in this file set.
- How to reproduce manually:
  1. Log in as `staff2`.
  2. Open `/form-templates`.
  3. Use the upload or delete controls.
  4. The action is forbidden by route middleware.
- Exact fix approach:
  - Align permissions in one direction:
    - either allow staff2 to manage templates in routes/controllers
    - or hide those controls and make the page read-only for staff roles
  - Add an admin-accessible index route or fold template management into the admin panel only.
- Regression risk:
  - Medium, but it creates visible broken UX right now.

### 9. Medium - dashboard request search logic can leak results outside intended filters
- Affected area: dashboard filtering / query correctness
- Evidence:
  - `app/Repositories/RequestRepository.php::getFilteredRequests()`
  - `applyRoleSpecificSearch()` appends `orWhereHas('user', ...)` without grouping
- Root cause:
  - The extra applicant-name/email search is added as a top-level `OR`, so it can bypass earlier filters.
- User-visible impact:
  - Staff dashboard request lists can include rows that match applicant name/email even when other filters should exclude them.
- How to reproduce manually:
  1. Apply filters on a dashboard that uses `displayRequests`.
  2. Search for a common applicant name or email fragment.
  3. Observe rows that match the user relation but not the earlier status/type/date constraints.
- Exact fix approach:
  - Wrap all search predicates in one grouped `where(...)` block.
  - Keep relation search inside that same grouped condition.
  - Add filter-combination tests for status + search and date + search.
- Regression risk:
  - Medium.

## Findings - Next

### 10. Medium - notification and PDF generation are synchronous in request paths
- Affected area: performance / UX latency
- Evidence:
  - `app/Http/Controllers/RequestController.php::store()` sends notifications and generates PDFs inline
  - `app/Services/WorkflowTransitionService.php` dispatches notifications and regenerates PDFs after transitions
  - `app/Services/RequestPdfService.php`
- Root cause:
  - Expensive side effects are executed in the same request cycle as user actions.
- User-visible impact:
  - Submission and approval actions will slow down as user count or PDF complexity grows.
- How to reproduce manually:
  1. Seed more staff users and larger templates.
  2. Submit or approve requests repeatedly.
  3. Measure action latency.
- Exact fix approach:
  - Queue notification fan-out and PDF generation.
  - Record immutable artifact references in the request/audit model.
  - Add retry-safe idempotency for jobs.
- Regression risk:
  - Medium during refactor, low after stabilization.

### 11. Medium - duplicate migrations create schema-history ambiguity
- Affected area: migrations / maintainability / deployment safety
- Evidence:
  - duplicate `default_template_id` migrations:
    - `database/migrations/2024_04_06_000001_add_default_template_id_to_request_types_table.php`
    - `database/migrations/2026_04_06_013248_add_default_template_id_to_request_types_table.php`
  - duplicate stage signature migrations:
    - `database/migrations/2024_04_06_000002_add_stage_signatures_to_requests_table.php`
    - `database/migrations/2026_04_06_013258_add_stage_signatures_to_requests_table.php`
- Root cause:
  - Historical duplication was preserved with `Schema::hasColumn()` guards instead of being normalized.
- User-visible impact:
  - Lower direct user impact, but higher deployment/debugging risk and harder schema provenance.
- How to reproduce manually:
  1. Review migration history.
  2. Note duplicated intent and guard-based no-op behavior.
- Exact fix approach:
  - Keep one canonical migration chain for future environments.
  - Add a migration-governance check in CI for duplicate schema intent.
- Regression risk:
  - Low immediate runtime risk, medium long-term deployment risk.

### 12. Medium - signature storage is duplicated across request columns and normalized table, but artifact lineage is not explicit
- Affected area: data integrity / auditability
- Evidence:
  - legacy signature columns in `app/Models/Request.php`
  - normalized table backfill in `database/migrations/2026_04_08_120000_create_signatures_table.php`
  - PDF generation in `app/Services/RequestPdfService.php`
- Root cause:
  - The system currently writes both legacy request-level signature fields and normalized `signatures` rows, while generated PDFs are not tied to a specific transition or signature set.
- User-visible impact:
  - Harder to prove which PDF matches which approval state after revisions or regenerated artifacts.
- How to reproduce manually:
  1. Approve a request with signatures.
  2. Regenerate or revisit the PDF.
  3. Compare stored signatures and generated files; lineage is implicit, not explicit.
- Exact fix approach:
  - Choose one canonical signature source.
  - Version PDFs by request revision or transition event.
  - Link audit logs to the exact generated artifact.
- Regression risk:
  - Medium.

## Findings - Later

### 13. Low - test suite and implementation have materially drifted
- Affected area: quality gate / trust in automation
- Evidence:
  - `tests/Feature/TemplateSystemTest.php` expects routes and columns that do not exist in current controllers
  - `tests/Feature/OverrideSystemTest.php` expects `override_logs`, `OverrideLog`, and behaviors not implemented in current code
  - `tests/Feature/AuthenticationSystemTest.php` contains skip logic around the dev switcher issue
- Root cause:
  - The product evolved, but the test suite was not kept aligned with the current route/model design.
- User-visible impact:
  - Defects can survive because test coverage cannot be trusted as-is.
- How to reproduce manually:
  1. Compare test expectations with current routes/controllers.
  2. Note missing endpoints and mismatched schemas.
- Exact fix approach:
  - Triage tests into:
    - still valid but broken
    - stale and should be rewritten
    - obsolete and should be deleted
  - Rebuild the workflow and template tests around current behavior before adding new features.
- Regression risk:
  - Medium overall, even if immediate user impact is indirect.

## No major defect found in this pass
- Notification open redirect safety in `app/Http/Controllers/NotificationController.php` is defensively implemented and rejects unsafe targets.
- Request document endpoints in `RequestController` do perform per-request authorization before serving files.
- Core workflow mutation is at least centralized in `WorkflowTransitionService`, which is a strong base for later hardening.

## Fix order

1. Restore a usable PHP executable in the shell and run the baseline suite.
2. Fix `users.is_active` mismatch or remove the dependency from notifications.
3. Guard or remove the dev quick-switch in non-local views.
4. Fix admin dashboard routing for `/dashboard`.
5. Enforce request policies consistently on create/index and decide the admin visibility model.
6. Correct override-mode enforcement in the transition engine.
7. Resolve the dean return-path mismatch by simplifying or modeling return targets.
8. Align template-management UI with actual route permissions.
9. Repair dashboard search grouping.
10. Queue notifications and PDFs after correctness issues are resolved.

## Manual debugging guide

### Backend: how to trace a bug
- Start at `routes/web.php` and identify the exact route and middleware.
- Open the controller action and note:
  - authorization call
  - FormRequest class
  - service calls
  - redirect target
- Open the FormRequest and check:
  - `authorize()`
  - `rules()`
  - `prepareForValidation()`
  - post-validation hooks
- Open the service next if business logic exists there.
- Then inspect:
  - model casts
  - fillable/guarded fields
  - relevant migrations
  - the Blade template that consumes the data

### Frontend: how to trace a bug
- Start from the rendered Blade view under `resources/views/...`.
- Search for:
  - inline forms and hidden fields
  - `data-*` attributes
  - inline `<script>` blocks
  - included partials
- For request forms specifically:
  - `resources/views/requests/layouts/form-base.blade.php`
  - `resources/views/requests/partials/form-scripts.blade.php`
  - `resources/views/requests/show.blade.php`
- In the browser:
  - inspect the actual form payload
  - confirm hidden `status_id`, signature, and action fields before submit
  - watch the Network tab for 302/403/422 responses

### Commands to run once PHP is available
```powershell
php artisan test
php artisan test --filter RequestWorkflowTest
php artisan route:list
php artisan migrate:status
php artisan tinker
```

### High-value manual scenarios
- Admission submits a request with signature and attachment.
- Staff1 verifies, returns, and comments.
- Staff2 attempts an override with override mode off, then on.
- Dean uses both return buttons and compare stored outcome.
- Staff2 opens `/form-templates` and tries the visible upload/delete actions.
- Admin logs in and hits `/dashboard`.

## Final note

The most important theme in the current codebase is not raw complexity. It is contract drift:
- routes vs policies
- UI vs backend permissions
- workflow labels vs stored state
- services vs migrations
- tests vs actual implementation

That is the right target for the next round of fixes.
