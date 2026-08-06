<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_COOKIE['checkmate_token'])) {
    header('Location: /public/index.php?page=login');
    exit;
}

if (!empty($_COOKIE['checkmate_user'])) {
    $user = json_decode($_COOKIE['checkmate_user'], true);
    if ($user) {
        $_SESSION['user_role'] = $user['role'] ?? 'staff';
        $_SESSION['user_name'] = trim(($user['first_name'] ?? 'User') . ' ' . ($user['last_name'] ?? ''));
    }
}

$role = $_SESSION['user_role'] ?? 'staff';
$name = $_SESSION['user_name'] ?? 'User';

// Staff menu
$staffMenu = [
    ['label' => 'Dashboard',          'link' => '/public/index.php?page=dashboard'],
    ['label' => 'Clock In / Out',     'link' => '/public/index.php?page=clock-in-out'],
    ['label' => 'Attendance History', 'link' => '/public/index.php?page=attendance-history'],
    ['label' => 'Leave',              'link' => '/public/index.php?page=staff-leave'],
    ['label' => 'Settings',           'link' => '/public/index.php?page=settings'],
];

// Admin menu
$adminMenu = [
    ['label' => 'Dashboard',          'link' => '/public/index.php?page=dashboard'],
    ['label' => 'QR Codes',           'link' => '/public/index.php?page=admin-qr-code'],
    ['label' => 'Emergency',          'link' => '/public/index.php?page=admin-emergency'],
    ['label' => 'Employees',          'link' => '/public/index.php?page=admin-employees'],
    ['label' => 'Attendance History', 'link' => '/public/index.php?page=attendance-history'],
    ['label' => 'Leave Requests',     'link' => '/public/index.php?page=admin-leave-requests'],
    ['label' => 'Reports',            'link' => '/public/index.php?page=admin-reports'],
    ['label' => 'Settings',           'link' => '/public/index.php?page=settings'],
];

$menu = ($role === 'admin') ? $adminMenu : $staffMenu;
?>

<!-- Toggle button (visible on mobile) -->
<button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
    ☰
</button>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h2>CheckMate</h2>
        <span>ATTENDANCEHUB</span>
    </div>

    <nav class="sidebar-nav">
        <ul>
            <?php foreach ($menu as $item): ?>
                <?php 
                    $current = basename($_SERVER['PHP_SELF']);
                    $active = ($current === basename($item['link'])) ? 'active' : '';
                ?>
                <li class="<?= $active ?>">
                    <a href="<?= htmlspecialchars($item['link']) ?>">
                        <?= htmlspecialchars($item['label']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <p>Logged in as <strong><?= htmlspecialchars($name) ?></strong></p>
        <a href="javascript:void(0)" class="logout-btn" onclick="logoutUser(); return false;">Logout</a>
    </div>
</aside>

<!-- Overlay for mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Load external sidebar JS -->
<script src="/assets/js/utils/sidebar.js"></script>

<style>
/* ── same styles as before ── */
* { box-sizing: border-box; }

.sidebar-toggle {
    display: none;
    position: fixed;
    top: 15px;
    left: 15px;
    z-index: 1000;
    background: #1e2a3a;
    color: #fff;
    border: none;
    font-size: 1.8rem;
    padding: 6px 14px;
    border-radius: 6px;
    cursor: pointer;
}

.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 260px;
    height: 100vh;
    background: #1e2a3a;
    color: #ecf0f1;
    display: flex;
    flex-direction: column;
    padding: 20px 0;
    transition: transform 0.3s ease;
    z-index: 999;
    box-shadow: 2px 0 12px rgba(0,0,0,0.4);
    overflow-y: auto;
}

.sidebar-header {
    padding: 0 24px 20px 24px;
    border-bottom: 1px solid #2c3e50;
    margin-bottom: 16px;
}
.sidebar-header h2 {
    margin: 0;
    font-size: 1.6rem;
    font-weight: 600;
    color: #fff;
    letter-spacing: 0.5px;
}
.sidebar-header span {
    font-size: 0.65rem;
    letter-spacing: 2px;
    color: #7f8c8d;
}

.sidebar-nav ul {
    list-style: none;
    padding: 0;
    margin: 0;
}
.sidebar-nav li {
    margin: 2px 12px;
    border-radius: 8px;
    transition: background 0.15s;
}
.sidebar-nav li a {
    display: block;
    padding: 12px 20px;
    color: #b0bec5;
    text-decoration: none;
    font-size: 0.95rem;
    font-weight: 500;
    border-radius: 8px;
    transition: all 0.15s;
}
.sidebar-nav li a:hover {
    background: #2c3e50;
    color: #fff;
}
.sidebar-nav li.active a {
    background: #2c3e50;
    color: #fff;
    border-left: 4px solid #3498db;
    padding-left: 16px;
}

.sidebar-footer {
    margin-top: auto;
    padding: 20px 24px 10px 24px;
    border-top: 1px solid #2c3e50;
    font-size: 0.85rem;
}
.sidebar-footer p {
    margin: 0 0 10px 0;
    color: #b0bec5;
}
.sidebar-footer strong {
    color: #fff;
}
.logout-btn {
    display: inline-block;
    background: #c0392b;
    color: #fff;
    padding: 6px 16px;
    border-radius: 4px;
    text-decoration: none;
    font-size: 0.8rem;
    transition: background 0.15s;
}
.logout-btn:hover {
    background: #e74c3c;
}

.sidebar-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0,0,0,0.4);
    z-index: 998;
}

@media (max-width: 768px) {
    .sidebar-toggle {
        display: block;
    }
    .sidebar {
        transform: translateX(-110%);
        width: 280px;
    }
    .sidebar.open {
        transform: translateX(0);
    }
    .sidebar-overlay.show {
        display: block;
    }
}
</style>
