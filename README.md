# ~UniKL MIIT — STRG Request Management System~
~> **Short Term Research Grant (STRG) Request Management System** — A Laravel-based workflow platform for managing grant requests at Universiti Kuala Lumpur MIIT, covering multi-stage approvals, digital signatures, automated PDF generation, and a full audit trail.

> **AI-Assisted Development** — This system was developed with the assistance of AI coding tools:
> [Claude](https://claude.ai) · [ChatGPT](https://chat.openai.com) · [Windsurf](https://codeium.com/windsurf)

---

## Table of Contents

- [System Overview](#system-overview)
- [Key Features](#key-features)
- [Technical Architecture](#technical-architecture)
- [Installation & Setup](#installation--setup)
- [User Roles & Workflow](#user-roles--workflow)
- [Template & Document System](#template--document-system)
- [Development Guide](#development-guide)
- [AI Assistant Configuration](#ai-assistant-configuration)
- [Testing](#testing)
- [Deployment](#deployment)
- [Troubleshooting](#troubleshooting)

---

## System Overview

The UniKL MIIT STRG System digitises the full grant request lifecycle — from initial submission by academic staff through multi-level review, digital sign-off, and final PDF generation. It replaces paper-based processes with a transparent, auditable workflow.

### Business Objectives

- **Eliminate paperwork** — All forms, signatures, and documents are handled digitally
- **Enforce accountability** — Every action is logged in an immutable audit trail
- **Speed up approvals** — Role-based dashboards surface the right requests to the right people
- **Ensure compliance** — Workflow rules are enforced by code, not by convention

### System Capabilities

- Multi-role access control (Admission, Staff 1, Staff 2, Dean, Admin)
- Dynamic request types with configurable field schemas
- Dual signature-count workflows (2-signature and 3-signature paths)
- Real-time in-app notifications per role
- Automated PDF generation with embedded digital signatures
- System-generated templates **and** admin-uploaded supporting documents — managed separately
- CSV / Excel export of request data

---

## Key Features

### Workflow Engine

- **State machine** with 7 statuses: Draft → Submitted → Staff1 Approved → Staff2 Approved → Dean Approved / Returned / Rejected
- **Single transition service** (`WorkflowTransitionService`) is the only code path that changes request status — no scattered status updates
- **Policy-based authorisation** (`RequestPolicy`) controls who can view, edit, sign, or change status
- **Staff 2 override** — can approve a request that is still at Submitted status (bypasses Staff 1) for urgent cases
- **Revision cycle** — Returned requests can be edited and resubmitted; revision count is tracked

### Signature System

- Digital signature capture via canvas on browser (no third-party service required)
- Applicant signs at submission; Staff 2 and Dean sign at approval
- Signatures stored in a normalised `signatures` table (role: `applicant` / `staff2` / `dean`)
- Legacy columns on the `requests` table kept for backward compatibility
- Signature requirement is snapshotted at submission time (`snapshot_requires_dean_signature`) so policy changes don't affect in-flight requests

### Document Processing

- **System-generated PDF** — filled automatically from request data using DomPDF; layout switches between 2-signature and 3-signature based on the workflow policy
- **Admin-uploaded templates** — blank form files linked to a request type (`template_type = 'request_type_form'`)
- **Admin-uploaded supporting documents** — reference files linked to a request type (`template_type = 'supporting_document'`); shown separately from the generated form
- **Applicant-uploaded documents** — main file and additional attachments stored per request

### Administrative Tools

- User management with role assignment
- Request type management with configurable field schemas and VOT item support
- Template and supporting document upload with request-type linking
- Priority flagging (auto-assigned based on deadline proximity; manually overridable)
- Full audit log visible on each request

---

## Technical Architecture

### Technology Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 12 · PHP 8.3+ |
| Frontend | Blade · Tailwind CSS · Vite · Alpine.js |
| Database | SQLite (dev) · MySQL (production) |
| PDF Generation | DomPDF (barryvdh/laravel-dompdf) |
| Spreadsheet Export | PhpSpreadsheet · maatwebsite/excel |
| Authentication | Laravel Breeze (session-based) |

### Application Structure

```
app/
├── Enums/
│   └── RequestStatus.php          # 7-state workflow enum
├── Http/
│   ├── Controllers/               # RequestController, FormTemplateController, etc.
│   └── Requests/                  # StoreRequestRequest, UpdateStatusRequest, etc.
├── Models/
│   ├── Request.php                # Core request model
│   ├── RequestType.php            # Request type + getDefaultTemplate()
│   ├── RequestTypeTemplate.php    # Pivot: request type ↔ template + signature_layout
│   ├── RequestTypeWorkflowPolicy.php
│   ├── FormTemplate.php           # Templates and supporting documents
│   ├── Signature.php              # Normalised multi-role signatures
│   ├── AuditLog.php
│   ├── Notification.php
│   └── VotCode.php
├── Policies/
│   └── RequestPolicy.php          # view / changeStatus / revise / print
└── Services/
    ├── WorkflowTransitionService.php   # Single entry point for all status changes
    ├── RequestPdfService.php           # PDF generation
    ├── NotificationService.php         # Role-based notifications
    └── ExcelExportService.php
```

### Database

- **38 migration files** covering all tables, indexes, and incremental schema changes
- Key tables: `requests`, `request_types`, `form_templates`, `request_type_templates`, `signatures`, `audit_logs`, `notifications`, `vot_codes`, `comments`
- Unique constraint on `signatures(request_id, role)` — one signature per role per request

---

## Installation & Setup

### Prerequisites

- PHP 8.3+
- Composer 2.x
- Node.js 18+
- SQLite 3.x (dev) or MySQL 8.0+ (production)

### Quick Start

```bash
# 1. Install PHP dependencies
composer install

# 2. Install JS dependencies
npm install

# 3. Configure environment
cp .env.example .env
php artisan key:generate

# 4. Create and seed the database
php artisan migrate --seed

# 5. Link public storage (for uploaded files and generated PDFs)
php artisan storage:link

# 6. Start the dev server (runs Laravel + Vite together)
composer run dev
```

### Environment Configuration

```env
APP_NAME="UniKL MIIT STRG"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# SQLite (default for local dev)
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# Mail — use Mailpit locally for testing notifications
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_FROM_ADDRESS="noreply@unikl.edu.my"
MAIL_FROM_NAME="UniKL MIIT STRG"
```

---

## User Roles & Workflow

### Roles

| Role | What they do |
|---|---|
| **Admission** | Submit new requests, revise returned requests, track own request status |
| **Staff 1** | Verify submitted requests, forward to Staff 2 or return with notes |
| **Staff 2** | Review, sign, and recommend requests; override capability for urgent cases |
| **Dean** | Final sign-off for 3-signature request types |
| **Admin** | User management, request type configuration, template/document upload |

### Workflow State Machine

```
SUBMITTED ──► STAFF1_APPROVED ──► STAFF2_APPROVED ──► DEAN_APPROVED
    │               │                    │
    │           RETURNED ◄───────────────┘
    │               │
    └──────────► REJECTED
    │
    └──► STAFF2_APPROVED  (Staff 2 override, bypasses Staff 1)

RETURNED ──► SUBMITTED  (Applicant resubmits after revision)
```

**Signature requirements by role:**
- Staff 1 — no signature required (verification only)
- Staff 2 — signature required to approve or reject
- Dean — signature required to approve or reject (only for 3-signature request types)

### Demo Accounts

All demo accounts use password: `password`

| Role | Email |
|---|---|
| Admission | admissions@unikl.edu.my |
| Staff 1 | staff1@unikl.edu.my |
| Staff 2 | staff2@unikl.edu.my |
| Dean | dean@unikl.edu.my |
| Admin | admin@unikl.edu.my |

---

## Template & Document System

The system keeps two distinct types of files under `FormTemplate`, separated by `template_type`:

| Type | `template_type` value | Purpose |
|---|---|---|
| System Template | `request_type_form` | Blank form file linked to a request type. Used as the basis for PDF generation. Selected based on `signature_layout` (2 or 3 signatures). |
| Supporting Document | `supporting_document` | Admin-uploaded reference files (guidelines, instructions). Shown alongside a request — not filled or modified. |

**Template selection logic** (`RequestType::getDefaultTemplate($signatureLayout)`):
1. Look for a template with the matching `signature_layout` (`two_signatures` or `three_signatures`)
2. Fall back to the request type's `default_template_id`
3. Fall back to any template marked `is_default = true`

**Signature layout determination:**
- At submission, `RequestTypeWorkflowPolicy.requires_dean_signature` is snapshotted onto `Request.snapshot_requires_dean_signature`
- This snapshot drives PDF layout for the lifetime of that request, independent of any future policy change

---

## Development Guide

### Key Commands

```bash
# Run all tests
php artisan test

# Run a specific test class
php artisan test --filter UserManagementTest

# Generate test coverage report
php artisan test --coverage

# Code style (Laravel Pint)
./vendor/bin/pint

# Static analysis (PHPStan)
./vendor/bin/phpstan analyse

# Clear all caches
php artisan optimize:clear

# Refresh database with seed data
php artisan migrate:fresh --seed
```

### Adding a New Request Type

1. Log in as **Admin** → Admin Dashboard → Request Types → Add New
2. Set name, description, field schema (JSON), and whether VOT items are required
3. Set the workflow policy (requires dean signature: yes/no)
4. Upload a system template file and link it with the correct `signature_layout`
5. Optionally upload supporting documents for the request type

### Adding a New Workflow Step

All status transitions are controlled in `WorkflowTransitionService::getAllowedTransitions()`. Add the new `role → [from_status => [to_statuses]]` entry there. The policy, audit log, notifications, and PDF regeneration are handled automatically by `executeTransition()`.

---

## AI Assistant Configuration

### Claude Settings Setup

This project includes AI assistant configuration files for Claude development assistance:

1. **Copy example configuration:**
   ```bash
   cp .claude/settings.local.json.example .claude/settings.local.json
   ```

2. **Configure your local paths:**
   Edit `.claude/settings.local.json` and replace the placeholders:
   - `${PHP_PATH}` - Path to your PHP installation (e.g., `/c/Users/username/.config/herd/bin`)
   - `${PROJECT_PATH}` - Full path to the project directory (e.g., `C:\Users\username\Herd\STRG_system_new`)

3. **Example configuration:**
   ```json
   {
     "permissions": {
       "allow": [
         "Bash(${PHP_PATH}/php84 artisan:*)",
         "Bash(cmd.exe /c \"cd /d ${PROJECT_PATH} && php artisan --version 2>&1\")",
         "Bash(powershell.exe -Command \"Set-Location '${PROJECT_PATH}'; php artisan --version 2>&1\")"
       ]
     }
   }
   ```

**Important:** `.claude/settings.local.json` is excluded from version control via `.gitignore` to prevent committing personal configuration data.

---

## Testing

```bash
# Full test suite
php artisan test

# Feature tests only
php artisan test tests/Feature

# With coverage (requires Xdebug or PCOV)
php artisan test --coverage --min=70
```

Key test files:
- `tests/Feature/UserManagementTest.php` — user creation, role assignment
- Add request workflow tests in `tests/Feature/RequestWorkflowTest.php`

---

## Deployment

### Production Checklist

```bash
# Install production dependencies (no dev packages)
composer install --no-dev --optimize-autoloader

# Build frontend assets
npm ci && npm run build

# Set environment
cp .env.example .env
# → Set APP_ENV=production, APP_DEBUG=false, DB_* for MySQL

php artisan key:generate
php artisan migrate --force
php artisan storage:link

# Cache config, routes, and views for performance
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Security Checklist Before Go-Live

- [ ] `APP_DEBUG=false` in `.env`
- [ ] `APP_ENV=production`
- [ ] Strong `APP_KEY` generated
- [ ] Database credentials are not default values
- [ ] `storage/` and `bootstrap/cache/` are writable by the web server only
- [ ] HTTPS configured (SSL certificate installed)
- [ ] Mail credentials set for notification delivery
- [ ] `php artisan storage:link` run so uploaded files are publicly accessible

---

## Troubleshooting

**Uploaded files not showing / 404 on documents**
```bash
php artisan storage:link
```

**Blank PDF generated**
- Confirm at least one `FormTemplate` is linked to the request type with the correct `signature_layout`
- Check `storage/app/public/requests/pdf/` is writable

**Login redirect loop**
```bash
php artisan config:clear
php artisan cache:clear
```

**Database out of sync**
```bash
# Development only — destroys all data
php artisan migrate:fresh --seed
```

**Permission errors on Windows (Herd)**
- Herd manages permissions automatically; restart Herd if storage writes fail

**Vite assets not loading**
```bash
npm run build
# or for hot reload:
npm run dev
```

---

## License & Support

This system is developed for internal use at **Universiti Kuala Lumpur MIIT**.  
All rights reserved © {{ date('Y') }} Universiti Kuala Lumpur.

---

## AI Assistance

This project was built with AI coding assistance:

| Tool | Primary Use |
|---|---|
| **Claude** (Anthropic) | Architecture, code review, bug fixes, audit, documentation |
| **ChatGPT** (OpenAI) | Feature planning, code generation |
| **Windsurf** (Codeium) | In-editor autocomplete and pair programming |

---

*Last Updated: April 2026 · Version: 1.0.0*
~
