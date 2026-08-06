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

// Default to staff if not set
if (empty($_SESSION['user_role'])) {
    $_SESSION['user_role'] = 'staff';
}

// Preserve authenticated user name from cookie/session when available.
if (empty($_SESSION['user_name']) && !empty($_COOKIE['checkmate_user'])) {
    $user = json_decode($_COOKIE['checkmate_user'], true);
    if ($user) {
        $_SESSION['user_name'] = trim(($user['first_name'] ?? 'User') . ' ' . ($user['last_name'] ?? ''));
    }
}

if (empty($_SESSION['user_name'])) {
    $_SESSION['user_name'] = $_SESSION['user_role'] === 'admin' ? 'Admin User' : 'Staff User';
}

include __DIR__ . '/../partials/sidebar.php';
?>
