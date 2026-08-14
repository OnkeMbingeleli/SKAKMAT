(() => {
    const page = document.body;
    const role = page.dataset.role === 'admin' ? 'admin' : 'staff';
    const tableBody = document.getElementById('attendanceTableBody');
    const form = document.getElementById('filterForm');
    const errorBox = document.getElementById('attendanceError');
    const columns = role === 'admin' ? 6 : 5;
    const apiUrl = 'http://localhost:8080/api/attendance';

    function getToken() {
        const storedToken = localStorage.getItem('token');
        if (storedToken) return storedToken;
        const tokenCookie = document.cookie.split('; ').find(cookie => cookie.startsWith('checkmate_token='));
        return tokenCookie ? decodeURIComponent(tokenCookie.split('=').slice(1).join('=')) : '';
    }

    const todayFormatter = new Intl.DateTimeFormat('en-ZA', { day: 'numeric', month: 'short', year: 'numeric' });
    document.getElementById('todayLabel')?.textContent = todayFormatter.format(new Date());

    function updateAdminClocks() {
        if (role !== 'admin') return;
        const now = new Date();
        const time = new Intl.DateTimeFormat('en-ZA', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false }).format(now);
        const date = new Intl.DateTimeFormat('en-ZA', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).format(now);
        document.getElementById('adminHeaderTime').firstChild.nodeValue = time;
        document.getElementById('adminHeaderDate').textContent = date;
        document.getElementById('adminHeroTime').firstChild.nodeValue = time;
        document.getElementById('adminHeroDate').textContent = date;
    }
    updateAdminClocks();
    if (role === 'admin') window.setInterval(updateAdminClocks, 1000);

    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[character]));
    const initials = value => String(value || '?').trim().split(/\s+/).slice(0, 2).map(part => part[0]).join('').toUpperCase();
    const normaliseStatus = value => String(value || '').toLowerCase().replace(/[\s-]/g, '_');

    function formatDate(value) {
        if (!value) return '—';
        const date = new Date(`${value}T00:00:00`);
        return Number.isNaN(date.valueOf()) ? escapeHtml(value) : new Intl.DateTimeFormat('en-ZA', { day: '2-digit', month: 'short', year: 'numeric' }).format(date);
    }

    function formatTime(value) {
        if (!value) return '<span class="time muted">Not clocked out</span>';
        const [hours, minutes] = String(value).split(':');
        if (hours === undefined || minutes === undefined) return escapeHtml(value);
        const numericHours = Number(hours);
        return `<span class="time">${numericHours % 12 || 12}:${minutes} ${numericHours >= 12 ? 'PM' : 'AM'}</span>`;
    }

    function minutesFromDuration(value) {
        if (!value || typeof value !== 'string') return 0;
        const parts = value.split(':').map(Number);
        return (parts[0] || 0) * 60 + (parts[1] || 0) + Math.round((parts[2] || 0) / 60);
    }

    function formatDuration(value) {
        const minutes = minutesFromDuration(value);
        if (!minutes) return 'In progress';
        const hours = Math.floor(minutes / 60);
        const remainder = minutes % 60;
        return remainder ? `${hours}h ${remainder}m` : `${hours}h`;
    }

    function statusBadge(value, checkOut) {
        const status = normaliseStatus(value);
        if (!checkOut && !['absent', 'late', 'present', 'on_time'].includes(status)) return '<span class="status status-active">Active</span>';
        if (status === 'late') return '<span class="status status-late">Late</span>';
        if (status === 'absent') return '<span class="status status-absent">Absent</span>';
        return '<span class="status status-present">On time</span>';
    }

    function updateSummary(records) {
        if (role === 'admin') {
            const today = new Date().toISOString().slice(0, 10);
            const todaysRecords = records.filter(record => String(record.date || '').slice(0, 10) === today);
            const onsiteEmployees = new Set(todaysRecords.filter(record => record.check_in && !record.check_out).map(record => record.employee_name || record.user_id || record.id));
            document.getElementById('adminOnsite').textContent = onsiteEmployees.size;
            document.getElementById('adminCheckedIn').textContent = todaysRecords.filter(record => record.check_in).length;
            document.getElementById('adminCheckedOut').textContent = todaysRecords.filter(record => record.check_out).length;
            document.getElementById('adminLate').textContent = todaysRecords.filter(record => normaliseStatus(record.status) === 'late').length;
            document.getElementById('recordNote').textContent = records.length ? `${records.length} attendance record${records.length === 1 ? '' : 's'} found` : 'No attendance records found';
            return;
        }
        const totalMinutes = records.reduce((sum, record) => sum + minutesFromDuration(record.total_hours), 0);
        const onTime = records.filter(record => ['on_time', 'present'].includes(normaliseStatus(record.status))).length;
        document.getElementById('totalRecords').textContent = records.length;
        document.getElementById('totalHours').textContent = totalMinutes ? `${Math.floor(totalMinutes / 60)}h ${totalMinutes % 60}m` : '0h';
        document.getElementById('onTimeRate').textContent = records.length ? `${Math.round((onTime / records.length) * 100)}%` : '—';
        document.getElementById('recordNote').textContent = records.length ? `${records.length} record${records.length === 1 ? '' : 's'} found` : 'No records found';
    }

    function render(records) {
        updateSummary(records);
        if (!records.length) {
            tableBody.innerHTML = `<tr class="empty-row"><td colspan="${columns}"><span class="empty-icon">◷</span><strong>No attendance records yet</strong><br><span>Your attendance history will appear here once a record is available.</span></td></tr>`;
            return;
        }
        tableBody.innerHTML = records.map(record => {
            const employee = escapeHtml(record.employee_name || page.dataset.userName || 'Employee');
            return `<tr>${role === 'admin' ? `<td><span class="employee"><span class="avatar">${initials(employee)}</span>${employee}</span></td>` : ''}<td>${formatDate(record.date)}</td><td>${formatTime(record.check_in)}</td><td>${formatTime(record.check_out)}</td><td class="hours">${formatDuration(record.total_hours)}</td><td>${statusBadge(record.status, record.check_out)}</td></tr>`;
        }).join('');
    }

    function showError(message) {
        errorBox.textContent = message;
        errorBox.classList.add('visible');
    }

    async function loadAttendance(params = {}) {
        errorBox.classList.remove('visible');
        tableBody.innerHTML = `<tr class="loading-row"><td colspan="${columns}"><span class="loading-dot"></span>Loading records</td></tr>`;
        const query = new URLSearchParams(Object.entries(params).filter(([, value]) => value));
        const token = getToken();
        try {
            const response = await fetch(`${apiUrl}${query.toString() ? `?${query}` : ''}`, { headers: token ? { Authorization: `Bearer ${token}` } : {} });
            const result = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(result.error || 'Could not load your attendance history.');
            if (!Array.isArray(result.data)) throw new Error('The attendance service returned an unexpected response.');
            if (result.role && result.role !== role) throw new Error('Your account permissions do not match the attendance response. Please sign in again.');
            render(result.data);
        } catch (error) {
            updateSummary([]);
            tableBody.innerHTML = `<tr class="empty-row"><td colspan="${columns}"><span class="empty-icon">!</span><strong>We couldn’t load attendance records</strong><br><span>Please check your connection and try again.</span></td></tr>`;
            showError(error.message);
        }
    }

    form?.addEventListener('submit', event => {
        event.preventDefault();
        loadAttendance(Object.fromEntries(new FormData(form).entries()));
    });
    document.getElementById('resetBtn')?.addEventListener('click', () => { form.reset(); loadAttendance(); });
    document.getElementById('adminQuickSearch')?.addEventListener('input', event => {
        const searchInput = document.getElementById('searchEmployee');
        if (searchInput) searchInput.value = event.target.value;
    });
    document.getElementById('adminQuickSearch')?.addEventListener('keydown', event => {
        if (event.key === 'Enter') {
            event.preventDefault();
            form?.requestSubmit();
        }
    });
    loadAttendance();
})();
