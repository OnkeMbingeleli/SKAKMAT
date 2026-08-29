<?php
$page = 'admin-employees';
$title = 'Employees - Skakmat';
ob_start();
?>

<style>
/* Employee page styles kept local to this page so pulls do not depend on app.css page-specific rules. */
body[data-page="admin-employees"] .page-content {
    padding-top: 40px;
}body[data-page="admin-employees"] .page-heading {
    margin-bottom: 26px;
}body[data-page="admin-employees"] .page-heading h1 {
    margin-bottom: 10px;
    font-size: 32px;
    font-weight: 800;
}body[data-page="admin-employees"] .page-heading p {
    font-size: 18px;
}body[data-page="admin-employees"] .employee-add-button {
    background: var(--teal);
    color: #ffffff;
    border-color: var(--teal);
    border-radius: 12px;
    min-height: 52px;
    box-shadow: 0 16px 28px rgba(15, 118, 110, 0.22);
}body[data-page="admin-employees"] .employee-add-button:hover,
.primary-button:hover,
.employee-create-submit:hover {
    background: var(--teal-dark);
    border-color: var(--teal-dark);
}body[data-page="admin-employees"] .panel {
    border-radius: 12px;
    box-shadow: none;
}body[data-page="admin-employees"] .panel-header {
    min-height: 56px;
    padding: 0 18px;
}body[data-page="admin-employees"] .panel-header h2 {
    font-size: 16px;
}body[data-page="admin-employees"] .employee-list-panel {
    min-height: 0;
}body[data-page="admin-employees"] .employee-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    margin-bottom: 22px;
}body[data-page="admin-employees"] .employee-toolbar .filters {
    margin: 0;
}body[data-page="admin-employees"] .employee-toolbar .search-box {
    width: min(352px, 100%);
    background: #FFFFFF;
}body[data-page="admin-employees"] .employee-card-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(240px, 1fr));
    gap: 20px;
    padding: 0;
}body[data-page="admin-employees"] .employee-card {
    border: 1px solid var(--line);
    border-radius: 18px;
    padding: 26px 22px 22px;
    background: #ffffff;
    box-shadow: 0 14px 26px rgba(12, 32, 64, 0.06);
}body[data-page="admin-employees"] .employee-card-main {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 18px;
}body[data-page="admin-employees"] .employee-avatar {
    width: 42px;
    height: 42px;
    flex: 0 0 42px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background: var(--teal);
    color: #ffffff;
    font-size: 13px;
    font-weight: 800;
}body[data-page="admin-employees"] .employee-card:nth-child(4n + 2) .employee-avatar {
    background: var(--accent);
}body[data-page="admin-employees"] .employee-card:nth-child(4n + 3) .employee-avatar {
    background: #849B43;
}body[data-page="admin-employees"] .employee-card:nth-child(4n + 4) .employee-avatar {
    background: #526324;
}body[data-page="admin-employees"] .employee-copy {
    min-width: 0;
    flex: 1;
}body[data-page="admin-employees"] .employee-copy h3 {
    margin: 0 0 4px;
    overflow: hidden;
    color: var(--text);
    font-size: 19px;
    font-weight: 800;
    line-height: 1.2;
    text-overflow: ellipsis;
    white-space: nowrap;
}body[data-page="admin-employees"] .employee-copy p {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
    color: var(--muted);
    font-size: 15px;
    line-height: 1.4;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}body[data-page="admin-employees"] .employee-card-icon {
    display: inline-block;
    width: 14px;
    height: 14px;
    flex: 0 0 14px;
    color: #6c7f9b;
    position: relative;
}body[data-page="admin-employees"] .employee-meta-list {
    display: grid;
    gap: 16px;
    margin-bottom: 22px;
}body[data-page="admin-employees"] .employee-meta-list p {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
    margin: 0;
    color: var(--muted);
    font-size: 16px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}body[data-page="admin-employees"] .employee-mail-icon {
    border: 1.7px solid currentColor;
    border-radius: 3px;
}body[data-page="admin-employees"] .employee-mail-icon::before,
body[data-page="admin-employees"] .employee-mail-icon::after {
    content: "";
    position: absolute;
    top: 3px;
    width: 8px;
    height: 1.7px;
    background: currentColor;
}body[data-page="admin-employees"] .employee-mail-icon::before {
    left: 0;
    transform: rotate(35deg);
}body[data-page="admin-employees"] .employee-mail-icon::after {
    right: 0;
    transform: rotate(-35deg);
}body[data-page="admin-employees"] .employee-department-icon {
    border: 1.7px solid currentColor;
    border-radius: 3px;
}body[data-page="admin-employees"] .employee-department-icon::before {
    content: "";
    position: absolute;
    left: 3px;
    right: 3px;
    top: -4px;
    height: 4px;
    border: 1.7px solid currentColor;
    border-bottom: 0;
    border-radius: 3px 3px 0 0;
}body[data-page="admin-employees"] .employee-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    min-height: 28px;
    padding: 0 11px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 900;
    white-space: nowrap;
}body[data-page="admin-employees"] .employee-status-pill span {
    width: 7px;
    height: 7px;
    border-radius: 50%;
}body[data-page="admin-employees"] .employee-status-pill.is-onsite {
    background: #d7fbe6;
    color: #128246;
}body[data-page="admin-employees"] .employee-status-pill.is-onsite span {
    background: #20c863;
}body[data-page="admin-employees"] .employee-status-pill.is-offsite {
    background: #F1F5F9;
    color: #64748B;
}body[data-page="admin-employees"] .employee-status-pill.is-offsite span {
    background: #94A3B8;
}body[data-page="admin-employees"] .employee-field-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 16px;
}body[data-page="admin-employees"] .employee-field {
    min-width: 0;
    padding: 12px;
    border: 1px solid #edf2f8;
    border-radius: 10px;
    background: var(--bg);
}body[data-page="admin-employees"] .employee-field > span {
    display: block;
    margin-bottom: 7px;
    color: #6b7c94;
    font-size: 11px;
    font-weight: 900;
    letter-spacing: 0.7px;
    text-transform: uppercase;
}body[data-page="admin-employees"] .employee-field strong {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
    overflow: hidden;
    color: var(--text);
    font-size: 14px;
    font-weight: 800;
    text-overflow: ellipsis;
    white-space: nowrap;
}body[data-page="admin-employees"] .employee-stats {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 16px;
}body[data-page="admin-employees"] .employee-stats > span {
    color: #526a89;
    font-size: 13px;
}body[data-page="admin-employees"] .employee-stats strong {
    color: var(--text);
    font-weight: 900;
}body[data-page="admin-employees"] .employee-actions {
    display: flex;
    gap: 10px;
}body[data-page="admin-employees"] .employee-actions button {
    min-height: 44px;
    border: 1px solid var(--line);
    border-radius: 10px;
    font-size: 16px;
    font-weight: 800;
}body[data-page="admin-employees"] .detail-button {
    flex: 1;
    background: #ffffff;
    color: var(--text);
}body[data-page="admin-employees"] .employee-delete-button {
    width: 50px;
    flex: 0 0 50px;
    background: #ffffff;
    color: #EF4444;
}body[data-page="admin-employees"] .employee-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-top: 20px;
    color: var(--muted);
}.edit-icon,
.trash-icon,
.delete-icon {
    display: inline-block;
    position: relative;
}.modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 30;
    display: grid;
    place-items: center;
    padding: 24px;
    background: rgba(15, 23, 42, 0.52);
}.modal-panel {
    width: min(680px, 100%);
    max-height: calc(100vh - 48px);
    overflow: auto;
    border-radius: 20px;
    background: #ffffff;
    box-shadow: 0 26px 70px rgba(6, 23, 53, 0.22);
}.modal-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 18px;
    padding: 22px 24px;
    border-bottom: 1px solid var(--line);
}.modal-header h2 {
    margin: 0 0 6px;
    font-size: 22px;
}.employee-create-panel {
    width: min(526px, 100%);
}.modal-note {
    margin: 0;
    color: var(--muted);
    font-size: 15px;
    line-height: 1.45;
}.employee-delete-panel {
    width: min(430px, 100%);
    padding: 32px;
    text-align: center;
}.employee-delete-panel h2 {
    margin: 16px 0 10px;
    color: var(--text);
    font-size: 22px;
}.employee-delete-panel p {
    margin: 0;
    color: var(--muted);
    line-height: 1.55;
}.employee-delete-panel .modal-actions {
    display: flex;
    justify-content: center;
    margin-top: 26px;
    padding: 0;
}.delete-icon {
    width: 54px;
    height: 54px;
    border-radius: 16px;
    background: #FEE2E2;
    color: #EF4444;
}.delete-icon::before {
    content: "";
    position: absolute;
    left: 18px;
    right: 18px;
    top: 22px;
    bottom: 14px;
    border: 2px solid currentColor;
    border-top: 0;
    border-radius: 0 0 4px 4px;
}.delete-icon::after {
    content: "";
    position: absolute;
    left: 16px;
    right: 16px;
    top: 17px;
    border-top: 2px solid currentColor;
}.employee-remove-submit {
    min-height: 50px;
    padding: 0 22px;
    border: 0;
    border-radius: 12px;
    background: #EF4444;
    color: #FFFFFF;
    font-weight: 900;
}.modal-header p {
    margin: 0;
    color: var(--muted);
}.modal-close {
    width: 36px;
    height: 36px;
    border: 1px solid var(--line);
    border-radius: 10px;
    background: #ffffff;
    color: var(--text);
    font-size: 24px;
    line-height: 1;
}.modal-form {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
    padding: 22px 24px 24px;
}.modal-form label {
    display: grid;
    gap: 8px;
}.modal-form label > span {
    color: #596d8b;
    font-weight: 800;
}.modal-form input,
.modal-form select,
body[data-page="admin-reports"] .reports-filter-panel input,
body[data-page="admin-reports"] .reports-filter-panel select,
body[data-page="admin-employees"] .employee-toolbar select {
    width: 100%;
    min-height: 48px;
    border: 1px solid var(--line);
    border-radius: 10px;
    background: var(--soft-input);
    color: var(--text);
    padding: 0 14px;
    outline: 0;
}.employee-modal-stats {
    grid-column: 1 / -1;
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 10px;
    padding: 12px;
    border: 1px solid var(--line);
    border-radius: 12px;
    background: var(--bg);
}.employee-modal-stats span {
    min-width: 0;
    color: var(--muted);
    font-size: 12px;
    font-weight: 800;
}.employee-modal-stats strong {
    display: block;
    margin-top: 4px;
    overflow: hidden;
    color: var(--text);
    font-size: 14px;
    text-overflow: ellipsis;
    white-space: nowrap;
}.modal-actions {
    grid-column: 1 / -1;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding-top: 6px;
}.employee-create-submit {
    min-height: 50px;
    padding: 0 22px;
    border: 0;
    border-radius: 12px;
    background: var(--teal);
    color: #ffffff;
    font-weight: 900;
    box-shadow: 0 14px 24px rgba(19, 133, 120, 0.2);
}.employee-create-submit:disabled {
    opacity: 0.65;
    cursor: progress;
}.page-heading h1,
body[data-page="admin-employees"] .page-heading h1,
body[data-page="admin-reports"] .page-heading h1 {
    font-size: 31px;
    font-weight: 800;
    letter-spacing: -.025em;
}.page-heading p,
body[data-page="admin-employees"] .page-heading p,
body[data-page="admin-reports"] .page-heading p {
    color: var(--muted);
    font-size: 15px;
}body[data-page="admin-employees"] .employee-toolbar {
    margin: 0 auto 24px;
    max-width: 1600px;
}body[data-page="admin-employees"] .employee-toolbar .search-box {
    width: min(360px, 100%);
    min-height: 48px;
}body[data-page="admin-employees"] .employee-card-grid {
    grid-template-columns: repeat(4, minmax(230px, 1fr));
    gap: 18px;
    max-width: 1600px;
    margin: 0 auto;
}body[data-page="admin-employees"] .employee-card {
    min-width: 0;
    padding: 20px 18px 16px;
    border-radius: 16px;
    border-color: #E7EDF4;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.045);
}body[data-page="admin-employees"] .employee-card-main {
    gap: 10px;
    margin-bottom: 15px;
}body[data-page="admin-employees"] .employee-avatar {
    width: 40px;
    height: 40px;
    flex-basis: 40px;
    background: var(--teal);
}body[data-page="admin-employees"] .employee-card:nth-child(4n + 2) .employee-avatar,
body[data-page="admin-employees"] .employee-card:nth-child(4n + 3) .employee-avatar,
body[data-page="admin-employees"] .employee-card:nth-child(4n + 4) .employee-avatar {
    background: #E6FFFA;
    color: var(--teal);
}body[data-page="admin-employees"] .employee-copy h3 {
    font-size: 16px;
    font-weight: 800;
}body[data-page="admin-employees"] .employee-copy p,
body[data-page="admin-employees"] .employee-meta-list p {
    font-size: 13px;
}body[data-page="admin-employees"] .employee-meta-list {
    gap: 11px;
    margin-bottom: 16px;
}body[data-page="admin-employees"] .employee-status-pill {
    min-height: 25px;
    padding: 0 9px;
    font-size: 11px;
}body[data-page="admin-employees"] .employee-actions {
    gap: 8px;
}body[data-page="admin-employees"] .employee-actions button {
    min-height: 40px;
    border-radius: 10px;
    font-size: 14px;
}body[data-page="admin-employees"] .detail-button {
    color: var(--text);
}body[data-page="admin-employees"] .employee-delete-button {
    width: 42px;
    flex-basis: 42px;
    color: #EF4444;
}body[data-page="admin-employees"] .employee-delete-button:hover {
    background: #FFF1F2;
}.employee-create-submit,
body[data-page="admin-employees"] .employee-add-button {
    background: var(--teal);
    border-color: var(--teal);
}.employee-create-submit:hover,
body[data-page="admin-employees"] .employee-add-button:hover {
    background: var(--teal-dark);
    border-color: var(--teal-dark);
}.modal-backdrop {
    position: fixed !important;
    inset: 0 !important;
    z-index: 2000 !important;
    align-items: center;
    justify-items: center;
    padding: 24px;
    background: rgba(15, 23, 42, .50);
    backdrop-filter: blur(4px);
}.modal-panel {
    position: relative;
    z-index: 2001;
    max-height: calc(100vh - 48px);
    border: 1px solid rgba(226, 232, 240, .85);
    border-radius: 20px;
    box-shadow: 0 28px 80px rgba(15, 23, 42, .22);
}.modal-form input:focus,
.modal-form select:focus,
body[data-page="admin-reports"] .reports-filter-panel input:focus,
body[data-page="admin-reports"] .reports-filter-panel select:focus,
body[data-page="admin-employees"] .employee-toolbar select:focus,
body[data-page="admin-employees"] .employee-toolbar input:focus {
    border-color: var(--teal);
    box-shadow: 0 0 0 3px rgba(15, 118, 110, .10);
}body.modal-open { overflow: hidden; }
</style>

