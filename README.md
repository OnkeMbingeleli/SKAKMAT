# Skakmat

**Skakmat** (Danish/Norwegian) — pronounced *Skahk-maht* — is the literal word for checkmate.

Skakmat is an employee attendance and leave management system, built as a PHP backend (custom MVC) with a server-rendered PHP + vanilla JS frontend.

## Features

- **Attendance & clock-in/out** — QR-code based clock-in/out sessions, plus attendance history logging
- **Leave management** — staff leave requests with admin approval, and leave balance/ledger tracking
- **Employee management** — employee records, contracts, and bulk CSV imports
- **Admin insights & reports** — analytics and reports for admins
- **Emergency logging** — emergency contact logging for staff
- **Notifications** — daily/weekly email summaries (PHPMailer)
- **Multi-language UI** — DeepL-backed translation with local caching

## Project structure

```
skakmat/
├── frontend/   # PHP views + vanilla JS (server-rendered UI)
├── backend/    # PHP MVC API (controllers, models, routes)
└── docs/
```

## Getting started

- Backend setup: `backend/setup.sh` (Mac/Linux) or `backend/setup.bat` (Windows); email config in `backend/MAILER.md`
- Frontend setup: copy `frontend/.env.example` to `frontend/.env` and fill in your values
- Full walkthrough: [`QUICK_START.md`](QUICK_START.md) · Full doc index: [`DOCUMENTATION_INDEX.md`](DOCUMENTATION_INDEX.md)

## Branches

Team development happens on feature branches, one per workstream. See [`BRANCHES.md`](BRANCHES.md) for the full list and what each one owns.
