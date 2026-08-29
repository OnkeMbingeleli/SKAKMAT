<?php
if (session_status() === PHP_SESSION_NONE) {
    $sessionPath = session_save_path();
    if ($sessionPath && (!is_dir($sessionPath) || !is_writable($sessionPath))) {
        session_save_path(sys_get_temp_dir());
    }
    session_start();
}

$baseUrl = $baseUrl ?? rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if ($baseUrl === '/' || $baseUrl === '.') {
    $baseUrl = '';
}

if (empty($_COOKIE['skakmat_token'])) {
    header('Location: ' . $baseUrl . '/index.php?page=login');
    exit;
}

if (isset($_GET['role']) && in_array($_GET['role'], ['admin', 'staff'], true)) {
    $_SESSION['user_role'] = $_GET['role'];
}

if (empty($_SESSION['user_role']) && !empty($_COOKIE['skakmat_user'])) {
    $user = json_decode($_COOKIE['skakmat_user'], true);
    if ($user) {
        $_SESSION['user_role'] = $user['role'] ?? 'staff';
        $_SESSION['user_name'] = trim(($user['first_name'] ?? 'User') . ' ' . ($user['last_name'] ?? ''));
    }
}

if (empty($_SESSION['user_name'])) {
    $_SESSION['user_name'] = ($_SESSION['user_role'] ?? 'staff') === 'admin' ? 'Admin User' : 'Staff User';
}

$role = $_SESSION['user_role'] ?? 'staff';
$currentPage = $_GET['page'] ?? 'dashboard';

$staffMenu = [
    ['label' => 'Dashboard', 'i18n' => 'sidebar.dashboard', 'link' => '/index.php?page=dashboard', 'icon' => 'nav-dashboard'],
    ['label' => 'Clock In / Out', 'i18n' => 'sidebar.clockInOut', 'link' => '/index.php?page=clock-in-out', 'icon' => 'nav-clock'],
    ['label' => 'Attendance History', 'i18n' => 'sidebar.attendanceHistory', 'link' => '/index.php?page=attendance-history', 'icon' => 'nav-history'],
    ['label' => 'Leave', 'i18n' => 'sidebar.leave', 'link' => '/index.php?page=staff-leave', 'icon' => 'nav-calendar'],
    ['label' => 'Settings', 'i18n' => 'sidebar.settings', 'link' => '/index.php?page=settings', 'icon' => 'nav-settings'],
];

$adminMenu = [
    ['label' => 'Dashboard', 'i18n' => 'sidebar.dashboard', 'link' => '/index.php?page=dashboard', 'icon' => 'nav-dashboard'],
    ['label' => 'QR Codes', 'i18n' => 'sidebar.qrCodes', 'link' => '/index.php?page=admin-qr-code', 'icon' => 'nav-qr'],
    ['label' => 'Emergency', 'i18n' => 'sidebar.emergency', 'link' => '/index.php?page=admin-emergency', 'icon' => 'nav-alert'],
    ['label' => 'Employees', 'i18n' => 'sidebar.employees', 'link' => '/index.php?page=admin-employees', 'icon' => 'nav-users'],
    ['label' => 'Attendance History', 'i18n' => 'sidebar.attendanceHistory', 'link' => '/index.php?page=attendance-history', 'icon' => 'nav-history'],
    ['label' => 'Leave Requests', 'i18n' => 'sidebar.leaveRequests', 'link' => '/index.php?page=admin-leave-requests', 'icon' => 'nav-calendar'],
    ['label' => 'Reports', 'i18n' => 'sidebar.reports', 'link' => '/index.php?page=admin-reports', 'icon' => 'nav-chart'],
    ['label' => 'Settings', 'i18n' => 'sidebar.settings', 'link' => '/index.php?page=settings', 'icon' => 'nav-settings'],
];

$menu = $role === 'admin' ? $adminMenu : $staffMenu;

