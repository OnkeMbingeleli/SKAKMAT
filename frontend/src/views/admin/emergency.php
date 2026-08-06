<?php
// ============================================================
// Emergency Roll Call – Single Button + Siren (no "Mark all safe")
// ============================================================

$employees = [
    ['id' => 1, 'name' => 'TN', 'initials' => 'TN', 'department' => 'Engineering', 'status' => 'unconfirmed'],
    ['id' => 2, 'name' => 'AP', 'initials' => 'AP', 'department' => 'Sales', 'status' => 'unconfirmed'],
    ['id' => 3, 'name' => 'LB', 'initials' => 'LB', 'department' => 'HR', 'status' => 'unconfirmed'],
    ['id' => 4, 'name' => 'ZD', 'initials' => 'ZD', 'department' => 'Finance', 'status' => 'unconfirmed'],
    ['id' => 5, 'name' => 'SK', 'initials' => 'SK', 'department' => 'Operations', 'status' => 'unconfirmed']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Emergency Roll Call</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f5f7fa;
            padding: 30px 20px;
            color: #1d2a3a;
        }

        .emergency-container {
            max-width: 820px;
            margin: 0 auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
            padding: 28px 32px 32px;
            border: 1px solid #e4e7ed;
        }

        .emergency-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px 20px;
            margin-bottom: 24px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .emergency-icon {
            width: 36px;
            height: 36px;
            background: #fde7e3;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #c72a1f;
            font-size: 18px;
        }

        .header-left h1 {
            font-size: 22px;
            font-weight: 600;
            letter-spacing: -0.2px;
            color: #1d2a3a;
        }
        .header-left h1 small {
            font-weight: 400;
            color: #6b7a8d;
            font-size: 17px;
            margin-left: 4px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #f0f2f5;
            padding: 6px 14px 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            color: #1d2a3a;
            border: 1px solid #e4e7ed;
        }
        .status-badge .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            background: #b0bccd;
        }
        .status-badge .dot.active {
            background: #c72a1f;
            animation: pulse 1s infinite;
        }
        @keyframes pulse {
            0% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.8); }
            100% { opacity: 1; transform: scale(1); }
        }
        .status-badge .sub {
            font-weight: 400;
            color: #6b7a8d;
            margin-left: 2px;
        }
        .status-badge .active-text {
            color: #c72a1f;
            font-weight: 600;
        }

        .emergency-controls {
            display: flex;
            flex-wrap: wrap;
            gap: 8px 12px;
            background: #fafbfc;
            padding: 14px 16px;
            border-radius: 6px;
            border: 1px solid #e4e7ed;
            margin-bottom: 28px;
            align-items: center;
            justify-content: space-between;
        }

        .controls-left {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 16px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 500;
            border: 1px solid #d0d5dd;
            background: #fff;
            color: #1d2a3a;
            cursor: pointer;
            transition: 0.12s ease;
            font-family: inherit;
            line-height: 1.4;
        }
        .btn:hover { background: #f0f2f5; border-color: #b8c0cc; }

        .btn-danger {
            background: #c72a1f;
            border-color: #c72a1f;
            color: #fff;
        }
        .btn-danger:hover { background: #b0231a; border-color: #b0231a; }

        .btn-secondary {
            background: #6b7a8d;
            border-color: #6b7a8d;
            color: #fff;
        }
        .btn-secondary:hover { background: #5a6a7c; border-color: #5a6a7c; }

        .btn-outline-danger {
            border-color: #c72a1f;
            color: #c72a1f;
            background: transparent;
        }
        .btn-outline-danger:hover { background: #fde7e3; border-color: #c72a1f; }

        .btn-success {
            background: #21a67a;
            border-color: #21a67a;
            color: #fff;
        }
        .btn-success:hover { background: #1a8d67; border-color: #1a8d67; }

        .btn-sm { padding: 4px 12px; font-size: 12px; }

        .controls-right {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .table-wrap {
            border: 1px solid #e4e7ed;
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 24px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        thead {
            background: #f8f9fc;
            border-bottom: 1px solid #e4e7ed;
        }
        thead th {
            text-align: left;
            padding: 12px 16px;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #5b6b7e;
        }
        tbody tr {
            border-bottom: 1px solid #edf0f5;
            transition: background 0.1s ease;
        }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: #fafbfc; }
        tbody td { padding: 12px 16px; vertical-align: middle; }

        .employee-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .avatar {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            background: #eef2f7;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 13px;
            color: #1d2a3a;
            flex-shrink: 0;
        }
        .employee-name { font-weight: 600; }
        .dept-tag {
            display: inline-block;
            background: #eef2f7;
            padding: 2px 12px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 500;
            color: #3d4a5c;
        }
        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .dot-sm {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }
        .dot-sm.grey { background: #b0bccd; }
        .dot-sm.green { background: #21a67a; }
        .status-text { font-weight: 500; font-size: 13px; }
        .status-text.confirmed { color: #21a67a; }
        .status-text.unconfirmed { color: #6b7a8d; }

        .action-cell { text-align: right; }

        .btn-mark {
            padding: 5px 16px;
            font-size: 12px;
            font-weight: 500;
            border-radius: 4px;
            border: 1px solid #d0d5dd;
            background: #fff;
            color: #1d2a3a;
            cursor: pointer;
            transition: 0.1s ease;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .btn-mark:hover { background: #f0f2f5; border-color: #b8c0cc; }
        .btn-mark.marked {
            background: #21a67a;
            border-color: #21a67a;
            color: #fff;
            cursor: default;
            opacity: 0.85;
        }
        .btn-mark.marked:hover { background: #21a67a; border-color: #21a67a; opacity: 0.85; }

        .footer-summary {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px 20px;
            padding-top: 4px;
        }
        .stats {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        .stat-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: #3d4a5c;
        }
        .stat-item .num {
            font-weight: 700;
            font-size: 16px;
            color: #1d2a3a;
        }
        .stat-item .num.green { color: #21a67a; }
        .stat-item .num.amber { color: #e8a838; }

        .btn-ghost {
            background: transparent;
            border: 1px solid #d0d5dd;
            color: #1d2a3a;
            padding: 5px 14px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: 0.1s ease;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .btn-ghost:hover { background: #f0f2f5; border-color: #b8c0cc; }

        .toast {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%) translateY(80px);
            background: #1d2a3a;
            color: #fff;
            padding: 10px 24px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.22,1,0.36,1);
            pointer-events: none;
            display: flex;
            align-items: center;
            gap: 8px;
            z-index: 999;
            font-family: inherit;
        }
        .toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
        .toast i { font-size: 16px; color: #6fc9b0; }

        @media (max-width: 650px) {
            .emergency-container { padding: 20px 16px; }
            .header-left h1 { font-size: 19px; }
            .header-left h1 small { font-size: 15px; }
            .emergency-controls { flex-direction: column; align-items: stretch; gap: 10px; }
            .controls-left, .controls-right { justify-content: center; }
            .action-cell { text-align: left; }
            .footer-summary { flex-direction: column; align-items: stretch; gap: 12px; }
            .stats { justify-content: center; }
        }
        @media (max-width: 480px) {
            table { font-size: 13px; }
            thead th, tbody td { padding: 10px 10px; }
            .btn { font-size: 12px; padding: 5px 12px; }
            .btn-mark { font-size: 11px; padding: 4px 12px; }
        }
    </style>
</head>
<body>

<div class="emergency-container" id="app">

    <!-- HEADER -->
    <div class="emergency-header">
        <div class="header-left">
            <div class="emergency-icon"><i class="fas fa-triangle-exclamation"></i></div>
            <h1>Emergency <small>· Roll Call</small></h1>
        </div>
        <div class="status-badge" id="statusBadge">
            <span class="dot" id="statusDot"></span>
            <span id="statusLabel">No active emergency</span>
            <span class="sub" id="statusSub">— activate to begin</span>
        </div>
    </div>

    <!-- CONTROLS -->
    <div class="emergency-controls">
        <div class="controls-left">
            <button class="btn btn-danger" id="emergencyToggleBtn">
                <i class="fas fa-exclamation-triangle"></i> <span id="emergencyBtnText">Activate Emergency</span>
            </button>
        </div>
        <div class="controls-right">
            <button class="btn btn-outline-danger" id="resetAllBtn">
                <i class="fas fa-rotate-left"></i> Reset roll call
            </button>
        </div>
    </div>

    <!-- TABLE -->
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th style="text-align:right;">Action</th>
                </tr>
            </thead>
            <tbody id="employeeTableBody"></tbody>
        </table>
    </div>

    <!-- FOOTER (without "Mark all safe") -->
    <div class="footer-summary">
        <div class="stats">
            <div class="stat-item"><span class="num" id="totalCount">0</span> total</div>
            <div class="stat-item"><span class="num green" id="safeCount">0</span> safe</div>
            <div class="stat-item"><span class="num amber" id="unconfirmedCount">0</span> not confirmed</div>
        </div>
        <div>
            <button class="btn-ghost" id="exportReportBtn"><i class="fas fa-file-export"></i> Export report</button>
        </div>
    </div>

</div>

<!-- Toast -->
<div class="toast" id="toast"><i class="fas fa-check-circle"></i><span id="toastMsg">Done</span></div>

<script>
// ============================================================
// Data from PHP
// ============================================================
const employeesData = <?php echo json_encode($employees); ?>;

// ============================================================
// DOM refs
// ============================================================
const tbody = document.getElementById('employeeTableBody');
const totalSpan = document.getElementById('totalCount');
const safeSpan = document.getElementById('safeCount');
const unconfirmedSpan = document.getElementById('unconfirmedCount');
const toastEl = document.getElementById('toast');
const toastMsg = document.getElementById('toastMsg');
const statusDot = document.getElementById('statusDot');
const statusLabel = document.getElementById('statusLabel');
const statusSub = document.getElementById('statusSub');
const emergencyBtn = document.getElementById('emergencyToggleBtn');
const emergencyBtnText = document.getElementById('emergencyBtnText');

let toastTimer = null;
let emergencyActive = false;

// ============================================================
// Audio – siren using Web Audio API
// ============================================================
let audioCtx = null;
let oscillator = null;
let gainNode = null;
let isSirenPlaying = false;
let sirenInterval = null;

function initAudio() {
    if (!audioCtx) {
        audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    }
}

function playSiren() {
    if (isSirenPlaying) return;
    initAudio();
    if (audioCtx.state === 'suspended') {
        audioCtx.resume();
    }

    gainNode = audioCtx.createGain();
    gainNode.gain.value = 0.3;
    gainNode.connect(audioCtx.destination);

    let freq = 600;
    let direction = 1;

    function createOscillator() {
        if (oscillator) {
            oscillator.stop();
            oscillator.disconnect();
        }
        oscillator = audioCtx.createOscillator();
        oscillator.type = 'sawtooth';
        oscillator.frequency.value = freq;
        oscillator.connect(gainNode);
        oscillator.start();
    }

    createOscillator();

    sirenInterval = setInterval(() => {
        freq += direction * 30;
        if (freq > 900) direction = -1;
        if (freq < 600) direction = 1;
        if (oscillator) {
            oscillator.frequency.setTargetAtTime(freq, audioCtx.currentTime, 0.05);
        }
    }, 80);

    isSirenPlaying = true;
}

function stopSiren() {
    if (sirenInterval) {
        clearInterval(sirenInterval);
        sirenInterval = null;
    }
    if (oscillator) {
        oscillator.stop();
        oscillator.disconnect();
        oscillator = null;
    }
    if (gainNode) {
        gainNode.disconnect();
        gainNode = null;
    }
    isSirenPlaying = false;
}

// ============================================================
// Toast
// ============================================================
function showToast(message) {
    toastMsg.textContent = message;
    toastEl.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toastEl.classList.remove('show'), 2800);
}

// ============================================================
// Render table
// ============================================================
function renderTable() {
    let html = '';
    employeesData.forEach(emp => {
        const statusClass = emp.status === 'safe' ? 'confirmed' : 'unconfirmed';
        const dotClass = emp.status === 'safe' ? 'green' : 'grey';
        const btnClass = emp.status === 'safe' ? 'marked' : '';
        const disabled = emp.status === 'safe' ? 'disabled' : '';
        const btnText = emp.status === 'safe' ? 'Safe' : 'Mark as safe';
        const avatarBg = emp.color || '#eef2f7';
        html += `
            <tr data-id="${emp.id}">
                <td>
                    <div class="employee-cell">
                        <div class="avatar" style="background:${avatarBg};">${emp.initials}</div>
                        <span class="employee-name">${emp.name}</span>
                    </div>
                </td>
                <td><span class="dept-tag">${emp.department}</span></td>
                <td>
                    <div class="status-indicator">
                        <span class="dot-sm ${dotClass}"></span>
                        <span class="status-text ${statusClass}">${emp.status === 'safe' ? 'Safe' : 'Not confirmed'}</span>
                    </div>
                </td>
                <td class="action-cell">
                    <button class="btn-mark ${btnClass}" data-id="${emp.id}" ${disabled}>
                        <i class="fas fa-check-circle"></i> ${btnText}
                    </button>
                </td>
            </tr>
        `;
    });
    tbody.innerHTML = html;
    updateCounts();
    attachEvents();
}

// ============================================================
// Counters
// ============================================================
function updateCounts() {
    const total = employeesData.length;
    const safe = employeesData.filter(e => e.status === 'safe').length;
    totalSpan.textContent = total;
    safeSpan.textContent = safe;
    unconfirmedSpan.textContent = total - safe;

    if (emergencyActive && safe === total && total > 0) {
        if (!window._allSafeNotified) {
            showToast('✅ Everyone is safe! You can deactivate the emergency.');
            window._allSafeNotified = true;
        }
    } else {
        window._allSafeNotified = false;
    }
}

// ============================================================
// Actions
// ============================================================
function markSafe(id) {
    const emp = employeesData.find(e => e.id === id);
    if (!emp || emp.status === 'safe') return;
    emp.status = 'safe';
    renderTable();
    showToast(`${emp.name} marked as safe ✓`);
}

function resetAll() {
    employeesData.forEach(e => e.status = 'unconfirmed');
    renderTable();
    showToast('Roll call reset – all unconfirmed');
}

function toggleEmergency() {
    if (emergencyActive) {
        deactivateEmergency();
    } else {
        activateEmergency();
    }
}

function activateEmergency() {
    if (emergencyActive) return;
    emergencyActive = true;
    window._allSafeNotified = false;

    employeesData.forEach(e => e.status = 'unconfirmed');
    renderTable();

    statusDot.className = 'dot active';
    statusLabel.textContent = 'Emergency active';
    statusSub.textContent = '— roll call in progress';
    emergencyBtn.className = 'btn btn-secondary';
    emergencyBtnText.textContent = 'Deactivate Emergency';
    emergencyBtn.querySelector('i').className = 'fas fa-stop-circle';

    playSiren();
    showToast('🚨 Emergency activated! Roll call started.');
}

function deactivateEmergency() {
    if (!emergencyActive) return;
    emergencyActive = false;

    stopSiren();

    statusDot.className = 'dot';
    statusLabel.textContent = 'No active emergency';
    statusSub.textContent = '— activate to begin';
    emergencyBtn.className = 'btn btn-danger';
    emergencyBtnText.textContent = 'Activate Emergency';
    emergencyBtn.querySelector('i').className = 'fas fa-exclamation-triangle';

    showToast('✅ Emergency deactivated.');
}

function exportReport() {
    const safe = employeesData.filter(e => e.status === 'safe').length;
    const total = employeesData.length;
    const msg = `📋 Report: ${safe}/${total} safe · ${total - safe} unconfirmed · ${new Date().toLocaleTimeString()}`;
    showToast(msg);
}

// ============================================================
// Event binding
// ============================================================
function attachEvents() {
    document.querySelectorAll('.btn-mark').forEach(btn => {
        btn.addEventListener('click', function() {
            if (this.disabled) return;
            markSafe(parseInt(this.dataset.id));
        });
    });
}

// ============================================================
// DOM ready
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    renderTable();

    document.getElementById('emergencyToggleBtn').addEventListener('click', toggleEmergency);
    document.getElementById('resetAllBtn').addEventListener('click', resetAll);
    document.getElementById('exportReportBtn').addEventListener('click', exportReport);
});
</script>

</body>
</html>