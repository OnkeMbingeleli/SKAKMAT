<?php
$page = 'qr-code';
$title = 'QR Codes - CheckMate';
ob_start();
?>

<div class="cm-hero">
    <div>
        <h1 class="cm-display">QR Codes</h1>
        <p>Display this code at the entrance — it's the only way employees can clock in or out.</p>
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

    <button id="generateQrButton" class="cm-btn primary" style="width:100%; justify-content:center; padding:14px;">
        <span>🔄</span> Generate QR Code
    </button>

    <div id="qrResult" style="display:none; margin-top:16px; padding:12px; border-radius:8px; background:var(--green-light); border:1px solid var(--green);">
        ✅ QR Code generated successfully!
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.1/build/qrcode.min.js"></script>
<script>
const API_BASE = 'http://localhost:8080';
const TOKEN_KEY = 'token';
let countdownInterval = null;

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

function stopCountdown() {
    if (countdownInterval) {
        clearInterval(countdownInterval);
        countdownInterval = null;
    }
}

function setQrMeta(message) {
    const el = document.getElementById('qrMeta');
    if (el) {
        el.textContent = message;
    }
}

function renderQr(token) {
    const qrDisplay = document.getElementById('qrCodeDisplay');
    if (!token) {
        qrDisplay.innerHTML = '<div style="color:#94A3B8; font-size:14px;">No active QR code<br>Generate one below</div>';
        return;
    }

    if (window.QRCode) {
        QRCode.toDataURL(token, {
            width: 220,
            margin: 2,
            color: {
                dark: '#0f172a',
                light: '#ffffff'
            }
        }, function(error, url) {
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
}

async function loadActiveQrCode() {
    const token = getToken();

    if (!token) {
        setQrMeta('Please log in as an admin to manage QR codes.');
        renderQr(null);
        return;
    }

    try {
        const activeQr = await apiRequest(`${API_BASE}/api/qr-codes/active`);
        const qr = activeQr && activeQr.data ? activeQr.data : null;

        if (!qr || !qr.token) {
            renderQr(null);
            setQrMeta('No active QR code. Generate one below.');
            return;
        }

        renderQr(qr.token);
        setQrMeta('Active QR session is running.');
    } catch (error) {
        console.error('Fetch active QR error:', error);
        renderQr(null);
        setQrMeta('Unable to load active QR code.');
    }
}

async function generateQRCode() {
    const btn = document.getElementById('generateQrButton');
    const token = getToken();

    if (!token) {
        showResult('Please log in as an admin first.', false);
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span>⏳</span> Generating...';
    showResult('Generating QR code...', true);

    try {
        const now = new Date();

        // Backend columns are TIME, not full ISO datetimes — send HH:MM:SS.
        const toTimeString = (d) => d.toTimeString().slice(0, 8);

        const clockInDeadline = toTimeString(new Date(now.getTime() + 30 * 60000));
        const clockOutDeadline = toTimeString(new Date(now.getTime() + 8 * 60 * 60000));

        let sessionResponse;
        try {
            sessionResponse = await apiRequest(`${API_BASE}/api/qr-sessions/enable`, {
                method: 'POST',
                body: JSON.stringify({
                    clock_in_deadline: clockInDeadline,
                    clock_out_deadline: clockOutDeadline
                })
            });
        } catch (sessionError) {
            if (sessionError.message && sessionError.message.toLowerCase().includes('already exists')) {
                const activeSession = await apiRequest(`${API_BASE}/api/qr-sessions/active`);
                sessionResponse = { session_id: activeSession && activeSession.data ? activeSession.data.id : null };
            } else {
                throw sessionError;
            }
        }

        const sessionId = sessionResponse && (sessionResponse.session_id || (sessionResponse.data && sessionResponse.data.session_id));

        if (!sessionId) {
            throw new Error('No active session was returned by the backend.');
        }

        const qrResponse = await apiRequest(`${API_BASE}/api/qr-codes/generate`, {
            method: 'POST',
            body: JSON.stringify({ session_id: sessionId })
        });

        const qr = qrResponse && qrResponse.data ? qrResponse.data : null;
        if (!qr || !qr.token) {
            throw new Error('The backend did not return a QR token.');
        }

        renderQr(qr.token);
        setQrMeta('New QR code generated successfully.');
        showResult('✅ QR Code generated successfully!', true);
    } catch (error) {
        console.error('Generate QR error:', error);
        renderQr(null);
        setQrMeta('Unable to generate QR code.');
        showResult('❌ ' + (error.message || 'Error generating QR code. Please try again.'), false);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<span>🔄</span> Generate QR Code';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    stopCountdown();
    document.getElementById('generateQrButton').addEventListener('click', generateQRCode);
    loadActiveQrCode();
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
?>