<div class="page-heading">
    <div>
        <h1>Employees</h1>
        <p>Create staff profiles — login credentials are generated automatically.</p>
    </div>
    <div class="topbar-actions">
        <button type="button" class="filter-button" id="openEmployeeImport">Import Employees</button>
        <button type="button" class="filter-button employee-add-button" id="openEmployeeCreate">+ Add Employee</button>
    </div>
</div>

<div id="employeeImportModal" class="modal-backdrop hidden" aria-hidden="true">
    <div class="modal-panel employee-create-panel" role="dialog" aria-modal="true" aria-labelledby="employeeImportTitle">
        <div class="modal-header">
            <div>
                <h2 id="employeeImportTitle">Import Employees</h2>
                <p class="modal-note">Upload CSV, TSV, JSON, XLS, XLSX, or ODS. Required columns: first name, last name, and email.</p>
            </div>
            <button type="button" class="modal-close" id="closeEmployeeImport" aria-label="Close employee import form">&times;</button>
        </div>
        <form id="employeeImportForm" class="modal-form">
            <label>
                <span>Employee file</span>
                <input id="employeeImportFile" type="file" accept=".csv,.tsv,.json,.xls,.xlsx,.ods,text/csv,application/json,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required />
            </label>
            <p class="modal-note">Existing email addresses are skipped. Downloaded spreadsheets should have headers in the first row.</p>
            <div id="employeeImportStatus" class="modal-note" role="status"></div>
            <div class="modal-actions">
                <button type="button" class="filter-button" id="cancelEmployeeImport">Cancel</button>
                <button type="submit" class="employee-create-submit" id="submitEmployeeImport">Import file</button>
            </div>
        </form>
    </div>
