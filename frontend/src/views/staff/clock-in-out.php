<?php
$page = 'clock-in-out';
$title = 'Clock In / Out - CheckMate';

ob_start();
?>

<div class="cm-hero">
    <div>
        <h1 class="cm-display">Clock In / Out</h1>
        <p>Scan the QR code displayed at your workplace — it's the only way to clock in or out.</p>
    </div>
    <div>
        <div class="cm-livetime" id="clockTime"><?= date('H:i:s') ?></div>
        <div class="cm-livedate"><?= date('l, j F Y') ?></div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1.1fr .9fr; gap: 20px;">
    <div class="cm-card" style="padding: 28px;">
        <div class="cm-scan-box" id="qrScanner">
            <div id="qrReader" style="position:absolute; inset:0; width:100%; height:100%; display:none; border-radius:20px; overflow:hidden;"></div>
            <div class="cm-scan-line" id="scanLine" style="display: none;"></div>
            <div class="cm-scan-corner" style="top:14px; left:14px; border-top:3px solid var(--blue); border-left:3px solid var(--blue); border-radius:6px 0 0 0;"></div>
            <div class="cm-scan-corner" style="top:14px; right:14px; border-top:3px solid var(--blue); border-right:3px solid var(--blue); border-radius:0 6px 0 0;"></div>
            <div class="cm-scan-corner" style="bottom:14px; left:14px; border-bottom:3px solid var(--blue); border-left:3px solid var(--blue); border-radius:0 0 0 6px;"></div>
            <div class="cm-scan-corner" style="bottom:14px; right:14px; border-bottom:3px solid var(--blue); border-right:3px solid var(--blue); border-radius:0 0 6px 0;"></div>
            <div id="scanPlaceholder" style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center; flex-direction:column; gap:8px; color:#94A3B8;">
                <div id="scanStatus">
                    <span style="font-size:12px; display:block; margin-top:8px;">Loading today's status…</span>
                </div>
            </div>
        </div>

        <button class="cm-btn success"
                style="width:100%; justify-content:center; padding:16px; font-size:15px; margin-top:22px;"
                onclick="handleClockAction()"
                id="clockButton" disabled>
            <span>⏳</span> Loading...
        </button>

        <div id="clockResult" style="display:none; margin-top:20px; padding:16px 18px; border-radius:14px; border:1px solid;">
        </div>
    </div>

    <div class="cm-card">
        <div class="cm-card-head"><h3>Today's Timeline</h3></div>
        <div class="cm-card-body" style="padding-top:14px;" id="timelineContainer">
            <div class="cm-empstate" style="padding:20px 0;">
                <p style="font-size:12.5px;">Loading…</p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>
<style>
#qrReader video {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
}
#qrReader__scan_region {
    width: 100% !important;
    height: 100% !important;
}
#qrReader__dashboard {
    display: none !important;
}
</style>
<script>
const API_BASE = 'http://localhost:8080';
const TOKEN_KEY = 'token';

let html5QrCode = null;
let currentAttendanceId = null;
let nextAction = null; // 'in' | 'out' | 'done'

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
        throw new Error((data && (data.message || data.error)) || 'Request failed');
    }

    return data;
}

function fmtTime(dt) {
    if (!dt) return '';
    return new Date(dt.replace(' ', 'T')).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
}

function renderTimeline(record) {
    const el = document.getElementById('timelineContainer');

    if (!record) {
        el.innerHTML = `
            <div class="cm-empstate" style="padding:20px 0;">
                <p style="font-size:12.5px;">No activity recorded yet today.<br>Scan the QR code to clock in.</p>
            </div>`;
        return;
    }

    let html = `
        <div class="cm-activity">
            <div class="cm-activity-icon" style="background:#22C55E22; color:#22C55E;">✅</div>
            <div>
                <div class="cm-activity-text">Checked in</div>
                <div class="cm-activity-time">Today, ${fmtTime(record.clock_in_at)}</div>
            </div>
        </div>`;

    if (record.clock_out_at) {
        html += `
        <div class="cm-activity">
            <div class="cm-activity-icon" style="background:#EF444422; color:#EF4444;">✕</div>
            <div>
                <div class="cm-activity-text">Checked out</div>
                <div class="cm-activity-time">Today, ${fmtTime(record.clock_out_at)}</div>
            </div>
        </div>`;
    }

    el.innerHTML = html;
}

function setScanStatus(text) {
    document.getElementById('scanStatus').innerHTML =
        `<span style="font-size:12px; display:block;">${text}</span>`;
}

function resetScanPlaceholder() {
    setScanStatus('Point your camera at the office QR code');
}

function setButtonState(action) {
    nextAction = action;
    const button = document.getElementById('clockButton');
    button.disabled = false;

    if (action === 'in') {
        button.className = 'cm-btn success';
        button.innerHTML = 'Scan QR to Clock In';
        resetScanPlaceholder();
    } else if (action === 'out') {
        button.className = 'cm-btn danger';
        button.innerHTML = 'Scan QR to Clock Out';
        resetScanPlaceholder();
    } else {
        button.className = 'cm-btn';
        button.disabled = true;
        button.innerHTML = 'Attendance complete for today';
        setScanStatus('See you tomorrow');
    }
}

