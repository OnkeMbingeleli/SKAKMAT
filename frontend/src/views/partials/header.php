<?php
if (session_status() === PHP_SESSION_NONE) {
    $sessionPath = session_save_path();
    if ($sessionPath && (!is_dir($sessionPath) || !is_writable($sessionPath))) {
        session_save_path(sys_get_temp_dir());
    }
    session_start();
}

$headerName = $_SESSION['user_name'] ?? 'User';
$headerRole = $_SESSION['user_role'] ?? 'staff';
$headerInitials = '';
foreach (explode(' ', trim($headerName)) as $part) {
    if ($part !== '') $headerInitials .= mb_strtoupper(mb_substr($part, 0, 1));
}
$headerInitials = mb_substr($headerInitials, 0, 2) ?: 'U';
?>
<header class="topbar">
    <label class="search-box">
        <span aria-hidden="true"></span>
        <input type="search" placeholder="Search employees, records..." data-i18n-placeholder="header.search">
    </label>
    <div class="topbar-actions">
        <div class="clock-block">
            <strong id="currentTime">--:--:--</strong>
            <small id="currentDate">-- --- ----</small>
        </div>

        <button class="icon-button" id="headerDarkModeBtn" type="button" aria-label="Toggle dark mode">
            <span class="moon-icon" aria-hidden="true"></span>
        </button>

        <button class="icon-button has-badge" type="button" aria-label="Notifications" id="headerNotifBtn">
            <span class="bell-icon" aria-hidden="true"></span>
            <span id="headerNotifBadge" hidden>0</span>
        </button>

        <div class="profile-switcher">
            <button class="profile-chip" id="headerProfileBtn" type="button" aria-haspopup="true" aria-expanded="false">
                <span class="profile-avatar"><?= htmlspecialchars($headerInitials) ?></span>
                <span class="profile-copy">
                    <strong><?= htmlspecialchars($headerName) ?></strong>
                    <small><?= htmlspecialchars(ucfirst($headerRole)) ?></small>
                </span>
            </button>
            <div class="profile-menu" id="headerProfileMenu" role="menu" hidden>
                <a href="/index.php?page=settings" role="menuitem" data-i18n="header.settings">Settings</a>
                <button type="button" id="headerLogoutBtn" role="menuitem" data-i18n="header.logout">Log out</button>
            </div>
        </div>
    </div>
</header>
