# Branches

Skakmat's work is split into 15 branches, one per feature area, so everyone
can push their own slice without stepping on anyone else's files.

**Naming convention:** `M9080/<frontend|backend>/<work-title>`

All 15 branches currently point at the same base commit — the one that
renamed the project to Skakmat. Check out yours and start committing.

```
git fetch origin
git checkout M9080/backend/attendance-clock-in-out
```

## Backend

| Branch | Owns | Description |
|---|---|---|
| `M9080/backend/attendance-clock-in-out` | `controllers/Attendance*.php`, `models/Attendance*.php`, `routes/attendance*.php` | Core attendance engine — clock-in/out records and the attendance history log. |
| `M9080/backend/qr-code-sessions` | `controllers/QRCode*.php`, `QRSession*.php`, `models/QRCode*.php`, `QRSession*.php`, `routes/qr_codes.php`, `qr_sessions.php` | QR-based attendance — QR code generation and session validation. |
| `M9080/backend/leave-requests` | `controllers/LeaveRequest*.php`, `Leave*.php`, `models/Leave*.php`, `routes/leave_requests.php`, `leaves.php`, `_deprecated/Leave*` | Staff leave request workflow — submit, approve, reject. |
| `M9080/backend/leave-balance-ledger` | `controllers/LeaveBalanceController.php`, `models/LeaveBalanceModel.php`, `routes/leave_balance.php`, `migrations/002_leave_balances_and_ledger.sql` | Leave balance tracking and ledger calculations. |
| `M9080/backend/employee-management` | `controllers/UserController.php`, `EmployeeContractController.php`, `EmployeeImportController.php`, `routes/users.php`, `employee_imports.php`, `migrations/001_employee_insights_and_imports.sql` | Employee records, contracts, and bulk CSV imports. |
| `M9080/backend/insights-and-reports` | `controllers/EmployeeInsightController.php`, `ReportController.php`, `services/EmployeeInsightService.php`, `routes/reports.php`, `scripts/migration_employee_insights.sql` | Analytics and admin-facing reports. |
| `M9080/backend/emergency-alerts` | `controllers/EmergencyLogController.php`, `models/EmergencyLogModel.php`, `routes/emergency_logs.php` | Emergency contact logging. |
| `M9080/backend/core-auth-and-notifications` | `bootstrap.php`, `config/database.php`, `middleware/AuthMiddleware.php`, `services/EmailService.php`, `Mailer.php`, `SummaryBuilder.php`, `scripts/send_*_summaries.php`, `composer.json`, `setup.sh`/`.bat`, `MAILER.md` | App bootstrap/config, auth middleware, mailer/email services, and scheduled summary jobs. |

## Frontend

| Branch | Owns | Description |
|---|---|---|
| `M9080/frontend/auth-views` | `views/login.php`, `signup.php`, `assets/js/login.js` | Login and signup screens. |
| `M9080/frontend/dashboard-widgets` | `views/dashboard.php`, `partials/quick-stats.php`, `quick-status.php`, `recent-activity.php`, `admin-cards.php`, `staff-cards.php` | Dashboard page and its summary/card widgets. |
| `M9080/frontend/attendance-ui` | `views/staff/clock-in-out.php`, `attendance-history.php`, `assets/js/attendanceLog.js` | Staff clock-in/out screen and attendance history view. |
| `M9080/frontend/leave-ui` | `views/staff/leave.php`, `admin/leave-requests.php`, `assets/js/leave.js` | Leave request screens for staff and admin. |
| `M9080/frontend/employee-admin-ui` | `views/admin/employees.php`, `partials/add-employee.php`, `assets/js/admin-employees.js` | Employee management screen for admins. |
| `M9080/frontend/qr-and-reports-ui` | `views/admin/qr-code.php`, `admin/reports.php`, `assets/js/admin-reports.js`, `public/api/qrcode.php` | QR code admin screen and the reports screen. |
| `M9080/frontend/shell-layout-and-settings` | `views/layouts/app.php`, `partials/header.php`, `sidebar.php`, `settings.php`, `admin/emergency.php`, `helpers/*`, `lang/*`, `assets/js/api.js`, `config.js`, `app-preferences.js`, `i18n.js`, `store.js`, `utils/sidebar.js`, `assets/css/app.css`, `public/*` | Shared shell — layout, header, sidebar, settings, i18n, and core JS/config. Also carries the emergency admin view. |

## Notes

- The existing branches from the original group project (`main`, `OnkeMbing/*`, `lina/dev/settings`, `SizaConfiguration`, etc.) are untouched — these 15 are additional, not replacements.
- "Owns" is a guide for where each workstream's changes should land, not a hard file lock — every branch has the full codebase checked out, since the app isn't split into independently-runnable packages.
- Update `git remote set-url origin <your-new-repo-url>` once you've created the Skakmat repo under your own account/org; the remote currently still points at the original `checkmate` repo.
