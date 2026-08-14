<?php
// Admin Employees: searchable/filterable staff directory with an attendance detail panel.
$page = 'admin-employees';
$title = 'Employees - CheckMate';
ob_start();
?>

<div class="page-heading">
    <div>
        <h1>Employees</h1>
        <p>Search and filter your team, and drill into each employee's attendance record.</p>
    </div>
    <button id="refreshEmployees" class="cm-btn primary" type="button">↻ Refresh</button>
</div>

<div class="panel" style="margin-bottom:24px;">
    <div class="panel-header">
        <h2>Filters</h2>
    </div>
    <div class="leave-form" style="grid-template-columns:repeat(auto-fit, minmax(180px,1fr)); display:grid;">
        <label>
            <span>Search</span>
            <input type="text" id="employeeSearch" placeholder="Name or email...">
        </label>
        <label>
            <span>Department</span>
            <select id="employeeDepartment"><option value="">All departments</option></select>
        </label>
        <label>
            <span>Position</span>
            <select id="employeePosition"><option value="">All positions</option></select>
        </label>
    </div>
</div>

<div class="panel requests-panel">
    <div class="panel-header">
        <h2>Team directory</h2>
        <div style="display:flex; align-items:center; gap:12px;">
            <span id="employeesPaginationLabel" style="font-size:13px; color:var(--muted);">Page 1 of 1</span>
            <button id="employeesPrevPage" class="cm-btn" type="button" disabled>‹ Prev</button>
            <button id="employeesNextPage" class="cm-btn" type="button">Next ›</button>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Position</th>
                    <th>Status</th>
                    <th>Attendance</th>
                    <th>Check-ins</th>
                    <th>Check-outs</th>
                    <th>Late</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="employeesBody"></tbody>
        </table>
        <div class="table-state hidden" id="employeesEmpty">No employees match these filters.</div>
    </div>
</div>

<div class="panel hidden" id="employeeDetailPanel" style="margin-top:24px;">
    <div class="panel-header">
        <h2 id="detailEmployeeTitle">Employee details</h2>
        <button id="closeEmployeeDetail" class="cm-btn" type="button">✕ Close</button>
    </div>

    <div class="stat-grid" style="padding:20px 24px 0;">
        <div class="stat-card">
            <div class="stat-label">Attendance</div>
            <div class="stat-value" id="detailAttendanceCount">0</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Check-ins</div>
            <div class="stat-value" id="detailCheckins">0</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Check-outs</div>
            <div class="stat-value" id="detailCheckouts">0</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Late arrivals</div>
            <div class="stat-value" id="detailLateArrivals">0</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Last seen</div>
            <div class="stat-value" style="font-size:16px;" id="detailLastSeen">-</div>
        </div>
    </div>

    <form id="employeeDetailForm" class="leave-form" style="grid-template-columns:repeat(auto-fit, minmax(200px,1fr)); display:grid;">
        <label>
            <span>First name</span>
            <input type="text" name="first_name" required>
        </label>
        <label>
            <span>Last name</span>
            <input type="text" name="last_name" required>
        </label>
        <label>
            <span>Email</span>
            <input type="email" name="email" required>
        </label>
        <label>
            <span>Department</span>
            <input type="text" name="department" required>
        </label>
        <label>
            <span>Position</span>
            <input type="text" name="position" required>
        </label>
        <div style="align-self:end;">
            <button type="submit" class="primary-button" style="width:100%;">Save changes</button>
        </div>
    </form>
</div>

<script src="/assets/js/config.js"></script>
<script src="/assets/js/api.js"></script>
<script src="/assets/js/admin-employees.js"></script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
?>