</div>

<div class="employee-toolbar">
    <div class="search-box">
        <span></span>
        <input id="employeeSearch" type="search" placeholder="Search employees..." />
    </div>
    <div class="filters">
        <select id="employeeDepartment">
            <option value="">All departments</option>
        </select>
        <select id="employeePosition">
            <option value="">All positions</option>
        </select>
    </div>
</div>

<div class="employee-list-panel">
    <div id="employeesBody" class="employee-card-grid"></div>
    <div id="employeesEmpty" class="table-state hidden">
        No employee records found. Adjust filters to try again.
    </div>
    <div class="employee-pagination">
        <div id="employeesPaginationLabel">Page 1 of 1</div>
        <div>
            <button id="employeesPrevPage" class="filter-button">Previous</button>
            <button id="employeesNextPage" class="filter-button">Next</button>
        </div>
    </div>
</div>

<div id="employeeCreateModal" class="modal-backdrop hidden" aria-hidden="true">
    <div class="modal-panel employee-create-panel" role="dialog" aria-modal="true" aria-labelledby="employeeCreateTitle">
        <div class="modal-header">
            <div>
                <h2 id="employeeCreateTitle">Add Employee</h2>
            </div>
            <button type="button" class="modal-close" id="closeEmployeeCreate" aria-label="Close add employee form">&times;</button>
        </div>
        <form id="employeeCreateForm" class="modal-form">
            <label>
                <span>First name</span>
                <input type="text" name="first_name" autocomplete="given-name" required />
            </label>
            <label>
                <span>Surname</span>
                <input type="text" name="last_name" autocomplete="family-name" required />
            </label>
            <label>
                <span>Email</span>
                <input type="email" name="email" autocomplete="email" required />
            </label>
            <label>
                <span>Department</span>
                <select name="department" required></select>
            </label>
            <label>
                <span>Position</span>
                <input type="text" name="position" required />
            </label>
            <p class="modal-note">A secure temporary password will be generated automatically and sent to the employee by email.</p>
            <div class="modal-actions">
                <button type="button" class="filter-button" id="cancelEmployeeCreate">Cancel</button>
                <button type="submit" class="employee-create-submit">Create Employee</button>
            </div>
        </form>
    </div>
