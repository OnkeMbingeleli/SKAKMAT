<?php include __DIR__ . '/../partials/sidebar.php'; ?>

<?php
session_start();

// Allow switching via URL: ?role=admin or ?role=staff
if (isset($_GET['role'])) {
    $_SESSION['user_role'] = $_GET['role'];
}

// Default to staff if not set
if (!isset($_SESSION['user_role'])) {
    $_SESSION['user_role'] = 'staff';
}

// Set a default name
$_SESSION['user_name'] = $_SESSION['user_role'] === 'admin' ? 'Karabo' : 'Thabo';
?>