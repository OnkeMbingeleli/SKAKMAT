<div class="page-heading">
    <div>
        <h1>Employees</h1>
        <p>View and manage employee records, attendance metrics, and departmental details.</p>
    </div>
    <div class="topbar-actions">
        <div class="search-box">
            <span></span>
            <input id="employeeSearch" type="search" placeholder="Search employees, email, department..." />
        </div>
        <button id="refreshEmployees" class="filter-button">Refresh</button>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h2>Filters</h2>
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

<div class="panel history-panel">
    <div class="panel-header">
        <h2>Employee list</h2>
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
                    <th>Late arrivals</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="employeesBody"></tbody>
        </table>
    </div>
    <div id="employeesEmpty" class="table-state hidden">
        No employee records found. Adjust filters or refresh to try again.
    </div>
    <div class="filters" style="justify-content: space-between;">
        <div id="employeesPaginationLabel">Page 1 of 1</div>
        <div>
            <button id="employeesPrevPage" class="filter-button">Previous</button>
            <button id="employeesNextPage" class="filter-button">Next</button>
        </div>
    </div>
</div>

<div id="employeeDetailPanel" class="panel hidden" style="position: relative;">
    <div class="panel-header">
        <div>
            <h2 id="detailEmployeeTitle">Employee details</h2>
            <p>Update employee profile and review attendance summary.</p>
        </div>
        <button id="closeEmployeeDetail" class="filter-button">Close</button>
    </div>
    <div class="filters" style="gap: 24px; flex-wrap: wrap;">
        <div style="flex:1; min-width:260px;">
            <strong>Attendance</strong>
            <div class="filters" style="flex-wrap: wrap; gap: 12px; margin-top: 12px;">
                <div><div class="status-badge status-approved"><span></span>Check-ins: <span id="detailCheckins">0</span></div></div>
                <div><div class="status-badge status-approved"><span></span>Check-outs: <span id="detailCheckouts">0</span></div></div>
                <div><div class="status-badge status-rejected"><span></span>Late: <span id="detailLateArrivals">0</span></div></div>
                <div><div class="status-badge status-approved"><span></span>Records: <span id="detailAttendanceCount">0</span></div></div>
                <div><div class="status-badge status-rejected"><span></span>Last seen: <span id="detailLastSeen">-</span></div></div>
            </div>
        </div>
    </div>
    <form id="employeeDetailForm" data-employee-id="0">
        <div class="filters" style="flex-wrap: wrap; gap: 16px;">
            <label style="flex:1; min-width:220px;">
                <span>First name</span>
                <input type="text" name="first_name" required />
            </label>
            <label style="flex:1; min-width:220px;">
                <span>Last name</span>
                <input type="text" name="last_name" required />
            </label>
            <label style="flex:1; min-width:220px;">
                <span>Email</span>
                <input type="email" name="email" required />
            </label>
            <label style="flex:1; min-width:220px;">
                <span>Department</span>
                <input type="text" name="department" required />
            </label>
            <label style="flex:1; min-width:220px;">
                <span>Position</span>
                <input type="text" name="position" required />
            </label>
        </div>
        <div style="margin-top: 18px; display:flex; gap: 12px;">
            <button type="submit" class="filter-button">Save changes</button>
        </div>
    </form>
</div>

<div class="toast-region"></div>
