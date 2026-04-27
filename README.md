# STRG Request Management System

> A Laravel-based workflow platform for managing grant requests, covering multi-stage approvals, digital signatures, and a full audit trail.

> **AI-Assisted Development** — Built with [Claude](https://claude.ai) · [ChatGPT](https://chat.openai.com) · [Windsurf](https://codeium.com/windsurf)

---

## Table of Contents

- [Overview](#overview)
- [Key Features](#key-features)
- [Technical Architecture](#technical-architecture)
- [Installation](#installation)
- [Roles & Workflow](#roles--workflow)
- [Document System](#document-system)
- [Development](#development)
- [Testing](#testing)
- [Deployment](#deployment)
- [Troubleshooting](#troubleshooting)
- [License](#license)

---

## Overview

The STRG System digitises the full grant request lifecycle — from initial submission through multi-level review, digital sign-off, and completion. It replaces paper-based processes with a transparent, auditable workflow.

**Goals:**
- All forms, signatures, and documents handled digitally
- Every action logged in an immutable audit trail
- Role-based dashboards surface the right requests to the right people
- Workflow rules enforced by code, not convention

---

## Key Features

### Workflow Engine

- **State machine** with 6 statuses: `SUBMITTED → STAFF1_REVIEWED → STAFF2_APPROVED → COMPLETED` (plus `RETURNED` and `DECLINED`)
- **Single transition service** (`WorkflowTransitionService`) is the only code path that changes request status
- **Policy-based authorisation** (`RequestPolicy`) controls who can view, edit, sign, or change status
- **Staff 2 override** — can approve a request still at `SUBMITTED`, bypassing Staff 1; role alone grants capability
- **Revision cycle** — `RETURNED` requests can be edited and resubmitted

### Checklist Enforcement

- Staff 2 configures checklist items per request type
- Staff 1 must mark every item checked or flagged before forwarding
- Backend validates checklist completion as part of the transition — a UI-only bypass is impossible

### Signature System

- Digital signature capture via canvas (no third-party service required)
- Applicant signs at submission; Staff 2 signs at approval
- Signatures stored in a normalised `signatures` table (roles: `applicant`, `staff2`)
- Staff 2 signature enforced server-side before `STAFF2_APPROVED` transition is allowed

### Administrative Tools

- User management with role assignment (Admin only)
- Request type configuration: field schemas, VOT requirement, signature requirement, required documents
- Template and supporting document upload per request type (Staff 2)
- Checklist item management per request type (Staff 2)
- Full audit log on every request

---

## Technical Architecture

### Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 11 · PHP 8.3+ |
| Frontend | Blade · Tailwind CSS · Vite · Alpine.js |
| Database | SQLite (dev) · MySQL (production) |
| Authentication | Laravel Breeze (session-based) |

### Application Structure

```
app/
├── Enums/
│   ├── RequestStatus.php          # 6-state workflow enum
│   └── DocumentType.php           # template / user_submission / staff_attachment
├── Http/
│   ├── Controllers/               # RequestController, DocumentController, etc.
│   ├── Middleware/                 # RoleMiddleware, PerformanceMonitoring
│   └── Requests/                  # StoreRequestRequest, UpdateStatusRequest, etc.
├── Models/
│   ├── Request.php                # Core request model
│   ├── RequestType.php            # Request type config (field schema, VOT, checklist)
│   ├── Document.php               # Unified document model (3 types)
│   ├── ChecklistItem.php          # Per-request-type checklist items
│   ├── ChecklistReview.php        # Staff 1 review result per checklist item
│   ├── Signature.php              # Normalised multi-role signatures
│   ├── AuditLog.php
│   ├── Notification.php
│   └── VotCode.php
├── Policies/
│   └── RequestPolicy.php          # view / changeStatus / revise / addComment / review / print
└── Services/
    ├── WorkflowTransitionService.php   # Single entry point for all status changes
    ├── DashboardService.php            # Role-specific dashboard data
    ├── NotificationService.php         # Role-based in-app notifications
    └── RequestService.php
```

### Database

Key tables: `requests`, `request_types`, `documents`, `checklist_items`, `checklist_reviews`, `signatures`, `audit_logs`, `notifications`, `vot_codes`, `comments`, `users`

---

## Installation

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

# 5. Link public storage
php artisan storage:link

# 6. Start the dev server (Laravel + Vite)
composer run dev
```

### Environment

Copy `.env.example` to `.env` and set at minimum:

```env
APP_NAME="STRG System"
APP_ENV=local
APP_DEBUG=false
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=strg
DB_USERNAME=root
DB_PASSWORD=
```

---

## Roles & Workflow

### Roles

| Role | Responsibilities |
|---|---|
| **Admission** | Submit new requests, revise returned requests, track own request status |
| **Staff 1** | Verify submitted requests, complete checklist review, forward to Staff 2 or return with notes |
| **Staff 2** | Review and approve requests, manage request types, templates, checklists, and VOT codes |
| **Admin** | User management and role assignment only |

### Workflow State Machine

```
SUBMITTED ──► STAFF1_REVIEWED ──► STAFF2_APPROVED ──► COMPLETED
    │               │                    │
    └───────────────┴────────────────► RETURNED
    │               │
    └───────────────┴────────────────► DECLINED
    │
    └──► STAFF2_APPROVED  (Staff 2 override — bypasses Staff 1)

RETURNED ──► SUBMITTED  (Applicant revises and resubmits)
```

**Key rules:**
- Staff 1 cannot forward until all checklist items are reviewed
- Staff 2 must have a saved signature before approving
- All transitions go through `WorkflowTransitionService` — no direct status updates anywhere else

### Demo Accounts

All demo accounts use the password `password`.

| Role | Email |
|---|---|
| Admission | admission@example.com |
| Staff 1 | staff1@example.com |
| Staff 2 | staff2@example.com |
| Admin | admin@example.com |

---

## Document System

All files are stored in the unified `documents` table, separated by `document_type`:

| Type | Who uploads | Purpose |
|---|---|---|
| `template` | Staff 2 | Blank form files linked to a request type; shown to Admission when creating a request |
| `user_submission` | Admission | Files attached to a specific request at submission or revision |
| `staff_attachment` | Staff 2 | Files attached to a specific request during review |

Downloads are always routed through `documents.download` (authenticated, logged) — never via a direct `storage/` URL.

---

## Development

### Commands

```bash
# Run all tests
php artisan test

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

1. Log in as **Staff 2** → Dashboard → Request Types → Add New
2. Set name, description, field schema (JSON), and whether VOT items and/or a signature are required
3. Upload template files and link them to the request type
4. Add checklist items for Staff 1 to review

### Adding a New Workflow Transition

All status transitions are defined in `WorkflowTransitionService::getAllowedTransitions()`. Add the new `role → [from_status => [to_statuses]]` entry there. Audit logging, notifications, and requirement validation are handled automatically by `executeTransition()`.

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

---

## Deployment

```bash
# Install production dependencies
composer install --no-dev --optimize-autoloader

# Build frontend assets
npm ci && npm run build

# Configure environment
cp .env.example .env
# Set APP_ENV=production, APP_DEBUG=false, DB_* for MySQL

php artisan key:generate
php artisan migrate --force
php artisan storage:link

# Cache config, routes, and views
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Pre-launch checklist:**

- [ ] `APP_DEBUG=false`
- [ ] `APP_ENV=production`
- [ ] Strong `APP_KEY` generated
- [ ] Database credentials are not default values
- [ ] `storage/` and `bootstrap/cache/` are writable by the web server only
- [ ] HTTPS configured
- [ ] Mail credentials set for notification delivery

---

## Troubleshooting

**Uploaded files not showing / 404 on documents**
```bash
php artisan storage:link
```

**Login redirect loop**
```bash
php artisan config:clear && php artisan cache:clear
```

**Database out of sync**
```bash
# Development only — destroys all data
php artisan migrate:fresh --seed
```

**Vite assets not loading**
```bash
npm run build
```

---

## License

MIT License — see [LICENSE](LICENSE) for details.

---

*Version 2.0.0*
