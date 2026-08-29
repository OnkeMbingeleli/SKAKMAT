/**
 * Reports page (admin-reports.php): 4 Chart.js canvases + a "today at a
 * glance" metric grid, driven by GET /api/reports?type=dashboard.
 * Relies on api.js for apiCall()/getToken()/API_URL, and Chart.js (CDN).
 */
(function () {
    'use strict';

    const el = (id) => document.getElementById(id);
    let charts = {};
    let filtersPopulated = false;

    let state = {
        startDate: '',
        endDate: '',
        department: '',
        employeeId: '',
    };

    function toast(message, isError) {
        const region = document.querySelector('.toast-region');
        if (!region) { console.warn(message); return; }
        const node = document.createElement('div');
        node.className = 'toast ' + (isError ? 'error' : 'success');
        node.textContent = message;
        region.appendChild(node);
        setTimeout(() => node.remove(), 3500);
    }

    function defaultDates() {
        const end = new Date();
        const start = new Date();
        start.setDate(start.getDate() - 27);
        const fmt = (d) => d.toISOString().slice(0, 10);
        return { start: fmt(start), end: fmt(end) };
    }

    function shortDate(period) {
        const d = new Date(period);
        if (isNaN(d)) return String(period).replace(/^\d{4}\s?W\s?/, 'W');
        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' });
    }

    const palette = {
        teal: '#6B7F32',
        tealSoft: 'rgba(107, 127, 50, 0.18)',
        accent: '#849B43',
        amber: '#D97706',
        red: '#EF4444',
        blue: '#2563EB',
        grid: 'rgba(100, 116, 139, 0.12)',
    };

    function baseOptions(extra) {
        return Object.assign({
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#64748B', font: { size: 11 } } },
                y: { grid: { color: palette.grid }, ticks: { color: '#64748B', font: { size: 11 }, precision: 0 }, beginAtZero: true },
            },
        }, extra || {});
    }

    // ---------------------------------------------------------------
    // Load
    // ---------------------------------------------------------------

    async function loadReports() {
        [el('weeklyAttendanceChart'), el('monthlyAttendanceRateChart'), el('lateArrivalsChart'), el('presenceSplitChart')]
            .forEach(c => c.closest('.chart-frame').classList.add('is-loading'));

        const params = new URLSearchParams({
            type: 'dashboard',
            start_date: state.startDate,
            end_date: state.endDate,
        });
        if (state.department) params.set('department', state.department);
        if (state.employeeId) params.set('employee_id', state.employeeId);

        const res = await apiCall(`/api/reports?${params.toString()}`);

        [el('weeklyAttendanceChart'), el('monthlyAttendanceRateChart'), el('lateArrivalsChart'), el('presenceSplitChart')]
            .forEach(c => c.closest('.chart-frame').classList.remove('is-loading'));

        if (!res || res.success === false) {
            toast((res && res.error) || 'Could not load reports.', true);
            return;
        }

        const data = res.data;
        renderSummary(data.summary || {});
        populateFilterOptions(data.meta || {});
        renderCharts(data);
    }

    function renderSummary(summary) {
        el('reportAttendanceCount').textContent = summary.attendance_count ?? 0;
        el('reportCheckins').textContent = summary.total_checkins ?? 0;
        el('reportCheckouts').textContent = summary.total_checkouts ?? 0;
        el('reportLateArrivals').textContent = summary.late_arrivals ?? 0;
        el('reportAbsentees').textContent = summary.absentees ?? 0;
    }

    function populateFilterOptions(meta) {
        if (filtersPopulated) return;
        filtersPopulated = true;

        const deptSelect = el('reportDepartment');
        (meta.departments || []).forEach(dep => {
            const opt = document.createElement('option');
            opt.value = dep; opt.textContent = dep;
            deptSelect.appendChild(opt);
        });

        const empSelect = el('reportEmployee');
        (meta.employees || []).forEach(emp => {
            const opt = document.createElement('option');
            opt.value = emp.id; opt.textContent = emp.name;
            empSelect.appendChild(opt);
        });
    }

    // ---------------------------------------------------------------
    // Charts
    // ---------------------------------------------------------------

    function destroyChart(key) {
        if (charts[key]) { charts[key].destroy(); charts[key] = null; }
    }

    function renderCharts(data) {
        const dailyRows = data.weekly_rows || [];
        const weeklyRows = data.monthly_rows || [];
        const staffCount = Math.max(1, (data.meta && data.meta.staff_count) || 1);
        const summary = data.summary || {};

        // 1. Weekly (daily) attendance — bar
        destroyChart('weekly');
        charts.weekly = new Chart(el('weeklyAttendanceChart'), {
            type: 'bar',
            data: {
                labels: dailyRows.map(r => shortDate(r.start_date || r.period)),
                datasets: [{
                    label: 'Onsite',
                    data: dailyRows.map(r => Number(r.present_count) || 0),
                    backgroundColor: palette.teal,
                    borderRadius: 6,
                    maxBarThickness: 26,
                }],
            },
            options: baseOptions(),
        });

        // 2. Monthly attendance rate — line
        destroyChart('monthly');
        charts.monthly = new Chart(el('monthlyAttendanceRateChart'), {
            type: 'line',
            data: {
                labels: weeklyRows.map(r => shortDate(r.start_date || r.period)),
                datasets: [{
                    label: 'Attendance rate %',
                    data: weeklyRows.map(r => Math.min(100, Math.round(((Number(r.present_count) || 0) / staffCount) * 100))),
                    borderColor: palette.accent,
                    backgroundColor: palette.tealSoft,
                    fill: true,
                    tension: 0.35,
                    pointRadius: 4,
                    pointBackgroundColor: palette.accent,
                }],
            },
            options: baseOptions({ scales: { x: { grid: { display: false } }, y: { beginAtZero: true, max: 100, ticks: { callback: (v) => v + '%' } } } }),
        });

        // 3. Late arrivals — bar
        destroyChart('late');
        charts.late = new Chart(el('lateArrivalsChart'), {
            type: 'bar',
            data: {
                labels: dailyRows.map(r => shortDate(r.start_date || r.period)),
                datasets: [{
                    label: 'Late arrivals',
                    data: dailyRows.map(r => Number(r.late_arrivals) || 0),
                    backgroundColor: palette.amber,
                    borderRadius: 6,
                    maxBarThickness: 26,
                }],
            },
            options: baseOptions(),
        });

        // 4. Presence split today — doughnut
        destroyChart('presence');
        const present = Number(summary.total_checkins) || 0;
        const late = Number(summary.late_arrivals) || 0;
        const absent = Number(summary.absentees) || 0;
        const onTime = Math.max(0, present - late);
        charts.presence = new Chart(el('presenceSplitChart'), {
            type: 'doughnut',
            data: {
                labels: ['On time', 'Late', 'Absent'],
                datasets: [{
                    data: [onTime, late, absent],
                    backgroundColor: [palette.teal, palette.amber, palette.red],
                    borderWidth: 0,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: { legend: { position: 'bottom', labels: { color: '#64748B', boxWidth: 10, font: { size: 11 } } } },
            },
        });
    }

    // ---------------------------------------------------------------
    // Wiring
    // ---------------------------------------------------------------

    function init() {
        const dates = defaultDates();
        state.startDate = dates.start;
        state.endDate = dates.end;
        el('reportStartDate').value = state.startDate;
        el('reportEndDate').value = state.endDate;

        el('reportStartDate').addEventListener('change', (e) => { state.startDate = e.target.value; loadReports(); });
        el('reportEndDate').addEventListener('change', (e) => { state.endDate = e.target.value; loadReports(); });
        el('reportDepartment').addEventListener('change', (e) => { state.department = e.target.value; loadReports(); });
        el('reportEmployee').addEventListener('change', (e) => { state.employeeId = e.target.value; loadReports(); });

        loadReports();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
