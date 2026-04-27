# Grant Management System

A full-stack Laravel application for managing grant requests through a structured multi-stage approval pipeline. Built for organisations that need a traceable, paperless workflow — from initial submission through staff review, digital signing, PDF stamping, and final completion. Designed for teams of administrators, reviewers, and approving officers who handle document-heavy request processes.

---

## Features

- **Multi-stage approval workflow** — configurable state machine with role-gated transitions and concurrent-write protection via pessimistic locking
- **PDF zone designer** — drag-and-drop signature and stamp zone placement powered by PDF.js
- **Digital signature capture and stamping** — canvas-based signature pads with blank-detection; signatures are embedded into PDFs via FPDI at approval time
- **Immutable audit trail** — every status transition is logged with actor, role, IP address, user agent, timestamp, and optional notes
- **Role-based authorization** — four distinct roles with per-transition permission enforcement at the service layer
- **White-label theming** — organisation name, logo, and colour scheme configurable through admin settings
- **Checklist enforcement per request type** — Staff 1 cannot forward a request until all required checklist items are marked; flagged items block progression
- **VOT budget breakdown support** — structured vote-code line items for budget-linked grant requests

---

## Workflow

```
                        [Staff 2 override — skips Staff 1]
                ┌──────────────────────────────────────────────────┐
                │                                                  │
                ▼                                                  │
          SUBMITTED ──[Staff 1]──► STAFF1_REVIEWED ──[Staff 2]──► STAFF2_APPROVED ──[Staff 1]──► COMPLETED
              │  ▲                       │                              │
              │  │                       │                              │
              │  └──── resubmit ──── RETURNED ◄──────── return ─────────┘
              │
              └──────────────────────────────────────────────────────────────────────────────► DECLINED
                                                              [Staff 1 from SUBMITTED, or Staff 1/2 from any active state]
```

| Transition | Actor | Requirement |
|---|---|---|
| `SUBMITTED → STAFF1_REVIEWED` | Staff 1 | All required checklist items checked; no flagged items outstanding |
| `STAFF1_REVIEWED → STAFF2_APPROVED` | Staff 2 | Mandatory digital signature |
| `SUBMITTED → STAFF2_APPROVED` | Staff 2 | Override path — logged as `override_approved` |
| `STAFF2_APPROVED → COMPLETED` | Staff 1 | Final processing; triggers PDF stamp |
| `Any active → RETURNED` | Staff 1 or Staff 2 | Written return reason required |
| `RETURNED → SUBMITTED` | Admission | Resubmission after addressing feedback |
| `Any active → DECLINED` | Staff 1 or Staff 2 | Written decline reason required; terminal state |

---

## Roles

| Role | Description | Permissions |
|---|---|---|
| **Admission** | Submits and manages grant requests | Create requests, upload supporting documents, capture applicant signature, resubmit returned requests, track status |
| **Staff 1** | First-line reviewer and processor | Review checklist items, verify documents, forward to Staff 2, return or decline, finalise completed requests |
| **Staff 2** | Approving authority | Approve with digital signature, override Staff 1 stage, return or decline, manage request types and checklists |
| **Admin** | System administrator | Manage user accounts and roles, configure branding and system-wide settings |

---

## Tech Stack

- **Laravel 13, PHP 8.3**
- **SQLite** (development) / **MySQL 8.0** (production)
- **Tailwind CSS, Alpine.js, Vite**
- **FPDI 2.x** — PDF overlay stamping (signatures, approval marks)
- **DomPDF** — server-side PDF generation
- **PDF.js** — in-browser PDF zone designer
- **PhpSpreadsheet** — Excel export for request data

---

## Requirements

- PHP 8.2+
- Composer
- Node.js 18+
- MySQL 8.0 (production)

---

## Quick Start

```bash
git clone https://github.com/your-username/grant-management-system.git
cd grant-management-system
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm install && npm run build
php artisan serve
```

The application will be available at `http://localhost:8000`.

---

## Default Accounts

> **Note:** All seeded accounts use the password `password`. Do not deploy these credentials to any non-local environment.

| Email | Password | Role |
|---|---|---|
| `admin@example.com` | `password` | Admin |
| `staff1@example.com` | `password` | Staff 1 |
| `staff2@example.com` | `password` | Staff 2 |
| `admissions@example.com` | `password` | Admission |

---

## Screenshots

> Coming soon — screenshots of the request dashboard, PDF zone designer, signature capture flow, and audit trail will be added here.

---

## License

This project is open-sourced under the [MIT License](LICENSE).
