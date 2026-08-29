<?php
$page = 'admin-qr-code';
$title = 'QR Codes - Skakmat';
ob_start();
?>

<div class="cm-hero">
    <div>
        <h1 class="cm-display">QR Codes</h1>
        <p>Display this code at the entrance — it's the only way employees can clock in or out. A new code is generated automatically every time someone scans.</p>
    </div>
</div>

<div class="cm-card" style="max-width:420px; margin:0 auto; padding:32px; text-align:center;">
    <div style="display:flex; justify-content:center; margin-bottom:20px;">
        <div id="qrCodeDisplay" style="background:white; padding:20px; border-radius:16px; border:2px solid var(--border); width:300px; min-height:300px; display:flex; align-items:center; justify-content:center;">
            <div style="color:#94A3B8; font-size:14px;">Loading active QR code...</div>
        </div>
    </div>

    <div id="qrMeta" style="font-size:12px; color:var(--muted); margin-bottom:22px;">
        Loading active session...
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
        <button id="enableQrButton" class="cm-btn primary" type="button" style="justify-content:center; padding:14px;">Enable QR</button>
        <button id="disableQrButton" class="cm-btn" type="button" style="justify-content:center; padding:14px;">Disable QR</button>
    </div>

    <div id="qrResult" style="display:none; margin-top:16px; padding:12px; border-radius:8px; background:var(--green-light); border:1px solid var(--green);">
        ✅ QR Code generated successfully!
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.1/build/qrcode.min.js"></script>
<script src="/assets/js/config.js"></script>
<script>
const API_BASE = window.CONFIG?.API_URL || 'http://127.0.0.1:8000';
const TOKEN_KEY = 'token';
let activeSession = null;
let currentQrToken = null;
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
        throw new Error((data && (data.message || data.error)) || 'Request failed');
    }

    return data;
}

function setQrMeta(message) {
    const el = document.getElementById('qrMeta');
    if (el) el.textContent = message;
}

function renderQr(token) {
    currentQrToken = token || null;
    const qrDisplay = document.getElementById('qrCodeDisplay');
    if (!token) {
        qrDisplay.innerHTML = '<div style="color:#94A3B8; font-size:14px;">No active QR code<br>Enable QR to create one</div>';
        return;
    }

    if (window.QRCode) {
        QRCode.toDataURL(token, {
            width: 220,
            margin: 2,
            color: { dark: '#0f172a', light: '#ffffff' }
        }, function (error, url) {
            if (error) {
                console.error('QR render error:', error);
                qrDisplay.innerHTML = '<div style="color:#94A3B8; font-size:14px;">Unable to render QR code</div>';
                return;
            }
            qrDisplay.innerHTML = `<img src="${url}" width="220" height="220" alt="QR code" />`;
        });
    } else {
        qrDisplay.innerHTML = '<div style="color:#94A3B8; font-size:14px;">QR library not loaded</div>';
    }
}

function showResult(message, isSuccess = true) {
    const result = document.getElementById('qrResult');
    result.style.display = 'block';
    result.style.background = isSuccess ? 'var(--green-light)' : 'var(--red-light)';
    result.style.borderColor = isSuccess ? 'var(--green)' : 'var(--red)';
    result.textContent = message;
    clearTimeout(showResult._t);
    showResult._t = setTimeout(() => { result.style.display = 'none'; }, 3500);
}

function updateQrControls(enabled) {
    document.getElementById('enableQrButton').disabled = enabled;
    document.getElementById('disableQrButton').disabled = !enabled;
}

/**
 * Silently re-checks the active QR code without disturbing button state or
 * showing loading text — used for polling so the display auto-refreshes
 * the moment someone scans and the backend rotates the code.
 */
async function refreshActiveQr() {
    if (!activeSession) return;
    try {
        const activeQr = await apiRequest(`${API_BASE}/api/qr-codes/active`);
        const qr = activeQr && activeQr.data ? activeQr.data : null;
        if (qr && qr.token && qr.token !== currentQrToken) {
            renderQr(qr.token);
            setQrMeta(`QR is enabled for ${activeSession.date}. Code refreshed automatically.`);
        }
    } catch (error) {
        // Silent — this is a background poll, don't spam the UI on a
        // transient network hiccup.
        console.warn('QR poll error:', error);
    }
}

