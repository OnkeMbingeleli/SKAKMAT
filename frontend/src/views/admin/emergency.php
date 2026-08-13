<?php
$page = 'admin-emergency';
$title = 'Emergency - CheckMate';
ob_start();
?>

<div class="cm-hero">
    <div>
        <h1 class="cm-display">Emergency</h1>
        <p>Start an evacuation roll call and confirm each employee's safety.</p>
    </div>
</div>

<!-- Status / control banner -->
<div class="cm-card cm-emg-banner" id="emgBanner" style="padding:20px 22px; margin-bottom:22px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;">
    <div style="display:flex; align-items:center; gap:14px;">
        <div class="cm-emg-icon" id="emgIcon">⚠️</div>
        <div>
            <div style="font-weight:700; font-size:15px;" id="emgBannerTitle">Loading…</div>
            <div style="font-size:13px; color:var(--muted);" id="emgBannerSub">Checking for an active emergency</div>
        </div>
    </div>
    <button class="cm-btn danger" id="emgActionBtn" style="display:none;">
        <span id="emgActionIcon">⚠️</span> <span id="emgActionText">Start evacuation</span>
    </button>
</div>

<!-- Roll call -->
<div class="cm-card" id="rollCallCard" style="display:none;">
    <div class="cm-card-head" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
        <h3>Employee status</h3>
        <div style="font-size:12.5px; color:var(--muted);" id="rollCallSummary"></div>
    </div>
    <div class="cm-card-body" style="padding-top:0;">
        <div style="overflow-x:auto;">
            <table class="cm-emg-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th style="text-align:right;">Action</th>
                    </tr>
                </thead>
                <tbody id="rollCallBody">
                    <!-- rows injected by JS -->
                </tbody>
            </table>
        </div>
        <div class="cm-empstate" id="rollCallEmpty" style="display:none; padding:28px 0;">
            <p style="font-size:13px;">No one is currently clocked in, so there's nothing to roll call.</p>
        </div>
    </div>
</div>

<style>
.cm-emg-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: var(--red-light);
    color: var(--red);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}
.cm-emg-icon.active {
    background: var(--red);
    color: #fff;
    animation: cm-emg-pulse 1.4s ease-in-out infinite;
}
@keyframes cm-emg-pulse {
    0%   { box-shadow: 0 0 0 0 rgba(239,68,68,.45); }
    70%  { box-shadow: 0 0 0 10px rgba(239,68,68,0); }
    100% { box-shadow: 0 0 0 0 rgba(239,68,68,0); }
}

.cm-emg-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
.cm-emg-table thead th {
    text-align: left;
    padding: 10px 12px;
    font-size: 11.5px;
    text-transform: uppercase;
    letter-spacing: .3px;
    color: var(--muted);
    border-bottom: 1px solid var(--border);
}
.cm-emg-table tbody td {
    padding: 12px;
    border-bottom: 1px solid var(--border);
    vertical-align: middle;
}
.cm-emg-table tbody tr:last-child td { border-bottom: none; }

.cm-emg-emp { display: flex; align-items: center; gap: 10px; }
.cm-emg-avatar {
    width: 32px;
    height: 32px;
    border-radius: 10px;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 12px;
    flex-shrink: 0;
}
.cm-emg-name { font-weight: 600; }