async function loadStatus() {
    if (!getToken()) {
        setScanStatus('Please log in first');
        renderTimeline(null);
        return;
    }

    try {
        const res = await apiRequest(`${API_BASE}/api/attendance/mine`);
        const record = res.data;

        renderTimeline(record);

        if (!record) {
            setButtonState('in');
        } else if (record.status === 'clocked_in') {
            currentAttendanceId = record.id;
            setButtonState('out');
        } else {
            setButtonState('done');
        }
    } catch (error) {
        console.error('Load status error:', error);
        setScanStatus('Unable to load your status');
    }
}

function showResult(message, isSuccess, userName, time) {
    const result = document.getElementById('clockResult');
    result.style.display = 'block';
    result.style.background = isSuccess ? 'var(--green-light)' : 'var(--red-light)';
    result.style.borderColor = isSuccess ? 'var(--green)' : 'var(--red)';

    if (isSuccess) {
        result.innerHTML = `
            <div style="display:flex; align-items:center; gap:12px;">
                <span style="font-size:22px;">${nextAction === 'out' ? '✕' : '✅'}</span>
                <div>
                    <div style="font-weight:700; font-size:14px;">${message}</div>
                    <div style="font-size:12.5px; color:var(--muted);">${userName || ''} ${time ? '· ' + time : ''}</div>
                </div>
            </div>`;
    } else {
        result.innerHTML = `
            <div style="display:flex; align-items:center; gap:12px;">
                <span style="font-size:22px;">❌</span>
                <div>
                    <div style="font-weight:700; font-size:14px;">Error</div>
                    <div style="font-size:12.5px; color:var(--muted);">${message}</div>
                </div>
            </div>`;
    }
}

function startCameraScan() {
    if (typeof Html5Qrcode === 'undefined') {
        showResult('QR scanner library failed to load. Check your internet connection and refresh the page.', false);
        return;
    }

    const reader = document.getElementById('qrReader');
    const placeholder = document.getElementById('scanPlaceholder');
    const scanLine = document.getElementById('scanLine');

    reader.style.display = 'block';
    placeholder.style.display = 'none';
    scanLine.style.display = 'block';

    try {
        html5QrCode = new Html5Qrcode('qrReader');
    } catch (err) {
        console.error('Html5Qrcode init error:', err);
        showResult('Could not start the QR scanner: ' + err.message, false);
        reader.style.display = 'none';
        placeholder.style.display = 'flex';
        scanLine.style.display = 'none';
        return;
    }

    html5QrCode.start(
        { facingMode: 'environment' },
        { fps: 10, qrbox: 220 },
        onScanSuccess,
        () => {} // ignore per-frame scan misses
    ).catch((err) => {
        console.error('Camera start error:', err);
        stopCameraScan();
        showResult('Could not access camera: ' + (err.message || err) + '. Check browser permissions and try again.', false);
        const button = document.getElementById('clockButton');
        button.disabled = false;
        setButtonState(nextAction);
    });
}

function stopCameraScan() {
    const reader = document.getElementById('qrReader');
    const placeholder = document.getElementById('scanPlaceholder');
    const scanLine = document.getElementById('scanLine');

    if (html5QrCode) {
        html5QrCode.stop().then(() => html5QrCode.clear()).catch(() => {});
    }
    reader.style.display = 'none';
    placeholder.style.display = 'flex';
    scanLine.style.display = 'none';
}

async function onScanSuccess(decodedText) {
    stopCameraScan();
    const button = document.getElementById('clockButton');
    const action = nextAction;

    button.disabled = true;
    button.innerHTML = action === 'in' ? '⏳ Clocking in...' : '⏳ Clocking out...';

    try {
        if (action === 'in') {
            const res = await apiRequest(`${API_BASE}/api/attendance/scan`, {
                method: 'POST',
                body: JSON.stringify({ token: decodedText })
            });
            currentAttendanceId = res.attendance_id;
            showResult('Checked in successfully', true, '', new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' }));
        } else {
            await apiRequest(`${API_BASE}/api/attendance/clock-out`, {
                method: 'POST',
                body: JSON.stringify({ attendance_id: currentAttendanceId })
            });
            showResult('Checked out successfully', true, '', new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' }));
        }

        await loadStatus();
        setTimeout(() => { document.getElementById('clockResult').style.display = 'none'; }, 4000);
    } catch (error) {
        console.error('Clock action error:', error);
        showResult(error.message || 'Error connecting to server. Please try again.', false);
        setButtonState(action);
    }
}

function handleClockAction() {
    if (nextAction === 'in' || nextAction === 'out') {
        startCameraScan();
    }
}

function updateClock() {
    const now = new Date();
    document.getElementById('clockTime').textContent =
        now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}
setInterval(updateClock, 1000);

document.addEventListener('DOMContentLoaded', loadStatus);
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
?>
