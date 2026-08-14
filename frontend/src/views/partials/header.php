<?php
// Shared topbar: search box, live clock, notifications, profile menu. Included by layouts/app.php on every page.
$user = $_SESSION;
$notifications = [
    ['icon' => '✅', 'color' => '#22C55E', 'text' => 'Thabo Nkosi checked in', 'time' => '2 min ago'],
    ['icon' => '⚠️', 'color' => '#F59E0B', 'text' => 'Aisha Patel checked in late', 'time' => '18 min ago'],
];
?>
<div class="cm-topbar">
    <div class="cm-searchbox">
        <span>🔍</span>
        <input placeholder="Search employees, records…" id="globalSearch">
    </div>
    <div class="cm-topbar-right">
        <div class="cm-clockchip">
            <span class="t" id="liveTime">--:--:--</span>
            <span class="d" id="liveDate">-- --- ----</span>
        </div>
        <button class="cm-iconbtn" onclick="toggleDarkMode()" title="Toggle dark mode">
            <span id="darkModeIcon">🌙</span>
        </button>
        <div style="position:relative;">
            <button class="cm-iconbtn" onclick="toggleNotifications()">
                <span>🔔</span>
                <span class="cm-badge-dot"><?= count($notifications) ?></span>
            </button>
            <div id="notificationDropdown" style="display:none; position:absolute; right:0; top:46px; width:300px; z-index:50;" class="cm-card">
                <div class="cm-card-head"><h3>Notifications</h3></div>
                <div style="max-height:280px; overflow:auto; padding:4px 16px;">
                    <?php if (empty($notifications)): ?>
                        <div class="cm-empstate" style="padding:24px 8px;">
                            <span style="font-size:26px;">📭</span>
                            <p style="font-size:12.5px; margin-top:8px;">You're all caught up</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($notifications as $n): ?>
                            <div class="cm-activity">
                                <div class="cm-activity-icon" style="background:<?= $n['color'] ?>22; color:<?= $n['color'] ?>;">
                                    <?= $n['icon'] ?>
                                </div>
                                <div>
                                    <div class="cm-activity-text"><?= $n['text'] ?></div>
                                    <div class="cm-activity-time"><?= $n['time'] ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div style="position:relative;">
            <div class="cm-profile" onclick="toggleProfile()">
                <div class="cm-avatar round">
                    <?= strtoupper(substr($user['user_name'] ?? 'U', 0, 2)) ?>
                </div>
                <div style="line-height:1.1;">
                    <div style="font-size:13px; font-weight:600;"><?= htmlspecialchars($user['user_name'] ?? 'User') ?></div>
                    <div style="font-size:11px; color:var(--muted);">
                        <?= ($user['user_role'] ?? 'staff') === 'admin' ? 'Administrator' : htmlspecialchars($user['department'] ?? 'Staff') ?>
                    </div>
                </div>
                <span>▼</span>
            </div>
            <div id="profileDropdown" style="display:none; position:absolute; right:0; top:50px; width:190px; z-index:50;" class="cm-card">
                <div style="padding:6px;">
                    <a href="/public/index.php?page=settings" class="cm-navitem">⚙️ Settings</a>
                    <a href="javascript:void(0)" class="cm-navitem" style="color:var(--red);" onclick="logoutUser(); return false;">🚪 Log out</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Live clock
(function () {
    const timeEl = document.getElementById('liveTime');
    const dateEl = document.getElementById('liveDate');
    if (!timeEl || !dateEl) return;
    function tick() {
        const now = new Date();
        timeEl.textContent = now.toLocaleTimeString('en-GB');
        dateEl.textContent = now.toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    }
    tick();
    setInterval(tick, 1000);
})();

// Dark mode toggle (persists per-browser via localStorage)
function toggleDarkMode() {
    const isDark = document.body.classList.toggle('dark-mode');
    localStorage.setItem('checkmate_dark_mode', isDark ? '1' : '0');
    const icon = document.getElementById('darkModeIcon');
    if (icon) icon.textContent = isDark ? '☀️' : '🌙';
}
(function () {
    if (localStorage.getItem('checkmate_dark_mode') === '1') {
        document.addEventListener('DOMContentLoaded', function () {
            document.body.classList.add('dark-mode');
            const icon = document.getElementById('darkModeIcon');
            if (icon) icon.textContent = '☀️';
        });
    }
})();

// Notification / profile dropdowns (mutually exclusive, close on outside click)
function toggleNotifications() {
    const dropdown = document.getElementById('notificationDropdown');
    const profile = document.getElementById('profileDropdown');
    if (profile) profile.style.display = 'none';
    if (dropdown) dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
}
function toggleProfile() {
    const dropdown = document.getElementById('profileDropdown');
    const notif = document.getElementById('notificationDropdown');
    if (notif) notif.style.display = 'none';
    if (dropdown) dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
}
document.addEventListener('click', function (event) {
    const notif = document.getElementById('notificationDropdown');
    const profile = document.getElementById('profileDropdown');
    if (notif && !event.target.closest('.cm-iconbtn') && !event.target.closest('#notificationDropdown')) {
        notif.style.display = 'none';
    }
    if (profile && !event.target.closest('.cm-profile') && !event.target.closest('#profileDropdown')) {
        profile.style.display = 'none';
    }
});
</script>
