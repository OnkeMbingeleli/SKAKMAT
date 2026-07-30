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