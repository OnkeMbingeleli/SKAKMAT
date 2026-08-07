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

function normalizeDate(value) {
    if (!value) return '-';
    const date = new Date(value);
    return date.toLocaleDateString('en-GB');
}

function getStatusBadge(status) {
    const normalized = (status || 'inactive').toLowerCase();
    const label = normalized.charAt(0).toUpperCase() + normalized.slice(1);
    return `<span class="status-badge ${normalized === 'active' ? 'status-approved' : 'status-rejected'}"><span></span>${label}</span>`;
}

function renderEmployeeRow(employee) {
    return `
        <tr>
            <td>${employee.id}</td>
            <td>${employee.first_name} ${employee.last_name}</td>
            <td>${employee.email}</td>
            <td>${employee.department || '-'}</td>
            <td>${employee.position || '-'}</td>
            <td>${getStatusBadge(employee.status)}</td>
            <td>${employee.attendance_count ?? 0}</td>
            <td>${employee.total_checkins ?? 0}</td>
            <td>${employee.total_checkouts ?? 0}</td>
            <td>${employee.late_arrivals ?? 0}</td>
            <td><button type="button" class="detail-button" data-id="${employee.id}">View</button></td>
        </tr>
    `;
}

let employeesState = {
    page: 1,
    limit: 20,
    search: '',
    department: '',
    position: '',
};

async function loadEmployees() {
    const tableBody = document.getElementById('employeesBody');
    const emptyState = document.getElementById('employeesEmpty');
    const paginationLabel = document.getElementById('employeesPaginationLabel');
    const prevButton = document.getElementById('employeesPrevPage');
    const nextButton = document.getElementById('employeesNextPage');

    if (!tableBody) return;
    tableBody.innerHTML = '';
    emptyState?.classList.add('hidden');

    const query = buildQuery({
        attendance: true,
        search: employeesState.search,
        department: employeesState.department,
        position: employeesState.position,
        page: employeesState.page,
        limit: employeesState.limit,
    });

    const response = await apiCall('/users?' + query, { method: 'GET' });
    if (!response.success) {
        if (await handleAuthError(response)) return;
        createToast(response.error || 'Unable to load employees', 'error');
        return;
    }

    const users = response.data || [];
    const total = response.meta?.total ?? users.length;
    const lastPage = Math.max(1, Math.ceil(total / employeesState.limit));
    paginationLabel.textContent = `Page ${employeesState.page} of ${lastPage}`;
    prevButton.disabled = employeesState.page <= 1;
    nextButton.disabled = employeesState.page >= lastPage;

    if (!users.length) {
        emptyState?.classList.remove('hidden');
        return;
    }

    tableBody.innerHTML = users.map(renderEmployeeRow).join('');
    attachEmployeeActions();
}

function attachEmployeeActions() {
    document.querySelectorAll('.detail-button').forEach(button => {
        button.addEventListener('click', function () {
            const id = this.dataset.id;
            if (id) {
                showEmployeeDetails(Number(id));
            }
        });
    });
}

async function loadFilters() {
    const departmentSelect = document.getElementById('employeeDepartment');
    const positionSelect = document.getElementById('employeePosition');

    const response = await apiCall('/users?role=staff&limit=100', { method: 'GET' });
    if (!response.success) {
        return;
    }

    const departments = response.meta?.departments || [];
    const positions = response.meta?.positions || [];

    if (departmentSelect) {
        departmentSelect.innerHTML = '<option value="">All departments</option>' +
            departments.map(dep => `<option value="${dep}">${dep.charAt(0).toUpperCase() + dep.slice(1)}</option>`).join('');
    }

    if (positionSelect) {
        positionSelect.innerHTML = '<option value="">All positions</option>' +
            positions.map(pos => `<option value="${pos}">${pos}</option>`).join('');
    }
}

