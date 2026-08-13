<?php
$page = 'clock-in-out';
$title = 'Clock In / Out - CheckMate';

// Get user data
$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

// Get today's attendance
$stmt = $db->prepare("SELECT * FROM attendance WHERE user_id = ? AND date = CURDATE()");
$stmt->execute([$user_id]);
$today_attendance = $stmt->fetch();

// Get user status
$stmt = $db->prepare("SELECT status FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_status = $stmt->fetch();

$is_checked_in = $user_status && $user_status['status'] === 'Onsite';
$next_action = $is_checked_in ? 'out' : 'in';

// Get today's timeline
$timeline = [
    ['icon' => '✅', 'color' => '#22C55E', 'text' => 'Checked in', 'time' => 'Today, 08:14'],
    ['icon' => '⏰', 'color' => '#64748B', 'text' => 'Lunch break', 'time' => '12:30 – 13:15'],
];

if ($today_attendance && $today_attendance['check_out']) {
    $timeline[] = [
        'icon' => '✕', 
        'color' => '#EF4444', 
        'text' => 'Checked out', 
        'time' => 'Today, ' . date('H:i', strtotime($today_attendance['check_out']))
    ];
}

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
            <div class="cm-scan-line" id="scanLine" style="display: none;"></div>
            <div class="cm-scan-corner" style="top:14px; left:14px; border-top:3px solid var(--blue); border-left:3px solid var(--blue); border-radius:6px 0 0 0;"></div>
            <div class="cm-scan-corner" style="top:14px; right:14px; border-top:3px solid var(--blue); border-right:3px solid var(--blue); border-radius:0 6px 0 0;"></div>
            <div class="cm-scan-corner" style="bottom:14px; left:14px; border-bottom:3px solid var(--blue); border-left:3px solid var(--blue); border-radius:0 0 0 6px;"></div>
            <div class="cm-scan-corner" style="bottom:14px; right:14px; border-bottom:3px solid var(--blue); border-right:3px solid var(--blue); border-radius:0 0 6px 0;"></div>
            <div style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center; flex-direction:column; gap:8px; color:#94A3B8;">
                <div id="scanStatus">
                    <span style="font-size:46px;">📱</span>
                    <span style="font-size:12px; display:block; margin-top:8px;">Point your camera at the office QR code</span>
                </div>
            </div>
        </div>

        <button class="cm-btn <?= $next_action === 'in' ? 'success' : 'danger' ?>" 
                style="width:100%; justify-content:center; padding:16px; font-size:15px; margin-top:22px;"
                onclick="handleClockAction()"
                id="clockButton">
            <?php if ($next_action === 'in'): ?>
                <span>✅</span> Scan QR to Clock In
            <?php else: ?>
                <span>✕</span> Scan QR to Clock Out
            <?php endif; ?>
        </button>

        <div id="clockResult" style="display:none; margin-top:20px; padding:16px 18px; border-radius:14px; border:1px solid;">
        </div>
    </div>

    <div class="cm-card">
        <div class="cm-card-head"><h3>Today's Timeline</h3></div>
        <div class="cm-card-body" style="padding-top:14px;">
            <?php foreach ($timeline as $item): ?>
                <div class="cm-activity">
                    <div class="cm-activity-icon" style="background:<?= $item['color'] ?>22; color:<?= $item['color'] ?>;">
                        <?= $item['icon'] ?>
                    </div>
                    <div>
                        <div class="cm-activity-text"><?= $item['text'] ?></div>
                        <div class="cm-activity-time"><?= $item['time'] ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (!$today_attendance): ?>
                <div class="cm-empstate" style="padding:20px 0;">
                    <p style="font-size:12.5px;">No activity recorded yet today.<br>Scan the QR code to clock in.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function handleClockAction() {
    const button = document.getElementById('clockButton');
    const result = document.getElementById('clockResult');
    const scanLine = document.getElementById('scanLine');
    const scanStatus = document.getElementById('scanStatus');
    
    // Show scanning animation
    button.disabled = true;
    button.innerHTML = '⏳ Scanning...';
    scanLine.style.display = 'block';
    scanStatus.innerHTML = '<span style="font-size:46px;">⏳</span><span style="font-size:12px; display:block; margin-top:8px;">Scanning...</span>';
    
    // Simulate QR scan
    setTimeout(() => {
        // Make API call
        fetch('/api/attendance.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'toggle' })
        })
        .then(response => response.json())
        .then(data => {
            scanLine.style.display = 'none';
            button.disabled = false;
            
            if (data.success) {
                const isCheckIn = data.action === 'in';
                result.style.display = 'block';
                result.style.background = isCheckIn ? 'var(--green-light)' : 'var(--red-light)';
                result.style.borderColor = isCheckIn ? 'var(--green)' : 'var(--red)';
                result.innerHTML = `
                    <div style="display:flex; align-items:center; gap:12px;">
                        <span style="font-size:22px;">${isCheckIn ? '✅' : '✕'}</span>
                        <div>
                            <div style="font-weight:700; font-size:14px;">
                                ${isCheckIn ? 'Checked in successfully' : 'Checked out successfully'}
                            </div>
                            <div style="font-size:12.5px; color:var(--muted);">
                                ${data.user_name} · ${data.time}
                            </div>
                        </div>
                    </div>
                `;
                
                // Update button
                const nextAction = data.action === 'in' ? 'out' : 'in';
                if (nextAction === 'in') {
                    button.className = 'cm-btn success';
                    button.innerHTML = '<span>✅</span> Scan QR to Clock In';
                } else {
                    button.className = 'cm-btn danger';
                    button.innerHTML = '<span>✕</span> Scan QR to Clock Out';
                }
                
                // Update scan status
                scanStatus.innerHTML = `
                    <span style="font-size:46px;">📱</span>
                    <span style="font-size:12px; display:block; margin-top:8px;">Point your camera at the office QR code</span>
                `;
                
                // Reload page after 2 seconds to update timeline
                setTimeout(() => location.reload(), 2000);
            } else {
                result.style.display = 'block';
                result.style.background = 'var(--red-light)';
                result.style.borderColor = 'var(--red)';
                result.innerHTML = `
                    <div style="display:flex; align-items:center; gap:12px;">
                        <span style="font-size:22px;">❌</span>
                        <div>
                            <div style="font-weight:700; font-size:14px;">Error</div>
                            <div style="font-size:12.5px; color:var(--muted);">${data.message}</div>
                        </div>
                    </div>
                `;
                button.disabled = false;
                scanStatus.innerHTML = `
                    <span style="font-size:46px;">📱</span>
                    <span style="font-size:12px; display:block; margin-top:8px;">Point your camera at the office QR code</span>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            scanLine.style.display = 'none';
            button.disabled = false;
            button.innerHTML = '<?= $next_action === 'in' ? '✅ Scan QR to Clock In' : '✕ Scan QR to Clock Out' ?>';
            scanStatus.innerHTML = `
                <span style="font-size:46px;">📱</span>
                <span style="font-size:12px; display:block; margin-top:8px;">Point your camera at the office QR code</span>
            `;
            alert('Error connecting to server. Please try again.');
        });
    }, 2000);
}

// Live clock
function updateClock() {
    const now = new Date();
    const timeStr = now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    document.getElementById('clockTime').textContent = timeStr;
}

setInterval(updateClock, 1000);
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
?>