</div>

<div id="employeeEditModal" class="modal-backdrop hidden" aria-hidden="true">
    <div class="modal-panel employee-edit-panel" role="dialog" aria-modal="true" aria-labelledby="employeeEditTitle">
        <div class="modal-header">
            <div>
                <h2 id="employeeEditTitle">Edit Employee</h2>
                <p id="employeeEditSubtitle">Update employee profile details.</p>
            </div>
            <button type="button" class="modal-close" id="closeEmployeeEdit" aria-label="Close edit employee form">&times;</button>
        </div>
        <form id="employeeEditForm" class="modal-form" data-employee-id="0">
            <label>
                <span>First name</span>
                <input type="text" name="first_name" autocomplete="given-name" required />
            </label>
            <label>
                <span>Surname</span>
                <input type="text" name="last_name" autocomplete="family-name" required />
            </label>
            <label>
                <span>Email</span>
                <input type="email" name="email" autocomplete="email" required />
            </label>
            <label>
                <span>Department</span>
                <select name="department" required></select>
            </label>
            <label>
                <span>Position</span>
                <input type="text" name="position" required />
            </label>
            <div class="employee-modal-stats" aria-label="Attendance summary">
                <span>Records <strong id="detailAttendanceCount">0</strong></span>
                <span>Check-ins <strong id="detailCheckins">0</strong></span>
                <span>Check-outs <strong id="detailCheckouts">0</strong></span>
                <span>Late <strong id="detailLateArrivals">0</strong></span>
                <span>Last seen <strong id="detailLastSeen">-</strong></span>
            </div>
            <div class="modal-actions">
                <button type="button" class="filter-button" id="cancelEmployeeEdit">Cancel</button>
                <button type="submit" class="employee-create-submit">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<div id="employeeDeleteModal" class="modal-backdrop hidden" aria-hidden="true">
    <div class="modal-panel employee-delete-panel" role="dialog" aria-modal="true" aria-labelledby="employeeDeleteTitle">
        <div class="delete-icon" aria-hidden="true"></div>
        <h2 id="employeeDeleteTitle">Remove Employee</h2>
        <p>Are you sure you want to remove <strong id="employeeDeleteName">this employee</strong>? This action cannot be undone.</p>
        <div class="modal-actions">
            <button type="button" class="filter-button" id="cancelEmployeeDelete">Cancel</button>
            <button type="button" class="employee-remove-submit" id="confirmEmployeeDelete">Remove Employee</button>
        </div>
    </div>
</div>

<div class="toast-region"></div>
<script src="/assets/js/config.js"></script>
<script src="/assets/js/api.js"></script>
<script src="https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js"></script>
<script src="/assets/js/admin-employees.js"></script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
?>
