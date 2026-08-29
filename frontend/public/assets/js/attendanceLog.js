(() => {
    'use strict';

    // Role and DOM elements
    const role = document.body.dataset.role === 'admin' ? 'admin' : 'staff';
    const tableBody = document.getElementById('attendanceTableBody');
    const form = document.getElementById('filterForm');
    const errorBox = document.getElementById('attendanceError');
    const columns = role === 'admin' ? 6 : 5;
    const API_BASE = window.CONFIG?.API_URL || 'http://127.0.0.1:8000';

    // =====================================================
    // HELPERS
    // =====================================================

    function getToken() {
        return localStorage.getItem('token')
            || localStorage.getItem('checkmate_token')
            || document.cookie
                .split('; ')
                .find(c => c.startsWith('checkmate_token='))
                ?.split('=').slice(1).join('=') || '';
    }

    function getCurrentUser() {
        try {
            const stored = localStorage.getItem('user') || localStorage.getItem('checkmate_user');
            if (stored) return JSON.parse(stored);
        } catch (e) {}
        const cookie = document.cookie.split('; ').find(c => c.startsWith('checkmate_user='));
        if (cookie) {
            try { return JSON.parse(decodeURIComponent(cookie.split('=').slice(1).join('='))); } catch (e) {}
        }
        return null;
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));
    }

    function initials(name) {
        return String(name || '?').trim().split(/\s+/).slice(0,2).map(p => p[0]).join('').toUpperCase() || '?';
    }

    function normaliseStatus(value) {
        return String(value || '').toLowerCase().replace(/[\s-]/g, '_');
    }

    function formatDate(value) {
        if (!value) return '—';
        const d = new Date(`${String(value).substring(0,10)}T00:00:00`);
        return isNaN(d) ? escapeHtml(value) : new Intl.DateTimeFormat('en-ZA', {
            day: '2-digit', month: 'short', year: 'numeric'
        }).format(d);
    }

    function formatTime(value) {
        if (!value) return '<span class="time muted">Not clocked out</span>';
        let time = String(value);
        if (time.includes(' ')) time = time.split(' ')[1];
        const [h, m] = time.split(':');
        if (!h || !m) return escapeHtml(value);
        const hour = Number(h);
        const formattedHour = hour % 12 || 12;
        const period = hour >= 12 ? 'PM' : 'AM';
        return `<span class="time">${formattedHour}:${m} ${period}</span>`;
    }

    function minutesFromDuration(value) {
        if (!value || typeof value !== 'string') return 0;
        const [h, m, s] = value.split(':').map(Number);
        return (h||0)*60 + (m||0) + Math.round((s||0)/60);
    }

    function formatDuration(value) {
        if (!value) return 'In progress';
        const mins = minutesFromDuration(value);
        if (!mins) return 'In progress';
        const h = Math.floor(mins/60);
        const m = mins%60;
        return h === 0 ? `${m}m` : m === 0 ? `${h}h` : `${h}h ${m}m`;
    }

    function statusBadge(status, checkIn, checkOut) {
        const s = normaliseStatus(status);
        if (checkIn && !checkOut) return '<span class="status status-active">Active</span>';
        if (s === 'late') return '<span class="status status-late">Late</span>';
        if (s === 'absent') return '<span class="status status-absent">Absent</span>';
        return '<span class="status status-present">On time</span>';
    }

    function getField(record, field) {
        const target = field.toLowerCase().replace(/[^a-z0-9]/g, '');
        for (const key of Object.keys(record || {})) {
            if (key.toLowerCase().replace(/[^a-z0-9]/g, '') === target) return record[key];
        }
        return undefined;
    }

    function normaliseRecord(record) {
        const name = getField(record, 'NAME') ?? getField(record, 'employee_name') ?? getField(record, 'name');
        const department = getField(record, 'DEPARTMENT') ?? getField(record, 'department');
        const date = getField(record, 'DATE') ?? getField(record, 'date');
        const checkIn = getField(record, 'CHECK IN') ?? getField(record, 'CHECK_IN') ?? getField(record, 'clock_in_at');
        const checkOut = getField(record, 'CHECK OUT') ?? getField(record, 'CHECK_OUT') ?? getField(record, 'clock_out_at');
        const hoursWorked = getField(record, 'HOURS WORKED') ?? getField(record, 'HOURS_WORKED') ?? getField(record, 'hours_worked') ?? getField(record, 'total_hours');
        const status = getField(record, 'STATUS') ?? getField(record, 'status');

        let formattedDate = date;
        if (!formattedDate && checkIn) formattedDate = String(checkIn).substring(0,10);

        const formatPart = (value) => {
            const str = String(value || '');
            return str.includes(' ') ? str.split(' ')[1] : str;
        };

        return {
            employee_name: name || 'Unknown Employee',
            department: department || '',
            date: formattedDate || null,
            check_in: checkIn ? formatPart(checkIn) : null,
            check_out: checkOut ? formatPart(checkOut) : null,
            total_hours: hoursWorked,
            status: status || 'present'
        };
    }

    // =====================================================
    // SUMMARY
    // =====================================================

    function updateSummary(records) {
        if (role === 'admin') {
            const today = new Date().toISOString().slice(0,10);
            const todays = records.filter(r => String(r.date || '').slice(0,10) === today);
            const onsite = new Set(todays.filter(r => r.check_in && !r.check_out).map(r => r.employee_name)).size;
            const checkedIn = todays.filter(r => r.check_in).length;
            const checkedOut = todays.filter(r => r.check_out).length;
            const late = todays.filter(r => normaliseStatus(r.status) === 'late').length;

            document.getElementById('adminOnsite').textContent = onsite;
            document.getElementById('adminCheckedIn').textContent = checkedIn;
            document.getElementById('adminCheckedOut').textContent = checkedOut;
            document.getElementById('adminLate').textContent = late;
            document.getElementById('recordNote').textContent = records.length
                ? `${records.length} attendance record${records.length === 1 ? '' : 's'} found`
                : 'No attendance records found';
        } else {
            const totalMinutes = records.reduce((sum, r) => sum + minutesFromDuration(r.total_hours), 0);
            const onTime = records.filter(r => ['on_time', 'present'].includes(normaliseStatus(r.status))).length;
            document.getElementById('totalRecords').textContent = records.length;
            document.getElementById('totalHours').textContent = `${Math.floor(totalMinutes/60)}h ${totalMinutes%60}m`;
            document.getElementById('onTimeRate').textContent = records.length
                ? `${Math.round((onTime/records.length)*100)}%` : '—';
            document.getElementById('recordNote').textContent = records.length
                ? `${records.length} record${records.length === 1 ? '' : 's'} found`
                : 'No records found';
        }
    }

    // =====================================================
    // RENDER
    // =====================================================

    function render(records) {
        updateSummary(records);
        if (!records.length) {
            tableBody.innerHTML = `<tr class="empty-row"><td colspan="${columns}"><span class="empty-icon">◷</span><strong>No attendance records yet</strong><br><span>Attendance history will appear here once a record is available.</span></td></tr>`;
            return;
        }
        tableBody.innerHTML = records.map(record => `
            <tr>
                ${role === 'admin' ? `<td><span class="employee"><span class="avatar">${initials(record.employee_name)}</span>${escapeHtml(record.employee_name)}</span></td>` : ''}
                <td>${formatDate(record.date)}</td>
                <td>${formatTime(record.check_in)}</td>
                <td>${record.check_out ? formatTime(record.check_out) : '<span class="time muted">Not clocked out</span>'}</td>
                <td class="hours">${formatDuration(record.total_hours)}</td>
                <td>${statusBadge(record.status, record.check_in, record.check_out)}</td>
            </tr>
        `).join('');
    }

    // =====================================================
    // FETCH
    // =====================================================

    async function apiRequest(url, token) {
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                ...(token ? { 'Authorization': `Bearer ${token}` } : {})
            }
        });
        const text = await response.text();
        let data;
        try { data = JSON.parse(text); } catch (e) { throw new Error('Invalid JSON from server'); }
        if (!response.ok) throw new Error(data.error || `HTTP ${response.status}`);
        if (!data.success) throw new Error(data.error || 'Request failed');
        if (!Array.isArray(data.data)) throw new Error('Expected array of records');
        return data.data;
    }

    async function loadAttendance(filters = {}) {
        tableBody.innerHTML = `<tr class="loading-row"><td colspan="${columns}"><span class="loading-dot"></span> Loading records</td></tr>`;
        if (errorBox) errorBox.classList.remove('visible');

        const token = getToken();
        if (!token) {
            showError('No authentication token found. Please log in again.');
            return;
        }

        try {
            let url;
            if (role === 'admin') {
                url = `${API_BASE}/api/attendance?action=history`;
            } else {
                const user = getCurrentUser();
                const userId = user?.id ?? user?.user_id ?? user?.ID;
                if (!userId) throw new Error('Unable to determine user ID');
                url = `${API_BASE}/api/attendance?action=history`;
            }

            let records = await apiRequest(url, token);
            records = records.map(normaliseRecord);

            // Apply filters (client-side)
            const search = String(filters.search || '').toLowerCase().trim();
            if (search) records = records.filter(r => r.employee_name.toLowerCase().includes(search));
            if (filters.start_date) records = records.filter(r => String(r.date || '') >= filters.start_date);
            if (filters.end_date) records = records.filter(r => String(r.date || '') <= filters.end_date);
            if (filters.employee) records = records.filter(r => r.employee_name === filters.employee);

            render(records);
        } catch (err) {
            console.error('Error loading attendance:', err);
            updateSummary([]);
            tableBody.innerHTML = `<tr class="empty-row"><td colspan="${columns}"><span class="empty-icon">!</span><strong>We couldn't load attendance records</strong><br><span>${escapeHtml(err.message)}</span></td></tr>`;
            showError(err.message);
        }
    }

    function showError(message) {
        if (!errorBox) return;
        errorBox.textContent = message;
        errorBox.classList.add('visible');
    }

    // =====================================================
    // EVENTS
    // =====================================================

    form?.addEventListener('submit', e => {
        e.preventDefault();
        const filters = Object.fromEntries(new FormData(form).entries());
        loadAttendance(filters);
    });

    document.getElementById('resetBtn')?.addEventListener('click', () => {
        form?.reset();
        loadAttendance();
    });

    document.querySelector('.export-btn')?.addEventListener('click', async () => {
        // Simple print export
        try {
            // Get current records from table body or re-fetch
            const records = window._lastRecords || [];
            if (!records.length) {
                // Re-fetch without filters
                await loadAttendance({});
            }
            const printable = window._lastRecords || [];
            const printWindow = window.open('', '_blank');
            if (!printWindow) return showError('Please allow popups to export');
            const html = `<!doctype html><html><head><title>Attendance Export</title><style>body{font-family:sans-serif;margin:20px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #ddd;padding:8px;text-align:left}th{background:#f5f5f5}</style></head><body><h2>Attendance Export</h2><table><thead><tr><th>Name</th><th>Date</th><th>Check In</th><th>Check Out</th><th>Hours</th><th>Status</th></tr></thead><tbody>${printable.map(r => `<tr><td>${escapeHtml(r.employee_name)}</td><td>${escapeHtml(r.date||'')}</td><td>${escapeHtml(r.check_in||'')}</td><td>${escapeHtml(r.check_out||'')}</td><td>${escapeHtml(r.total_hours||'')}</td><td>${escapeHtml(r.status||'')}</td></tr>`).join('')}</tbody></table></body></html>`;
            printWindow.document.write(html);
            printWindow.document.close();
            printWindow.print();
        } catch (err) {
            showError('Export failed. Please try again.');
        }
    });

    // Store last records for export
    window._lastRecords = [];

    const originalRender = render;
    render = (records) => {
        window._lastRecords = records;
        originalRender(records);
    };

    // Date label for staff
    const todayLabel = document.getElementById('todayLabel');
    if (todayLabel) {
        todayLabel.textContent = new Intl.DateTimeFormat('en-ZA', {
            day: 'numeric', month: 'short', year: 'numeric'
        }).format(new Date());
    }

    // Start
    if (!tableBody) {
        console.error('attendanceTableBody not found');
        return;
    }
    loadAttendance();
})();
