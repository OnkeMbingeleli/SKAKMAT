<?php
$page = 'admin-reports';
$title = 'Reports - CheckMate';
ob_start();
?>

<style>
/* Reports page styles kept local to this page so pulls do not depend on app.css page-specific rules. */
body[data-page="admin-reports"] .reports-filter-panel {
    margin-bottom: 20px;
}body[data-page="admin-reports"] .page-heading h1 {
    margin-bottom: 10px;
    font-size: 32px;
    font-weight: 800;
}body[data-page="admin-reports"] .page-heading p {
    font-size: 18px;
}body[data-page="admin-reports"] .reports-filter-panel .filters {
    padding: 18px 24px 24px;
    margin: 0;
}body[data-page="admin-reports"] .report-dashboard-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
}body[data-page="admin-reports"] .report-chart-card {
    min-height: 378px;
    border-radius: 18px;
    box-shadow: var(--shadow);
}body[data-page="admin-reports"] .report-chart-card .panel-header {
    justify-content: space-between;
}body[data-page="admin-reports"] .report-chart-card .panel-header h2 {
    margin-bottom: 5px;
}body[data-page="admin-reports"] .report-chart-card .panel-header p {
    margin: 0;
    color: var(--muted);
    font-size: 14px;
}body[data-page="admin-reports"] .chart-frame {
    height: 302px;
    padding: 24px 28px 28px;
}body[data-page="admin-reports"] .chart-frame-donut {
    max-width: 420px;
    margin: 0 auto;
}body[data-page="admin-reports"] .report-summary-panel {
    margin-top: 20px;
}body[data-page="admin-reports"] .report-metric-grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 14px;
    padding: 18px 24px 24px;
}body[data-page="admin-reports"] .report-metric {
    min-width: 0;
    padding: 16px;
    border: 1px solid #edf2f8;
    border-radius: 12px;
    background: var(--bg);
}body[data-page="admin-reports"] .report-metric span {
    display: block;
    margin-bottom: 8px;
    color: var(--muted);
    font-size: 13px;
    font-weight: 800;
}body[data-page="admin-reports"] .report-metric strong {
    display: block;
    color: var(--text);
    font-size: 28px;
    line-height: 1;
}body[data-page="admin-reports"] .report-dashboard-grid {
    gap: 16px;
}body[data-page="admin-reports"] .report-chart-card {
    min-height: 352px;
    border-radius: 16px;
    box-shadow: 0 8px 24px rgba(15,23,42,.045);
}body[data-page="admin-reports"] .chart-frame {
    height: 276px;
    padding: 14px 18px 18px;
}body[data-page="admin-reports"] .chart-frame-donut {
    max-width: none;
}.checkmate-svg-chart {
    width: 100%;
    height: 100%;
    display: block;
}body[data-page="admin-reports"] .report-summary-panel {
    border-radius: 16px;
}body[data-page="admin-reports"] .reports-filter-panel {
    border-radius: 16px;
    box-shadow: 0 8px 24px rgba(15,23,42,.045);
}body[data-page="admin-reports"] .reports-filter-panel .filters {
    gap: 14px;
}body[data-page="admin-reports"] .reports-filter-panel label span {
    color: var(--muted);
    font-size: 12px;
    font-weight: 750;
}
</style>

<div class="page-heading">
    <div>
        <h1>Reports</h1>
        <p>Attendance trends across the organisation.</p>
    </div>
</div>

<div class="panel reports-filter-panel">
    <div class="panel-header">
        <h2>Filters</h2>
    </div>
    <div class="filters" style="flex-wrap: wrap; gap: 16px;">
        <label>
            <span>Start date</span>
            <input id="reportStartDate" type="date" />
        </label>
        <label>
            <span>End date</span>
            <input id="reportEndDate" type="date" />
        </label>
        <label>
            <span>Department</span>
            <select id="reportDepartment">
                <option value="">All departments</option>
            </select>
        </label>
        <label>
            <span>Employee</span>
            <select id="reportEmployee">
                <option value="">All employees</option>
            </select>
        </label>
    </div>
</div>

<div class="report-dashboard-grid">
    <section class="panel report-chart-card">
        <div class="panel-header">
            <div>
                <h2>Weekly Attendance</h2>
                <p>Onsite headcount Mon-Sun</p>
            </div>
        </div>
        <div class="chart-frame">
            <canvas id="weeklyAttendanceChart"></canvas>
        </div>
    </section>

    <section class="panel report-chart-card">
        <div class="panel-header">
            <div>
                <h2>Monthly Attendance Rate</h2>
                <p>Percent present by week, last 4 weeks</p>
            </div>
        </div>
        <div class="chart-frame">
            <canvas id="monthlyAttendanceRateChart"></canvas>
        </div>
    </section>

    <section class="panel report-chart-card">
        <div class="panel-header">
            <div>
                <h2>Late Arrivals</h2>
                <p>Late check-ins this week</p>
            </div>
        </div>
        <div class="chart-frame">
            <canvas id="lateArrivalsChart"></canvas>
        </div>
    </section>

    <section class="panel report-chart-card">
        <div class="panel-header">
            <div>
                <h2>Current Presence Split</h2>
                <p>Derived from today's attendance report</p>
            </div>
        </div>
        <div class="chart-frame chart-frame-donut">
            <canvas id="presenceSplitChart"></canvas>
        </div>
    </section>
</div>

<div class="panel report-summary-panel">
    <div class="panel-header">
        <h2>Today at a glance</h2>
    </div>
    <div class="report-metric-grid">
        <div class="report-metric"><span>Attendance</span><strong id="reportAttendanceCount">0</strong></div>
        <div class="report-metric"><span>Check-ins</span><strong id="reportCheckins">0</strong></div>
        <div class="report-metric"><span>Check-outs</span><strong id="reportCheckouts">0</strong></div>
        <div class="report-metric"><span>Late arrivals</span><strong id="reportLateArrivals">0</strong></div>
        <div class="report-metric"><span>Absent</span><strong id="reportAbsentees">0</strong></div>
    </div>
</div>

<div class="toast-region"></div>
<script src="/assets/js/config.js"></script>
<script src="/assets/js/api.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script src="/assets/js/admin-reports.js"></script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
?>
