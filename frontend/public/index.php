<?php
/**
 * Frontend Router
 * Routes requests to appropriate view files
 */

session_start();

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
<<<<<<< HEAD
        $_SESSION['user_role'] = $decodedUser['role'] ?? 'staff';
        $_SESSION['user_name'] = trim(($decodedUser['first_name'] ?? 'User') . ' ' . ($decodedUser['last_name'] ?? ''));
=======
        $_SESSION['user_id']     = $decodedUser['id'] ?? null;
        $_SESSION['user_role']   = $decodedUser['role'] ?? 'staff';
        $_SESSION['user_name']   = trim(($decodedUser['first_name'] ?? 'User') . ' ' . ($decodedUser['last_name'] ?? ''));
        $_SESSION['user_email']  = $decodedUser['email'] ?? null;
        $_SESSION['department']  = $decodedUser['department'] ?? null;
>>>>>>> origin/PortReferencingUpdate
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

// Check if file exists
if (file_exists($viewFile)) {
    include $viewFile;
} else {
    echo "<h1>404 - Page Not Found</h1>";
    echo "<p>The page you requested does not exist: $viewFile</p>";
}
?>
