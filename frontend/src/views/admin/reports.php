<div class="page-heading">
    <div>
        <h1>Reports</h1>
        <p>Generate attendance summaries, daily/weekly/monthly reports, and export results.</p>
    </div>
    <div class="topbar-actions">
        <button id="refreshReports" class="filter-button">Refresh</button>
        <button id="exportReport" class="filter-button">Export CSV</button>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h2>Report type</h2>
    </div>
    <div class="filters">
        <button type="button" class="filter-button report-type-button active" data-type="daily">Daily</button>
        <button type="button" class="filter-button report-type-button" data-type="weekly">Weekly</button>
        <button type="button" class="filter-button report-type-button" data-type="monthly">Monthly</button>
    </div>
</div>

<div class="panel history-panel">
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
        <select id="reportDepartment">
            <option value="">All departments</option>
        </select>
        <select id="reportEmployee">
            <option value="">All employees</option>
        </select>
    </div>
</div>

<div class="panel history-panel">
    <div class="panel-header">
        <h2>Summary</h2>
    </div>
    <div class="filters" style="gap: 18px; flex-wrap: wrap;">
        <div class="status-badge status-approved">Attendance: <strong id="reportAttendanceCount">0</strong></div>
        <div class="status-badge status-approved">Check-ins: <strong id="reportCheckins">0</strong></div>
        <div class="status-badge status-approved">Check-outs: <strong id="reportCheckouts">0</strong></div>
        <div class="status-badge status-rejected">Late arrivals: <strong id="reportLateArrivals">0</strong></div>
        <div class="status-badge status-rejected">Absentees: <strong id="reportAbsentees">0</strong></div>
    </div>
</div>

<div class="panel history-panel">
    <div class="panel-header">
        <h2>Report details</h2>
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
    </div>
    <div id="reportsEmpty" class="table-state hidden">
        No report data found for the selected range.
    </div>
</div>

<div class="toast-region"></div>
