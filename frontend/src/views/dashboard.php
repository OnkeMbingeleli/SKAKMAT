<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$page = 'dashboard';
$title = 'Dashboard - CheckMate';
$role = $_SESSION['user_role'] ?? 'admin';
$name = $_SESSION['user_name'] ?? 'Admin User';
$firstName = explode(' ', trim($name))[0] ?: 'User';

$hour = (int)date('H');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

$baseUrl = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if ($baseUrl === '/' || $baseUrl === '.') {
    $baseUrl = '';
}

ob_start();
?>
<style>
    .dashboard-heading { align-items: center; }
    .quick-actions { display: flex; gap: 10px; flex-wrap: wrap; }
    .quick-actions a.primary-button, .quick-actions a.filter-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 44px;
        padding: 0 20px;
        font-size: 14px;
        line-height: 1;
        box-sizing: border-box;
        text-decoration: none;
        white-space: nowrap;
    }
    .stats-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; margin-bottom: 20px; }
    .stat-card { background: var(--panel); border: 1px solid var(--line); border-radius: 16px; box-shadow: var(--shadow); padding: 20px; min-height: 148px; }
    .stat-icon { color: var(--teal); font-size: 18px; margin-bottom: 14px; }
    .stat-value { color: var(--text); font-size: 30px; font-weight: 800; line-height: 1; }
    .stat-label { color: var(--muted); margin-top: 10px; font-weight: 700; }
    .stat-card small { color: var(--teal); display: block; font-size: 12px; font-weight: 700; margin-top: 12px; }
    .live-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--green, #22c55e); display: inline-block; }
    .live-dot.offline { background: #cbd5e1; }
    .two-col { display: grid; grid-template-columns: minmax(0, 1.65fr) minmax(260px, .75fr); gap: 20px; margin-top: 20px; }
    .quick-stats-list, .activity-list { padding: 8px 24px 18px; }
    .quick-stat, .activity-item { display: flex; justify-content: space-between; gap: 12px; padding: 15px 0; border-bottom: 1px solid var(--line); }
    .activity-item { display: block; }
    .activity-item small { color: var(--muted); display: block; margin-top: 5px; }
    .status-hero { display: flex; align-items: center; gap: 18px; padding: 22px 24px; }
    .status-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: var(--soft-input, #F8FAFC); display: flex; align-items: center; justify-content: center; font-size: 22px; color: var(--teal); flex-shrink: 0; }
    .status-hero-icon.in { background: #E9F9EF; color: #16a34a; }
    .status-hero-icon.out { background: #FEF2F2; color: #dc2626; }
    .status-hero h3 { margin: 0; font-size: 16px; }
    .status-hero p { margin: 4px 0 0; color: var(--muted); font-size: 13px; }
    @media (max-width: 980px) { .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .two-col { grid-template-columns: 1fr; } }
    @media (max-width: 560px) { .stats-grid { grid-template-columns: 1fr; } .dashboard-heading { align-items: flex-start; } }
</style>

<div class="page-heading dashboard-heading">
    <div>
        <h1><?= htmlspecialchars($greeting) ?>, <?= htmlspecialchars($firstName) ?></h1>
        <p><?= $role === 'admin' ? 'Here is what is happening onsite right now.' : 'Ready to check in for the day?' ?></p>
    </div>
    <div class="quick-actions">
        <?php if ($role === 'admin'): ?>
            <a class="primary-button" href="<?= htmlspecialchars($baseUrl) ?>/index.php?page=admin-employees">Manage Employees</a>
            <a class="filter-button" href="<?= htmlspecialchars($baseUrl) ?>/index.php?page=admin-reports">View Reports</a>
        <?php else: ?>
            <a class="primary-button" href="<?= htmlspecialchars($baseUrl) ?>/index.php?page=clock-in-out">Clock In / Out</a>
            <a class="filter-button" href="<?= htmlspecialchars($baseUrl) ?>/index.php?page=staff-leave">Request Leave</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($role === 'admin'): ?>

    <div class="stats-grid">
        <div class="stat-card"><div class="stat-icon"><i class="fas fa-users"></i></div><div class="stat-value" id="dashboardOnsite">—</div><div class="stat-label">Employees Onsite</div><small>Right now</small></div>
        <div class="stat-card"><div class="stat-icon"><i class="fas fa-check-circle"></i></div><div class="stat-value" id="dashboardCheckedIn">—</div><div class="stat-label">Checked In Today</div><small id="dashboardCheckedInMeta">—</small></div>
        <div class="stat-card"><div class="stat-icon"><i class="fas fa-sign-out-alt"></i></div><div class="stat-value" id="dashboardCheckedOut">—</div><div class="stat-label">Checked Out</div><small>End of shift</small></div>
        <div class="stat-card"><div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div><div class="stat-value" id="dashboardLate">—</div><div class="stat-label">Late Employees</div><small>Today</small></div>
    </div>

    <div class="panel live-attendance">
        <div class="panel-header"><h2>Live Attendance</h2><span class="live-dot"></span></div>
        <div class="table-wrap"><table>
            <thead><tr><th>Employee</th><th>Department</th><th>Check-in</th><th>Status</th><th>Last Activity</th></tr></thead>
            <tbody id="liveAttendanceBody"><tr><td colspan="5" class="table-state">Loading attendance data...</td></tr></tbody>
        </table></div>
    </div>

    <div class="two-col">
        <div class="panel recent-activity"><div class="panel-header"><h2>Recent Activity</h2></div><div id="recentActivity" class="activity-list"><div class="table-state">Loading activity...</div></div></div>
        <div class="panel quick-stats"><div class="panel-header"><h2>Quick Stats</h2></div><div class="quick-stats-list" id="dashboardQuickStats"><div class="table-state">Loading…</div></div></div>
    </div>

<?php else: ?>

    <div class="panel status-hero">
        <div class="status-hero-icon" id="statusHeroIcon"><i class="fas fa-circle-notch fa-spin"></i></div>
        <div>
            <h3 id="statusHeroTitle">Checking today's status…</h3>
            <p id="statusHeroSubtitle">One moment.</p>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><div class="stat-icon"><i class="fas fa-calendar-week"></i></div><div class="stat-value" id="dashboardWeekHours">—</div><div class="stat-label">Hours This Week</div><small id="dashboardDaysLogged">—</small></div>
        <div class="stat-card"><div class="stat-icon"><i class="fas fa-check-circle"></i></div><div class="stat-value" id="dashboardOnTimeRate">—</div><div class="stat-label">On-time Rate</div><small>Last 30 days</small></div>
        <div class="stat-card"><div class="stat-icon"><i class="fas fa-umbrella-beach"></i></div><div class="stat-value" id="dashboardAnnualLeave">—</div><div class="stat-label">Annual Leave Left</div><small>Days remaining</small></div>
        <div class="stat-card"><div class="stat-icon"><i class="fas fa-notes-medical"></i></div><div class="stat-value" id="dashboardSickLeave">—</div><div class="stat-label">Sick Leave Left</div><small>Days remaining</small></div>
    </div>

    <div class="two-col">
        <div class="panel recent-activity">
            <div class="panel-header"><h2>Recent Attendance</h2></div>
            <div class="table-wrap"><table>
                <thead><tr><th>Date</th><th>Check-in</th><th>Check-out</th><th>Hours</th><th>Status</th></tr></thead>
                <tbody id="myAttendanceBody"><tr><td colspan="5" class="table-state">Loading…</td></tr></tbody>
            </table></div>
        </div>
        <div class="panel quick-stats"><div class="panel-header"><h2>Leave Balance</h2></div><div class="quick-stats-list" id="leaveBalanceList"><div class="table-state">Loading…</div></div></div>
    </div>

<?php endif; ?>

<div class="toast-region"></div>
<script src="/assets/js/config.js"></script>
<script src="/assets/js/api.js"></script>
<script>
document.addEventListener('DOMContentLoaded', async () => {
    const role = <?= json_encode($role) ?>;
    role === 'admin' ? loadAdminDashboard() : loadStaffDashboard();

    async function loadAdminDashboard() {
        const body = document.getElementById('liveAttendanceBody');
        const activity = document.getElementById('recentActivity');
        try {
            const res = await apiCall('/api/attendance?action=history', { method: 'GET' });
            if (!res.success && res.error) throw new Error(res.error);
            // The attendance-history endpoint uses lowercase field names;
            // normalise them once so this dashboard can consistently render
            // its employee, department and time columns.
            const records = (res.data || []).map(normalizeAttendanceRecord);
            const today = new Date().toISOString().slice(0, 10);
            const todays = records.filter(r => String(r.DATE || '').slice(0, 10) === today);
            const onsite = todays.filter(r => r['CHECK IN'] && !r['CHECK OUT']);

            document.getElementById('dashboardOnsite').textContent = onsite.length;
            document.getElementById('dashboardCheckedIn').textContent = todays.filter(r => r['CHECK IN']).length;
            document.getElementById('dashboardCheckedInMeta').textContent = todays.length ? `${todays.filter(r => r['CHECK IN']).length} of ${todays.length} today` : 'No records yet';
            document.getElementById('dashboardCheckedOut').textContent = todays.filter(r => r['CHECK OUT']).length;
            document.getElementById('dashboardLate').textContent = todays.filter(r => String(r.STATUS || '').toLowerCase() === 'late').length;

            body.innerHTML = onsite.length
                ? onsite.slice(0, 8).map(r => `<tr><td>${escapeHtml(r.NAME || 'Unknown')}</td><td>${escapeHtml(r.DEPARTMENT || '—')}</td><td>${escapeHtml(r['CHECK IN'] || '—')}</td><td><span class="status-badge status-approved"><span></span>${escapeHtml(r.STATUS || 'Onsite')}</span></td><td>Just now</td></tr>`).join('')
                : '<tr><td colspan="5" class="table-state">No employees currently clocked in</td></tr>';

            activity.innerHTML = records.slice(0, 6).map(r => `<div class="activity-item"><strong>${escapeHtml(r.NAME || 'Employee')} ${r['CHECK OUT'] ? 'checked out' : 'checked in'}</strong><small>${escapeHtml(r.DEPARTMENT || '')} · ${escapeHtml(r['CHECK OUT'] || r['CHECK IN'] || '')}</small></div>`).join('') || '<div class="table-state">No recent activity</div>';

            const total = records.length || 1;
            const lateCount = records.filter(r => String(r.STATUS || '').toLowerCase() === 'late').length;
            const onTimeRate = Math.round(((total - lateCount) / total) * 100);
            document.getElementById('dashboardQuickStats').innerHTML = `
                <div class="quick-stat"><span>Onsite right now</span><strong>${onsite.length}</strong></div>
                <div class="quick-stat"><span>Punctuality rate</span><strong>${onTimeRate}%</strong></div>
                <div class="quick-stat"><span>Total records</span><strong>${records.length}</strong></div>
            `;
        } catch (error) {
            const message = error?.message || 'Unable to load attendance data';
            const authMessage = /authentication|token|unauthorized/i.test(message)
                ? 'Your session has expired. Please sign in again.'
                : message;
            body.innerHTML = `<tr><td colspan="5" class="table-state">${escapeHtml(authMessage)}</td></tr>`;
            activity.innerHTML = `<div class="table-state">${escapeHtml(authMessage)}</div>`;
            document.getElementById('dashboardQuickStats').innerHTML = `<div class="table-state">${escapeHtml(authMessage)}</div>`;
        }
    }

    async function loadStaffDashboard() {
        const attendanceBody = document.getElementById('myAttendanceBody');
        try {
            const meRes = await apiCall('/api/attendance?action=history', { method: 'GET' });
            const records = ((meRes && meRes.data) || []).map(normalizeAttendanceRecord);
            renderStatusHero(records[0] || null);
            renderWeekStats(records);
            attendanceBody.innerHTML = records.length
                ? records.slice(0, 8).map(r => `
                    <tr>
                        <td>${escapeHtml((r.DATE || '').slice(0, 10))}</td>
                        <td>${escapeHtml(formatTime(r['CHECK IN']))}</td>
                        <td>${escapeHtml(formatTime(r['CHECK OUT']))}</td>
                        <td>${escapeHtml(r['HOURS WORKED'] || '—')}</td>
                        <td><span class="status-badge ${String(r.STATUS).toLowerCase() === 'late' ? 'status-rejected' : 'status-approved'}"><span></span>${escapeHtml(r.STATUS || '—')}</span></td>
                    </tr>`).join('')
                : '<tr><td colspan="5" class="table-state">No attendance records yet</td></tr>';
        } catch (error) {
            attendanceBody.innerHTML = '<tr><td colspan="5" class="table-state">Unable to load your attendance</td></tr>';
            document.getElementById('statusHeroTitle').textContent = 'Unable to load status';
            document.getElementById('statusHeroSubtitle').textContent = '';
        }

        try {
            const balRes = await apiCall('/api/leave-balance', { method: 'GET' });
            const balance = (balRes && balRes.data) || null;
            const list = document.getElementById('leaveBalanceList');
            if (!balance) { list.innerHTML = '<div class="table-state">Unavailable</div>'; return; }

            const labels = { annual: 'Annual', sick: 'Sick', other: 'Other' };
            list.innerHTML = balance.map(s => `
                <div class="quick-stat"><span>${s.leave_type_label || labels[s.leave_type] || s.leave_type}</span><strong style="${Number(s.days_remaining) < 0 ? 'color:#dc2626;' : ''}">${s.unlimited ? 'Unlimited' : `${s.days_remaining} / ${s.allocated_days} left`}</strong></div>
            `).join('');

            const annual = balance.find(s => s.leave_type === 'annual');
            const sick = balance.find(s => s.leave_type === 'sick');
            document.getElementById('dashboardAnnualLeave').textContent = annual ? annual.days_remaining : '—';
            document.getElementById('dashboardSickLeave').textContent = sick ? sick.days_remaining : '—';
        } catch (error) {
            document.getElementById('leaveBalanceList').innerHTML = '<div class="table-state">Unavailable</div>';
        }
    }

    function renderStatusHero(latest) {
        const icon = document.getElementById('statusHeroIcon');
        const title = document.getElementById('statusHeroTitle');
        const subtitle = document.getElementById('statusHeroSubtitle');
        const today = new Date().toISOString().slice(0, 10);
        const isToday = latest && String(latest.DATE || '').slice(0, 10) === today;

        if (isToday && latest['CHECK IN'] && !latest['CHECK OUT']) {
            icon.className = 'status-hero-icon in';
            icon.innerHTML = '<i class="fas fa-check"></i>';
            title.textContent = `Clocked in at ${formatTime(latest['CHECK IN'])}`;
            subtitle.textContent = String(latest.STATUS).toLowerCase() === 'late' ? "You're marked late today." : "You're on the clock. Don't forget to check out.";
        } else if (isToday && latest['CHECK OUT']) {
            icon.className = 'status-hero-icon out';
            icon.innerHTML = '<i class="fas fa-flag-checkered"></i>';
            title.textContent = `Checked out at ${formatTime(latest['CHECK OUT'])}`;
            subtitle.textContent = "You're done for the day.";
        } else {
            icon.className = 'status-hero-icon';
            icon.innerHTML = '<i class="fas fa-qrcode"></i>';
            title.textContent = "You haven't clocked in today";
            subtitle.textContent = 'Scan the office QR code to check in.';
        }
    }

    function renderWeekStats(records) {
        const now = new Date();
        const weekAgo = new Date(now); weekAgo.setDate(now.getDate() - 6);
        let totalMinutes = 0, daysLogged = 0, onTime = 0, counted = 0;

        records.forEach(r => {
            const d = new Date(r.DATE);
            if (!isNaN(d) && d >= weekAgo && r['HOURS WORKED']) {
                const [h, m] = String(r['HOURS WORKED']).split(':').map(Number);
                if (!isNaN(h)) { totalMinutes += (h * 60) + (m || 0); daysLogged++; }
            }
            if (r.STATUS && r.STATUS !== 'NOT CLOCKED IN') {
                counted++;
                if (String(r.STATUS).toLowerCase() === 'on time') onTime++;
            }
        });

        document.getElementById('dashboardWeekHours').textContent = (totalMinutes / 60).toFixed(1);
        document.getElementById('dashboardDaysLogged').textContent = `${daysLogged} days logged`;
        document.getElementById('dashboardOnTimeRate').textContent = counted ? Math.round((onTime / counted) * 100) + '%' : '—';
    }

    function formatTime(value) {
        if (!value) return '—';
        const d = new Date(value.replace(' ', 'T'));
        return isNaN(d) ? value : d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    function normalizeAttendanceRecord(record) {
        const rawStatus = record.STATUS ?? record.status ?? '';
        const status = String(rawStatus)
            .replace(/_/g, ' ')
            .replace(/\b\w/g, letter => letter.toUpperCase());

        return {
            ...record,
            NAME: record.NAME ?? record.employee_name ?? '',
            DEPARTMENT: record.DEPARTMENT ?? record.department ?? '',
            DATE: record.DATE ?? record.date ?? '',
            'CHECK IN': record['CHECK IN'] ?? record.check_in ?? '',
            'CHECK OUT': record['CHECK OUT'] ?? record.check_out ?? '',
            'HOURS WORKED': record['HOURS WORKED'] ?? record.total_hours ?? '',
            STATUS: status,
        };
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c]));
    }
});
</script>
<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/app.php';
?>
