const leaveTypes = [
    'annual',
    'sick',
    'unpaid',
    'family responsibility',
    'study leave',
    'maternity leave',
    'paternity leave'
];

function createToast(message, type = 'success') {
    const container = document.querySelector('.toast-region');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;

    container.appendChild(toast);
    setTimeout(() => {
        toast.remove();
    }, 4000);
}

function getStatusBadge(status) {
    const normalized = (status || '').toLowerCase();
    const label = normalized.charAt(0).toUpperCase() + normalized.slice(1);
    return `<span class="status-badge status-${normalized}"><span></span>${label}</span>`;
}

async function handleAuthError(response) {
    if (!response || response.error) {
        if (response && response.error && /auth|token/i.test(response.error)) {
            createToast('Session expired. Redirecting to login...', 'error');
            setTimeout(() => {
                window.location.href = '/public/index.php?page=login';
            }, 1200);
            return true;
        }
    }
    return false;
}

function normalizeDate(value) {
    return value ? new Date(value).toLocaleDateString('en-GB') : '-';
}

function renderLeaveRow(leave) {
    const statusBadge = getStatusBadge(leave.status || 'pending');

    if (document.body.dataset.page === 'admin-leave-requests') {
        return `
            <tr>
                <td>${leave.user_name || '-'}</td>
                <td>${leave.leave_type || '-'}</td>
                <td>${normalizeDate(leave.start_date)}</td>
                <td>${normalizeDate(leave.end_date)}</td>
                <td>${leave.reason || '-'}</td>
                <td>${statusBadge}</td>
                <td class="action-buttons">
                    ${leave.status === 'pending' ? `
                        <button type="button" class="approve-button" data-id="${leave.id}">✓</button>
                        <button type="button" class="reject-button" data-id="${leave.id}">✕</button>
                    ` : '-'}
                </td>
            </tr>
        `;
    }

    return `
        <tr>
            <td>${leave.id}</td>
            <td>${leave.leave_type || '-'}</td>
            <td>${normalizeDate(leave.start_date)}</td>
            <td>${normalizeDate(leave.end_date)}</td>
            <td>${leave.reason || '-'}</td>
            <td>${statusBadge}</td>
        </tr>
    `;
}

async function loadLeaveRequests() {
    const listBody = document.getElementById('leaveRequestsBody');
    const listEmpty = document.getElementById('leaveRequestsEmpty');
    if (!listBody) return;

    listBody.innerHTML = '';
    listEmpty.classList.add('hidden');

    const endpoint = document.body.dataset.page === 'admin-leave-requests'
        ? '/leave-requests?status=pending'
        : '/leave-requests';
    const response = await apiCall(endpoint, { method: 'GET' });
    if (!response.success) {
        if (await handleAuthError(response)) return;
        createToast(response.error || 'Unable to load leave requests', 'error');
        return;
    }

    const requests = response.data || [];
    if (!requests.length) {
        listEmpty.classList.remove('hidden');
        return;
    }

    const rows = requests.map(leave => renderLeaveRow(leave)).join('');

    listBody.innerHTML = rows;
    attachActionButtons();
}

function attachActionButtons() {
    document.querySelectorAll('.approve-button, .reject-button').forEach(button => {
        button.addEventListener('click', async function () {
            const leaveId = this.dataset.id;
            const status = this.classList.contains('approve-button') ? 'approved' : 'rejected';
            await updateLeaveStatus(leaveId, status);
        });
    });
}

async function updateLeaveStatus(id, status) {
    const response = await apiCall(`/leave-requests/${id}`, {
        method: 'PATCH',
        body: JSON.stringify({ status })
    });

    if (!response.success) {
        if (await handleAuthError(response)) return;
        createToast(response.error || 'Unable to update leave request', 'error');
        return;
    }

    createToast(`Leave request ${status}`, 'success');
    await loadLeaveRequests();
}

async function submitLeaveRequest(event) {
    event.preventDefault();
    const submitButton = event.target.querySelector('button[type="submit"]');
    const formData = new FormData(event.target);

    const data = {
        leave_type: formData.get('leave_type'),
        start_date: formData.get('start_date'),
        end_date: formData.get('end_date'),
        reason: formData.get('reason') || null,
    };

    if (!data.leave_type || !data.start_date || !data.end_date) {
        createToast('Please fill in all required fields.', 'error');
        return;
    }

    submitButton.disabled = true;
    submitButton.textContent = 'Submitting...';

    const response = await apiCall('/leave-requests', {
        method: 'POST',
        body: JSON.stringify(data)
    });

    submitButton.disabled = false;
    submitButton.textContent = 'Submit request';

    if (!response.success) {
        if (await handleAuthError(response)) return;
        createToast(response.error || 'Unable to create leave request', 'error');
        return;
    }

    event.target.reset();
    createToast('Leave request submitted', 'success');
    await loadLeaveRequests();
}

function populateLeaveTypeOptions() {
    const select = document.querySelector('select[name="leave_type"]');
    if (!select) return;

    select.innerHTML = leaveTypes.map(type => `
        <option value="${type}">${type.charAt(0).toUpperCase() + type.slice(1)}</option>
    `).join('');
}

function initLeavePage() {
    populateLeaveTypeOptions();

    const form = document.getElementById('leaveRequestForm');
    if (form) {
        form.addEventListener('submit', submitLeaveRequest);
    }

    loadLeaveRequests();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLeavePage);
} else {
    initLeavePage();
}