function startPolling() {
    stopPolling();
    pollTimer = setInterval(refreshActiveQr, 4000);
}
function stopPolling() {
    if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
}

async function loadActiveQrCode() {
    const token = getToken();

    if (!token) {
        setQrMeta('Please log in as an admin to manage QR codes.');
        renderQr(null);
        return;
    }

    try {
        const activeSessionResponse = await apiRequest(`${API_BASE}/api/qr-sessions/active`);
        activeSession = activeSessionResponse.data || null;
        updateQrControls(Boolean(activeSession));

        if (!activeSession) {
            renderQr(null);
            setQrMeta('QR is disabled. Enable it to create a session.');
            stopPolling();
            return;
        }

        const activeQr = await apiRequest(`${API_BASE}/api/qr-codes/active`);
        const qr = activeQr && activeQr.data ? activeQr.data : null;

        if (!qr || !qr.token) {
            // Session is active but has no live QR yet (edge case) — generate
            // one automatically so the admin never has to click a button.
            const generated = await apiRequest(`${API_BASE}/api/qr-codes/generate`, {
                method: 'POST',
                body: JSON.stringify({ session_id: activeSession.id })
            });
            renderQr(generated?.data?.token || null);
        } else {
            renderQr(qr.token);
        }

        setQrMeta(`QR is enabled for ${activeSession.date}. It rotates automatically after every scan.`);
        startPolling();
    } catch (error) {
        console.error('Fetch active QR error:', error);
        renderQr(null);
        setQrMeta('Unable to load active QR code.');
    }
}

async function enableQRCode() {
    const button = document.getElementById('enableQrButton');
    button.disabled = true;
    try {
        const now = new Date();
        const toTimeString = date => date.toTimeString().slice(0, 8);
        const response = await apiRequest(`${API_BASE}/api/qr-sessions/enable`, {
            method: 'POST',
            body: JSON.stringify({
                clock_in_deadline: toTimeString(new Date(now.getTime() + 30 * 60000)),
                clock_out_deadline: toTimeString(new Date(now.getTime() + 8 * 60 * 60000))
            })
        });
        activeSession = { id: response.session_id, date: new Date().toISOString().slice(0, 10) };

        // Enable always generates the first code automatically — there's
        // no separate "Generate" button anymore.
        const generated = await apiRequest(`${API_BASE}/api/qr-codes/generate`, {
            method: 'POST',
            body: JSON.stringify({ session_id: activeSession.id })
        });
        renderQr(generated?.data?.token || null);
        updateQrControls(true);
        setQrMeta(`QR is enabled for ${activeSession.date}. It rotates automatically after every scan.`);
        showResult('QR session enabled.', true);
        startPolling();
    } catch (error) {
        showResult(error.message || 'Unable to enable QR.', false);
        await loadActiveQrCode();
    } finally {
        button.disabled = false;
    }
}

async function disableQRCode() {
    if (!activeSession?.id) return;
    const button = document.getElementById('disableQrButton');
    button.disabled = true;
    try {
        await apiRequest(`${API_BASE}/api/qr-sessions/${activeSession.id}/disable`, { method: 'PATCH' });
        activeSession = null;
        stopPolling();
        renderQr(null);
        setQrMeta('QR is disabled. Enable it when you are ready to accept attendance scans.');
        updateQrControls(false);
        showResult('QR session disabled.', true);
    } catch (error) {
        showResult(error.message || 'Unable to disable QR.', false);
        button.disabled = false;
    }
}

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('enableQrButton').addEventListener('click', enableQRCode);
    document.getElementById('disableQrButton').addEventListener('click', disableQRCode);
    updateQrControls(false);
    loadActiveQrCode();
});
document.addEventListener('visibilitychange', function () {
    if (document.hidden) stopPolling();
    else if (activeSession) startPolling();
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
?>
