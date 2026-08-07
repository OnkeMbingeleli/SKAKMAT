function createToast(message, type = 'success') {
    const container = document.querySelector('.toast-region');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    container.appendChild(toast);

    setTimeout(() => toast.remove(), 3800);
}

async function handleAuthError(response) {
    if (response && response.error && /auth|token/i.test(response.error)) {
        createToast('Session expired. Redirecting to login...', 'error');
        setTimeout(() => {
            window.location.href = '/index.php?page=login';
        }, 1200);
        return true;
    }
    return false;
}

function buildQuery(params) {
    const searchParams = new URLSearchParams();
    Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') {
            searchParams.set(key, value);
        }
    });
    return searchParams.toString();
}

function formatNumber(value) {
    return value === null || value === undefined ? '0' : String(value);
}

function renderReportRow(row) {
    return `
        <tr>
            <td>${row.period || '-'}</td>
            <td>${row.start_date || '-'}</td>
            <td>${row.end_date || '-'}</td>
            <td>${formatNumber(row.present_count)}</td>
            <td>${formatNumber(row.total_checkins)}</td>
            <td>${formatNumber(row.total_checkouts)}</td>
            <td>${formatNumber(row.late_arrivals)}</td>
        </tr>
    `;
}

const reportState = {
    type: 'daily',
    start_date: '',
    end_date: '',
    department: '',
    employee_id: '',
};

async function loadReports() {
    const query = buildQuery({
        type: reportState.type,
        start_date: reportState.start_date,
        end_date: reportState.end_date,
        department: reportState.department,
        employee_id: reportState.employee_id,
    });

    const response = await apiCall('/reports?' + query, { method: 'GET' });
    if (!response.success) {
        if (await handleAuthError(response)) return;
        createToast(response.error || 'Unable to load reports', 'error');
        return;
    }

    const data = response.data || {};
    const summary = data.summary || {};
    const rows = data.rows || [];
    const meta = data.meta || {};

    document.getElementById('reportAttendanceCount').textContent = formatNumber(summary.attendance_count);
    document.getElementById('reportCheckins').textContent = formatNumber(summary.total_checkins);
    document.getElementById('reportCheckouts').textContent = formatNumber(summary.total_checkouts);
    document.getElementById('reportLateArrivals').textContent = formatNumber(summary.late_arrivals);
    document.getElementById('reportAbsentees').textContent = formatNumber(summary.absentees);

    const body = document.getElementById('reportsBody');
    const empty = document.getElementById('reportsEmpty');
    body.innerHTML = rows.map(renderReportRow).join('');
    empty.classList.toggle('hidden', rows.length > 0);

    populateFilterData(meta);
    updateTypeButtons();
}

function updateTypeButtons() {
    document.querySelectorAll('.report-type-button').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.type === reportState.type);
    });
}

function populateFilterData(meta) {
    const departmentSelect = document.getElementById('reportDepartment');
    const employeeSelect = document.getElementById('reportEmployee');

    if (departmentSelect && meta.departments) {
        departmentSelect.innerHTML = '<option value="">All departments</option>' +
            meta.departments.map(dep => `<option value="${dep}">${dep.charAt(0).toUpperCase() + dep.slice(1)}</option>`).join('');
        departmentSelect.value = reportState.department;
    }

    if (employeeSelect && meta.employees) {
        employeeSelect.innerHTML = '<option value="">All employees</option>' +
            meta.employees.map(emp => `<option value="${emp.id}">${emp.name}</option>`).join('');
        employeeSelect.value = reportState.employee_id;
    }
}

async function exportReport() {
    const query = buildQuery({
        type: reportState.type,
        start_date: reportState.start_date,
        end_date: reportState.end_date,
        department: reportState.department,
        employee_id: reportState.employee_id,
        format: 'csv',
    });
    window.location.href = `/api/reports?${query}`;
}

function initializeFilters() {
    const startDateInput = document.getElementById('reportStartDate');
    const endDateInput = document.getElementById('reportEndDate');
    const departmentSelect = document.getElementById('reportDepartment');
    const employeeSelect = document.getElementById('reportEmployee');
    const refreshButton = document.getElementById('refreshReports');
    const exportButton = document.getElementById('exportReport');

    const today = new Date();
    const sevenDaysAgo = new Date(today);
    sevenDaysAgo.setDate(today.getDate() - 7);

    reportState.start_date = startDateInput.value || sevenDaysAgo.toISOString().slice(0, 10);
    reportState.end_date = endDateInput.value || today.toISOString().slice(0, 10);

    startDateInput.value = reportState.start_date;
    endDateInput.value = reportState.end_date;

    startDateInput.addEventListener('change', event => {
        reportState.start_date = event.target.value;
        loadReports();
    });

    endDateInput.addEventListener('change', event => {
        reportState.end_date = event.target.value;
        loadReports();
    });

    departmentSelect.addEventListener('change', event => {
        reportState.department = event.target.value;
        loadReports();
    });

    employeeSelect.addEventListener('change', event => {
        reportState.employee_id = event.target.value;
        loadReports();
    });

    refreshButton.addEventListener('click', loadReports);
    exportButton.addEventListener('click', exportReport);

    document.querySelectorAll('.report-type-button').forEach(button => {
        button.addEventListener('click', () => {
            reportState.type = button.dataset.type;
            loadReports();
        });
    });
}

function initReportsPage() {
    initializeFilters();
    loadReports();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initReportsPage);
} else {
    initReportsPage();
}