function navIcon(string $icon): string
{
    $icons = [
        'nav-dashboard' => '<svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"></rect><rect x="14" y="3" width="7" height="7" rx="1.5"></rect><rect x="3" y="14" width="7" height="7" rx="1.5"></rect><rect x="14" y="14" width="7" height="7" rx="1.5"></rect></svg>',
        'nav-qr' => '<svg viewBox="0 0 24 24"><path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4z"></path><path d="M14 14h2M18 14h2M14 18h6M18 18v2M14 16h4"></path></svg>',
        'nav-alert' => '<svg viewBox="0 0 24 24"><path d="M12 5a5 5 0 0 0-5 5v3.1L5.2 16a1 1 0 0 0 .8 1.6h12a1 1 0 0 0 .8-1.6L17 13.1V10a5 5 0 0 0-5-5Z"></path><path d="M9.5 20h5M10 5a2 2 0 0 1 4 0"></path></svg>',
        'nav-users' => '<svg viewBox="0 0 24 24"><path d="M16 20v-1.5A3.5 3.5 0 0 0 12.5 15h-5A3.5 3.5 0 0 0 4 18.5V20"></path><circle cx="10" cy="8" r="3.5"></circle><path d="M16 5.5a3.5 3.5 0 0 1 0 6.8M16.5 15.5h1A3.5 3.5 0 0 1 21 19v1"></path></svg>',
        'nav-history' => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8.5"></circle><path d="M12 7v5l3.2 2"></path><path d="M4.5 4.5 3 3M3 3v4h4"></path></svg>',
        'nav-calendar' => '<svg viewBox="0 0 24 24"><path d="M8 2v4M16 2v4"></path><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M3 10h18M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01"></path></svg>',
        'nav-chart' => '<svg viewBox="0 0 24 24"><path d="M4 19V5M4 19h16"></path><rect x="7" y="11" width="3" height="6" rx=".8"></rect><rect x="12" y="8" width="3" height="9" rx=".8"></rect><rect x="17" y="5" width="3" height="12" rx=".8"></rect></svg>',
        'nav-settings' => '<svg viewBox="0 0 24 24"><path d="M12 15.5A3.5 3.5 0 1 0 12 8a3.5 3.5 0 0 0 0 7.5Z"></path><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.9.3l-.1.1A2 2 0 1 1 4.2 17l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1A2 2 0 1 1 7 4.2l.1.1a1.7 1.7 0 0 0 1.9.3h.1a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5h.1a1.7 1.7 0 0 0 1.9-.3l.1-.1A2 2 0 1 1 19.8 7l-.1.1a1.7 1.7 0 0 0-.3 1.9v.1a1.7 1.7 0 0 0 1.5 1h.1a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z"></path></svg>',
        'nav-clock' => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>',
        'nav-logout' => '<svg viewBox="0 0 24 24"><path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3"></path><path d="M10 8l-4 4 4 4M6 12h12"></path></svg>',
    ];

    return $icons[$icon] ?? $icons['nav-dashboard'];
}
?>

<aside class="sidebar" id="sidebar">
    <div class="brand">
        <div class="brand-mark" aria-hidden="true"></div>
        <div>
            <strong>Skakmat</strong>
            <small>ATTENDANCEHUB</small>
        </div>
    </div>

    <div class="workspace-label" data-i18n="<?= $role === 'admin' ? 'sidebar.adminWorkspace' : 'sidebar.staffWorkspace' ?>"><?= $role === 'admin' ? 'ADMIN WORKSPACE' : 'STAFF WORKSPACE' ?></div>

    <nav class="sidebar-nav" aria-label="Primary navigation">
        <?php foreach ($menu as $item): ?>
            <?php
                $query = parse_url($item['link'], PHP_URL_QUERY);
                parse_str($query ?: '', $params);
                $itemPage = $params['page'] ?? '';
                $active = $currentPage === $itemPage ? 'active' : '';
            ?>
            <a class="nav-item <?= htmlspecialchars($active) ?>" href="<?= htmlspecialchars($baseUrl . str_replace('/public/index.php', '/index.php', $item['link'])) ?>">
                <span class="nav-icon" aria-hidden="true"><?= navIcon($item['icon']) ?></span>
                <span data-i18n="<?= htmlspecialchars($item['i18n']) ?>"><?= htmlspecialchars($item['label']) ?></span>
            </a>
        <?php endforeach; ?>

        <a class="nav-item nav-item-logout" href="#" id="sidebarLogoutBtn">
            <span class="nav-icon" aria-hidden="true"><?= navIcon('nav-logout') ?></span>
            <span>Log out</span>
        </a>
    </nav>

    <div class="role-pill"><span class="spark">+</span><?= htmlspecialchars(strtoupper($role)) ?></div>
</aside>
