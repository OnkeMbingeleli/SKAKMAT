<?php
// Admin Reports: attendance summary, filters, 3D bar chart, and CSV export.
$page = 'admin-reports';
$title = 'Reports - CheckMate';
ob_start();
?>

<div class="page-heading">
    <div>
        <h1>Reports</h1>
        <p>Attendance summary across your team, with daily, weekly, or monthly breakdowns.</p>
    </div>
    <div style="display:flex; gap:12px;">
        <button id="refreshReports" class="cm-btn" type="button">↻ Refresh</button>
        <button id="exportReport" class="cm-btn primary" type="button">⬇ Export CSV</button>
    </div>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-label">Attendance records</div>
        <div class="stat-value" id="reportAttendanceCount">0</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Check-ins</div>
        <div class="stat-value" id="reportCheckins">0</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Check-outs</div>
        <div class="stat-value" id="reportCheckouts">0</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Late arrivals</div>
        <div class="stat-value" id="reportLateArrivals">0</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Absentees</div>
        <div class="stat-value" id="reportAbsentees">0</div>
    </div>
</div>

<div class="panel" style="margin-bottom:24px;">
    <div class="panel-header">
        <h2>Attendance filters</h2>
    </div>
    <div class="leave-form" style="grid-template-columns:repeat(auto-fit, minmax(180px,1fr)); display:grid;">
        <label>
            <span>Start date</span>
            <input type="date" id="reportStartDate">
        </label>
        <label>
            <span>End date</span>
            <input type="date" id="reportEndDate">
        </label>
        <label>
            <span>Department</span>
            <select id="reportDepartment"><option value="">All departments</option></select>
        </label>
        <label>
            <span>Employee</span>
            <select id="reportEmployee"><option value="">All employees</option></select>
        </label>
    </div>
    <div class="filters" style="padding:0 24px 22px;">
        <button type="button" class="filter-button active report-type-button" data-type="daily">Daily</button>
        <button type="button" class="filter-button report-type-button" data-type="weekly">Weekly</button>
        <button type="button" class="filter-button report-type-button" data-type="monthly">Monthly</button>
    </div>
</div>

<div class="panel" style="margin-bottom:24px;">
    <div class="panel-header">
        <h2>Attendance by period</h2>
    </div>
    <div class="chart3d-wrap">
        <div class="chart3d-scene" id="reportsChartScene"></div>
        <div class="chart3d-labels" id="reportsChartLabels"></div>
        <div class="table-state hidden" id="reportsChartEmpty">No data for this range yet.</div>
    </div>
</div>

<div class="panel requests-panel">
    <div class="panel-header">
        <h2>Breakdown table</h2>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Period</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Present</th>
                    <th>Check-ins</th>
                    <th>Check-outs</th>
                    <th>Late arrivals</th>
                </tr>
            </thead>
            <tbody id="reportsBody"></tbody>
        </table>
        <div class="table-state hidden" id="reportsEmpty">No attendance records for this range.</div>
    </div>
</div>

<script src="/assets/js/config.js"></script>
<script src="/assets/js/api.js"></script>
<script src="/assets/js/admin-reports.js"></script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
?>
