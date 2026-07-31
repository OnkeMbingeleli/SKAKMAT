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

if (empty($_SESSION['user_role'])) {
    $_SESSION['user_role'] = 'staff';
}

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
        <p>Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?></p>
        <p>Role: <?= htmlspecialchars($_SESSION['user_role']) ?></p>
    </div>
    <script src="/assets/js/login.js"></script>
</body>
</html>