async function showEmployeeDetails(id) {
    const response = await apiCall(`/users/${id}`, { method: 'GET' });
    const detailPanel = document.getElementById('employeeDetailPanel');
    const detailForm = document.getElementById('employeeDetailForm');

    if (!response.success) {
        if (await handleAuthError(response)) return;
        createToast(response.error || 'Unable to load employee details', 'error');
        return;
    }

    const { user, attendance } = response.data;
    if (!detailPanel || !detailForm) return;

    detailPanel.classList.remove('hidden');
    detailForm.querySelector('[name="first_name"]').value = user.first_name || '';
    detailForm.querySelector('[name="last_name"]').value = user.last_name || '';
    detailForm.querySelector('[name="email"]').value = user.email || '';
    detailForm.querySelector('[name="department"]').value = user.department || '';
    detailForm.querySelector('[name="position"]').value = user.position || '';
    detailForm.dataset.employeeId = String(user.id);

    document.getElementById('detailEmployeeTitle').textContent = `${user.first_name} ${user.last_name}`;
    document.getElementById('detailAttendanceCount').textContent = attendance.attendance_count ?? 0;
    document.getElementById('detailCheckins').textContent = attendance.total_checkins ?? 0;
    document.getElementById('detailCheckouts').textContent = attendance.total_checkouts ?? 0;
    document.getElementById('detailLateArrivals').textContent = attendance.late_arrivals ?? 0;
    document.getElementById('detailLastSeen').textContent = attendance.last_seen || '-';
}

async function submitEmployeeDetails(event) {
    event.preventDefault();
    const form = event.target;
    const employeeId = Number(form.dataset.employeeId || 0);
    if (!employeeId) {
        createToast('No employee selected', 'error');
        return;
    }

    const data = {
        first_name: form.querySelector('[name="first_name"]').value.trim(),
        last_name: form.querySelector('[name="last_name"]').value.trim(),
        email: form.querySelector('[name="email"]').value.trim(),
        department: form.querySelector('[name="department"]').value,
        position: form.querySelector('[name="position"]').value.trim(),
    };

    const response = await apiCall(`/users/${employeeId}`, {
        method: 'PATCH',
        body: JSON.stringify(data),
    });

    if (!response.success) {
        if (await handleAuthError(response)) return;
        createToast(response.error || 'Unable to update employee', 'error');
        return;
    }

    createToast('Employee updated successfully', 'success');
    await loadEmployees();
}

function closeEmployeeDetails() {
    const detailPanel = document.getElementById('employeeDetailPanel');
    if (detailPanel) {
        detailPanel.classList.add('hidden');
    }
}

function wireFormEvents() {
    const searchInput = document.getElementById('employeeSearch');
    const departmentSelect = document.getElementById('employeeDepartment');
    const positionSelect = document.getElementById('employeePosition');
    const refreshButton = document.getElementById('refreshEmployees');
    const prevButton = document.getElementById('employeesPrevPage');
    const nextButton = document.getElementById('employeesNextPage');
    const detailForm = document.getElementById('employeeDetailForm');
    const closeButton = document.getElementById('closeEmployeeDetail');

    searchInput?.addEventListener('input', () => {
        employeesState.search = searchInput.value.trim();
        employeesState.page = 1;
        loadEmployees();
    });

    departmentSelect?.addEventListener('change', () => {
        employeesState.department = departmentSelect.value;
        employeesState.page = 1;
        loadEmployees();
    });

    positionSelect?.addEventListener('change', () => {
        employeesState.position = positionSelect.value;
        employeesState.page = 1;
        loadEmployees();
    });

    refreshButton?.addEventListener('click', async () => {
        await loadFilters();
        await loadEmployees();
    });

    prevButton?.addEventListener('click', () => {
        if (employeesState.page > 1) {
            employeesState.page -= 1;
            loadEmployees();
        }
    });

    nextButton?.addEventListener('click', () => {
        employeesState.page += 1;
        loadEmployees();
    });

    detailForm?.addEventListener('submit', submitEmployeeDetails);
    closeButton?.addEventListener('click', closeEmployeeDetails);
}

async function initEmployeesPage() {
    await loadFilters();
    wireFormEvents();
    loadEmployees();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initEmployeesPage);
} else {
    initEmployeesPage();
}
