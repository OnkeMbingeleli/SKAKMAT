<?php
/**
 * Frontend Router
 * Routes requests to appropriate view files
 */

if (session_status() === PHP_SESSION_NONE) {
    $sessionPath = session_save_path();
    if ($sessionPath && (!is_dir($sessionPath) || !is_writable($sessionPath))) {
        session_save_path(sys_get_temp_dir());
    }
    session_start();
}

// Serve shared frontend assets from frontend/assets
$requestedPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (strpos($requestedPath, '/assets/') === 0) {
    $assetFile = realpath(__DIR__ . '/../assets' . substr($requestedPath, strlen('/assets')));
    $assetsDir = realpath(__DIR__ . '/../assets');
    if ($assetFile && strpos($assetFile, $assetsDir) === 0 && is_file($assetFile)) {
        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
            'json' => 'application/json',
        ];
        $ext = strtolower(pathinfo($assetFile, PATHINFO_EXTENSION));
        header('Content-Type: ' . ($mimeTypes[$ext] ?? 'application/octet-stream'));
        readfile($assetFile);
        exit;
    }
}

// Get the requested page
$page = $_GET['page'] ?? 'login';

if (isset($_GET['token']) && isset($_GET['user'])) {
    $token = $_GET['token'];
    $userData = $_GET['user'];

    setcookie('checkmate_token', $token, time() + 3600, '/');
    setcookie('checkmate_user', $userData, time() + 3600, '/');

    $_COOKIE['checkmate_token'] = $token;
    $_COOKIE['checkmate_user'] = $userData;

    $decodedUser = json_decode($userData, true);
    if (is_array($decodedUser)) {
        $_SESSION['user_role'] = $decodedUser['role'] ?? 'staff';
        $_SESSION['user_name'] = trim(($decodedUser['first_name'] ?? 'User') . ' ' . ($decodedUser['last_name'] ?? ''));
    }
}

if (isset($_GET['role']) && in_array($_GET['role'], ['admin', 'staff'], true)) {
    $_SESSION['user_role'] = $_GET['role'];
}

if (empty($_SESSION['user_role']) && !empty($_COOKIE['checkmate_user'])) {
    $cookieUser = json_decode($_COOKIE['checkmate_user'], true);
    if (is_array($cookieUser)) {
        $_SESSION['user_role'] = $cookieUser['role'] ?? 'staff';
        $_SESSION['user_name'] = trim(($cookieUser['first_name'] ?? 'User') . ' ' . ($cookieUser['last_name'] ?? ''));
    }
}

// Define available pages
$pages = [
    'login' => __DIR__ . '/../src/views/login.php',
    'dashboard' => __DIR__ . '/../src/views/dashboard.php',
    'attendance-history' => __DIR__ . '/../src/views/attendance-history.php',
    'settings' => __DIR__ . '/../src/views/settings.php',
    'signup' => __DIR__ . '/../src/views/signup.php',
    'clock-in-out' => __DIR__ . '/../src/views/staff/clock-in-out.php',
    'staff-leave' => __DIR__ . '/../src/views/staff/leave.php',
    'admin-emergency' => __DIR__ . '/../src/views/admin/emergency.php',
    'admin-employees' => __DIR__ . '/../src/views/admin/employees.php',
    'admin-leave-requests' => __DIR__ . '/../src/views/admin/leave-requests.php',
    'admin-qr-code' => __DIR__ . '/../src/views/admin/qr-code.php',
    'admin-reports' => __DIR__ . '/../src/views/admin/reports.php',
];

// Default to login if page not found
if (!isset($pages[$page])) {
    $page = 'login';
}

$viewFile = $pages[$page];
$standalonePages = ['login', 'dashboard', 'admin-emergency', 'logout'];
$pageScripts = [
    'staff-leave' => '/assets/js/leave.js',
    'admin-leave-requests' => '/assets/js/leave.js',
];

if ($page === 'logout') {
    setcookie('checkmate_token', '', time() - 3600, '/');
    setcookie('checkmate_user', '', time() - 3600, '/');
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
    header('Location: /public/index.php?page=login');
    exit;
}

if ($page !== 'login' && !in_array($page, $standalonePages, true) && empty($_COOKIE['checkmate_token'])) {
    header('Location: /public/index.php?page=login');
    exit;
}

// Check if file exists
if (file_exists($viewFile)) {
    if (in_array($page, $standalonePages, true)) {
        include $viewFile;
    } else {
        $workspaceRole = $_SESSION['user_role'] ?? 'staff';
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>CheckMate</title>
            <link rel="stylesheet" href="/assets/css/app.css">
        </head>
            <body data-role="<?= htmlspecialchars($workspaceRole) ?>" data-page="<?= htmlspecialchars($page) ?>">
            <div class="app-shell">
                <?php include __DIR__ . '/../src/views/partials/sidebar.php'; ?>
                <main class="main-shell">
                    <section class="page-content">
                        <?php include $viewFile; ?>
                    </section>
                </main>
            </div>
            <script src="/assets/js/api.js"></script>
            <script src="/assets/js/store.js"></script>
            <?php if (isset($pageScripts[$page])): ?>
                <script src="<?= htmlspecialchars($pageScripts[$page]) ?>"></script>
            <?php endif; ?>
        </body>
        </html>
        <?php
    }
} else {
    echo "<h1>404 - Page Not Found</h1>";
    echo "<p>The page you requested does not exist: $viewFile</p>";
}
?>