.cm-emg-status { display: inline-flex; align-items: center; gap: 6px; font-weight: 600; font-size: 12.5px; }
.cm-emg-dot { width: 7px; height: 7px; border-radius: 50%; display: inline-block; }
.cm-emg-dot.grey { background: #B0BCCD; }
.cm-emg-dot.green { background: var(--green); }
.cm-emg-status.unconfirmed { color: var(--muted); }
.cm-emg-status.confirmed { color: var(--green); }

.cm-emg-mark {
    border: 1px solid var(--green);
    background: var(--green-light);
    color: var(--green);
    font-weight: 700;
    font-size: 12px;
    padding: 6px 14px;
    border-radius: 10px;
    cursor: pointer;
    font-family: inherit;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: background .15s;
}
.cm-emg-mark:hover:not(:disabled) { background: var(--green); color: #fff; }
.cm-emg-mark:disabled {
    background: var(--green);
    color: #fff;
    opacity: .9;
    cursor: default;
}
</style>

<script>
const API_BASE = 'http://localhost:8080';
const TOKEN_KEY = 'token';
const AVATAR_COLORS = ['#4A6CF7', '#0D9488', '#DB2777', '#7C3AED', '#EA580C', '#16A34A'];

let currentEmergency = null; // { emergency, summary, roll_call } or null
let pollTimer = null;

function getToken() {
    return localStorage.getItem(TOKEN_KEY) || null;
}

function buildHeaders(extra = {}) {
    const token = getToken();
    return {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...extra,
        ...(token ? { Authorization: `Bearer ${token}` } : {})
    };
}

async function apiRequest(url, options = {}) {
    const response = await fetch(url, {
        ...options,
        headers: buildHeaders(options.headers || {}),
    });

    const text = await response.text();
    let data = {};

    try {
        data = text ? JSON.parse(text) : {};
    } catch (error) {
        throw new Error('Invalid response from server');
    }

    if (!response.ok) {
        throw new Error((data && data.message) || 'Request failed');
    }

    return data;
}

function initials(first, last) {
    const a = (first || '').trim().charAt(0);
    const b = (last || '').trim().charAt(0);
    return (a + b).toUpperCase() || '?';
}

function avatarColor(userId) {
    return AVATAR_COLORS[userId % AVATAR_COLORS.length];
}

// ── Rendering ──────────────────────────────────────────────────────

function renderBanner() {
    const icon = document.getElementById('emgIcon');
    const title = document.getElementById('emgBannerTitle');
    const sub = document.getElementById('emgBannerSub');
    const actionBtn = document.getElementById('emgActionBtn');
    const actionIcon = document.getElementById('emgActionIcon');
    const actionText = document.getElementById('emgActionText');

    actionBtn.style.display = 'inline-flex';
    actionBtn.disabled = false;

    if (currentEmergency) {
        icon.textContent = '🚨';
        icon.classList.add('active');
        title.textContent = 'Emergency active';
        const s = currentEmergency.summary;
        sub.textContent = `${s.confirmed} of ${s.total} confirmed safe`;

        actionBtn.className = 'cm-btn danger';
        actionIcon.textContent = '🛑';
        actionText.textContent = 'End evacuation';
    } else {
        icon.textContent = '⚠️';
        icon.classList.remove('active');
        title.textContent = 'No active emergency';
        sub.textContent = 'Start a drill or a real evacuation to begin tracking.';

        actionBtn.className = 'cm-btn danger';
        actionIcon.textContent = '⚠️';
        actionText.textContent = 'Start evacuation';
    }
}

function renderRollCall() {
    const card = document.getElementById('rollCallCard');
    const body = document.getElementById('rollCallBody');
    const summaryEl = document.getElementById('rollCallSummary');
    const emptyEl = document.getElementById('rollCallEmpty');

    if (!currentEmergency) {
        card.style.display = 'none';
        return;
    }

    card.style.display = 'block';

    const rollCall = currentEmergency.roll_call || [];
    const s = currentEmergency.summary;
    summaryEl.textContent = `${s.confirmed} safe · ${s.remaining} not confirmed · ${s.total} total`;

    if (rollCall.length === 0) {
        body.innerHTML = '';
        emptyEl.style.display = 'block';
        return;
    }
    emptyEl.style.display = 'none';

    body.innerHTML = rollCall.map(emp => {
        const isSafe = emp.status === 'marked safe';
        const name = `${emp.first_name || ''} ${emp.last_name || ''}`.trim();
        const dept = emp.department || '—';
        const dot = isSafe ? 'green' : 'grey';
        const statusClass = isSafe ? 'confirmed' : 'unconfirmed';
        const statusLabel = isSafe ? 'Safe' : 'Not confirmed';
        const btnLabel = isSafe ? 'Safe' : 'Mark as safe';
        const btnIcon = isSafe ? '✅' : '☑️';
        const disabled = isSafe ? 'disabled' : '';

        return `
            <tr>
                <td>
                    <div class="cm-emg-emp">
                        <div class="cm-emg-avatar" style="background:${avatarColor(emp.user_id)};">${initials(emp.first_name, emp.last_name)}</div>
                        <span class="cm-emg-name">${name}</span>
                    </div>
                </td>
                <td>${dept}</td>
                <td>
                    <span class="cm-emg-status ${statusClass}">
                        <span class="cm-emg-dot ${dot}"></span>${statusLabel}
                    </span>
                </td>
                <td style="text-align:right;">
                    <button class="cm-emg-mark" data-log-id="${emp.emergency_log_id}" ${disabled}>
                        ${btnIcon} ${btnLabel}
                    </button>
                </td>
            </tr>
        `;
    }).join('');

    body.querySelectorAll('.cm-emg-mark').forEach(btn => {
        btn.addEventListener('click', function () {
            if (this.disabled) return;
            markSafe(parseInt(this.dataset.logId, 10));
        });
    });
}

function render() {
    renderBanner();
    renderRollCall();
}

// ── API actions ────────────────────────────────────────────────────

async function loadActive() {
    try {
        const res = await apiRequest(`${API_BASE}/api/emergency/active`);
        currentEmergency = res.data || null;
        render();
    } catch (error) {
        console.error('Load active emergency error:', error);
        document.getElementById('emgBannerTitle').textContent = 'Unable to load emergency status';
        document.getElementById('emgBannerSub').textContent = error.message || '';
    }
}

async function startEvacuation() {
    const btn = document.getElementById('emgActionBtn');
    btn.disabled = true;
    try {
        const res = await apiRequest(`${API_BASE}/api/emergency/start`, { method: 'POST' });
        currentEmergency = {
            emergency: { id: res.emergency_id },
            summary: res.summary,
            roll_call: res.roll_call
        };
        render();
        startPolling();
    } catch (error) {
        console.error('Start evacuation error:', error);
        alert(error.message || 'Could not start the emergency.');
    } finally {
        btn.disabled = false;
    }
}

async function endEvacuation() {
    if (!currentEmergency) return;
    if (!confirm('End the current evacuation roll call?')) return;

    const btn = document.getElementById('emgActionBtn');
    btn.disabled = true;
    try {
        await apiRequest(`${API_BASE}/api/emergency/end`, {
            method: 'POST',
            body: JSON.stringify({ emergency_id: currentEmergency.emergency.id })
        });
        currentEmergency = null;
        stopPolling();
        render();
    } catch (error) {
        console.error('End evacuation error:', error);
        alert(error.message || 'Could not end the emergency.');
    } finally {
        btn.disabled = false;
    }
}

async function markSafe(emergencyLogId) {
    try {
        const res = await apiRequest(`${API_BASE}/api/emergency/mark-safe`, {
            method: 'POST',
            body: JSON.stringify({ emergency_log_id: emergencyLogId })
        });
        currentEmergency.summary = res.summary;
        currentEmergency.roll_call = res.roll_call;
        render();
    } catch (error) {
        console.error('Mark safe error:', error);
        alert(error.message || 'Could not mark this employee safe.');
    }
}

function toggleEmergency() {
    if (currentEmergency) {
        endEvacuation();
    } else {
        startEvacuation();
    }
}

// ── Polling (so a second admin's changes show up live while active) ──

function startPolling() {
    stopPolling();
    pollTimer = setInterval(loadActive, 5000);
}
function stopPolling() {
    if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
}

document.addEventListener('DOMContentLoaded', async function () {
    document.getElementById('emgActionBtn').addEventListener('click', toggleEmergency);
    await loadActive();
    if (currentEmergency) startPolling();
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
?>