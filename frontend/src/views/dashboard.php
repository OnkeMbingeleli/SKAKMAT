<?php
if (session_status() === PHP_SESSION_NONE) {
    $sessionPath = session_save_path();
    if ($sessionPath && (!is_dir($sessionPath) || !is_writable($sessionPath))) {
        session_save_path(sys_get_temp_dir());
    }
    session_start();
}

// Allow switching via URL: ?role=admin or ?role=staff
if (isset($_GET['role']) && in_array($_GET['role'], ['admin', 'staff'], true)) {
    $_SESSION['user_role'] = $_GET['role'];
}

// Use the authenticated cookie user if available.
if (empty($_SESSION['user_name']) && !empty($_COOKIE['checkmate_user'])) {
    $user = json_decode($_COOKIE['checkmate_user'], true);
    if ($user) {
        $_SESSION['user_role'] = $user['role'] ?? $_SESSION['user_role'] ?? 'staff';
        $_SESSION['user_name'] = trim(($user['first_name'] ?? 'User') . ' ' . ($user['last_name'] ?? ''));
    }
}

// Default to staff if not set
if (empty($_SESSION['user_role'])) {
    $_SESSION['user_role'] = 'staff';
}

// Default name only when no authenticated user name exists
if (empty($_SESSION['user_name'])) {
    $_SESSION['user_name'] = $_SESSION['user_role'] === 'admin' ? 'Admin User' : 'Staff User';
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Dashboard</title>
</head>
<body>
    <?php include __DIR__ . '/partials/sidebar.php'; ?>
    <div style="margin-left: 280px; padding: 20px;">
        <h1>Dashboard loaded successfully!</h1>
    </div>
</body>
</html>
