/**
 * Drives both the staff "Leave" page (submit + own history) and the admin
 * "Leave Requests" page (review all + approve/reject). Detects which one
 * it's on by the elements present. Relies on api.js.
 */
(function () {
    'use strict';

    const el = (id) => document.getElementById(id);
    const isAdminPage = !!document.querySelector('.leave-status-button');
    const hasSubmitForm = !!el('leaveRequestForm');

    function toast(message, isError) {
        const region = document.querySelector('.toast-region');
        if (!region) { alert(message); return; }
        const node = document.createElement('div');
        node.className = 'toast ' + (isError ? 'error' : 'success');
        node.textContent = message;
        region.appendChild(node);
        setTimeout(() => node.remove(), 3500);
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str == null ? '' : String(str);
        return div.innerHTML;
    }

    function titleCase(str) {
        return (str || '').replace(/\b\w/g, c => c.toUpperCase());
    }

    function statusBadge(status) {
        const cls = status === 'approved' ? 'status-approved' : status === 'rejected' ? 'status-rejected' : 'status-pending';
        return `<span class="status-badge ${cls}"><span></span>${escapeHtml(titleCase(status))}</span>`;
    }

    // =================================================================
    // STAFF: submit + own history + leave balance
    // =================================================================

    async function loadLeaveBalance() {
        const container = el('leaveBalanceSummary');
        if (!container) return;
        const res = await apiCall('/api/leave-balance');
        if (!res || res.success === false) {
            container.innerHTML = '';
            return;
        }
        const labels = { annual: 'Annual', sick: 'Sick', other: 'Other' };
        container.innerHTML = (res.data || []).map(s => `
            <div class="leave-balance-card ${Number(s.days_remaining) < 0 ? 'exceeded' : ''}">
                <div class="type">${escapeHtml(s.leave_type_label || labels[s.leave_type] || titleCase(s.leave_type))}</div>
                <div class="value">${s.unlimited ? 'Unlimited' : s.days_remaining} <small>${s.unlimited ? '' : `/ ${s.allocated_days} left`}</small></div>
            </div>
        `).join('');
    }

    async function loadOwnRequests() {
        const body = el('leaveRequestsBody');
        const emptyEl = el('leaveRequestsEmpty');
        if (!body) return;
        body.innerHTML = '<tr><td colspan="5" style="text-align:center; color:var(--muted);">Loading…</td></tr>';

        const res = await apiCall('/api/leave-requests');
        if (!res || res.success === false) {
            body.innerHTML = '';
            emptyEl.classList.remove('hidden');
            emptyEl.textContent = (res && res.error) || 'Could not load your requests.';
            return;
        }

        const rows = res.data || [];
        if (!rows.length) {
            body.innerHTML = '';
            emptyEl.classList.remove('hidden');
            return;
        }
        emptyEl.classList.add('hidden');
        body.innerHTML = rows.map(r => `
            <tr>
                <td>${escapeHtml(titleCase(r.leave_type))}</td>
                <td>${escapeHtml(r.start_date)}</td>
                <td>${escapeHtml(r.end_date)}</td>
                <td>${escapeHtml(r.reason || '—')}</td>
                <td>${statusBadge(r.status)}</td>
            </tr>
        `).join('');
    }

    function wireSubmitForm() {
        const form = el('leaveRequestForm');
        const errorEl = el('leaveRequestError');
        const warningEl = el('leaveWarning');
        const btn = el('leaveSubmitBtn');

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            errorEl.style.display = 'none';
            warningEl.classList.add('hidden');

            const payload = {
                leave_type: form.leave_type.value,
                start_date: form.start_date.value,
                end_date: form.end_date.value,
                reason: form.reason.value.trim(),
            };

            if (payload.end_date < payload.start_date) {
                errorEl.textContent = 'End date cannot be before the start date.';
                errorEl.style.display = 'block';
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Submitting…';
            try {
                const res = await apiCall('/api/leave-requests', { method: 'POST', body: JSON.stringify(payload) });
                if (!res || res.success === false) {
                    throw new Error((res && res.error) || 'Could not submit request.');
                }
                form.reset();
                toast('Leave request submitted.');
                if (res.warning) {
                    warningEl.textContent = res.warning;
                    warningEl.classList.remove('hidden');
                }
                await Promise.all([loadOwnRequests(), loadLeaveBalance()]);
            } catch (error) {
                errorEl.textContent = error.message;
                errorEl.style.display = 'block';
            } finally {
                btn.disabled = false;
                btn.textContent = 'Submit request';
            }
        });
    }

    // =================================================================
    // ADMIN: all requests + approve/reject
    // =================================================================

    let currentStatus = '';

    async function loadAdminRequests() {
        const body = el('leaveRequestsBody');
        const emptyEl = el('leaveRequestsEmpty');
        body.innerHTML = '<tr><td colspan="7" style="text-align:center; color:var(--muted);">Loading…</td></tr>';

        const query = currentStatus ? `?status=${encodeURIComponent(currentStatus)}` : '';
        const [res, balanceRes] = await Promise.all([
            apiCall(`/api/leave-requests${query}`),
            apiCall('/api/leave-balance/all'),
        ]);
        if (!res || res.success === false) {
            body.innerHTML = '';
            emptyEl.classList.remove('hidden');
            emptyEl.textContent = (res && res.error) || 'Could not load leave requests.';
            return;
        }

        const rows = res.data || [];
        if (!rows.length) {
            body.innerHTML = '';
            emptyEl.classList.remove('hidden');
            return;
        }
        emptyEl.classList.add('hidden');
        const balancesByUser = {};
        (balanceRes && balanceRes.data || []).forEach(employee => {
            balancesByUser[employee.user_id] = employee.balances || [];
        });

        body.innerHTML = rows.map(r => {
            const name = `${r.first_name || ''} ${r.last_name || ''}`.trim() || `Employee #${r.user_id}`;
            const actions = r.status === 'pending'
                ? `<button type="button" class="filter-button" data-action="approve" data-id="${r.id}">Approve</button>
                   <button type="button" class="filter-button" data-action="reject" data-id="${r.id}" style="color:var(--red);">Reject</button>`
                : '<span style="color:var(--muted); font-size:12.5px;">—</span>';
            const balance = (balancesByUser[r.user_id] || []).find(b => b.leave_type === r.leave_type);
            const daysLeft = balance && balance.unlimited ? 'Unlimited'
                : balance ? `${escapeHtml(balance.days_remaining)} / ${escapeHtml(balance.allocated_days)}` : '—';
            return `
                <tr>
                    <td>${escapeHtml(name)}<br><span style="color:var(--muted); font-size:12px;">${escapeHtml(r.department || '')}</span></td>
                    <td>${escapeHtml(titleCase(r.leave_type))}</td>
                    <td>${escapeHtml(r.start_date)}</td>
                    <td>${escapeHtml(r.end_date)}</td>
                    <td>${escapeHtml(r.reason || '—')}</td>
                    <td>${statusBadge(r.status)}</td>
                    <td>${daysLeft}</td>
                    <td style="display:flex; gap:6px; flex-wrap:wrap;">${actions}</td>
                </tr>
            `;
        }).join('');
    }

    async function setStatus(id, status, btn) {
        const original = btn.textContent;
        btn.disabled = true;
        btn.textContent = status === 'approved' ? 'Approving…' : 'Rejecting…';
        try {
            const res = await apiCall(`/api/leave-requests/${id}`, { method: 'PATCH', body: JSON.stringify({ status }) });
            if (!res || res.success === false) {
                throw new Error((res && res.error) || 'Could not update request.');
            }
            toast(`Leave request ${status}.` + (res.warning ? ' ' + res.warning : ''), status === 'rejected');
            await loadAdminRequests();
        } catch (error) {
            toast(error.message, true);
            btn.disabled = false;
            btn.textContent = original;
        }
    }

    function wireAdminPage() {
        el('refreshLeaveRequests').addEventListener('click', loadAdminRequests);

        document.querySelectorAll('.leave-status-button').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.leave-status-button').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                currentStatus = btn.dataset.status;
                loadAdminRequests();
            });
        });

        el('leaveRequestsBody').addEventListener('click', (e) => {
            const btn = e.target.closest('button[data-action]');
            if (!btn) return;
            const id = btn.dataset.id;
            const status = btn.dataset.action === 'approve' ? 'approved' : 'rejected';
            if (status === 'rejected' && !confirm('Reject this leave request?')) return;
            setStatus(id, status, btn);
        });

        loadAdminRequests();
    }

    // =================================================================
    // Init
    // =================================================================

    function init() {
        if (isAdminPage) {
            wireAdminPage();
            return;
        }
        if (hasSubmitForm) {
            wireSubmitForm();
            loadLeaveBalance();
            loadOwnRequests();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
