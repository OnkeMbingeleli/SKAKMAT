<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Must run before any HTML output, so the redirect header can still be sent.
if (empty($_COOKIE['checkmate_token'])) {
    header('Location: /public/index.php?page=login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= isset($title) ? htmlspecialchars($title) : 'CheckMate' ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="/assets/css/app.css" />
</head>
<body>

<?php include __DIR__ . '/../partials/sidebar.php'; ?>

<?php
// session already started in frontend/public/index.php

// Dev-only override: ?role=admin or ?role=staff, only when not logged in.
if (isset($_GET['role']) && !isset($_SESSION['user_id'])) {
    $_SESSION['user_role'] = $_GET['role'];
    $_SESSION['user_name'] = $_GET['role'] === 'admin' ? 'Karabo (demo)' : 'Thabo (demo)';
}

// Fallback only if no session exists at all (e.g. dev preview without login).
if (!isset($_SESSION['user_role'])) {
    $_SESSION['user_role'] = 'staff';
}
if (!isset($_SESSION['user_name'])) {
    $_SESSION['user_name'] = 'Guest';
}
?>

<?php include __DIR__ . '/../partials/header.php'; ?>

<div class="cm-main">
    <div class="cm-container">
        <?php
        // Render the page content captured by views
        if (isset($content)) {
            echo $content;
        } else {
            echo '<p>Page content is missing.</p>';
        }
        ?>
    </div>
</div>

<style>
/* Basic main content spacing to sit next to sidebar */
.cm-main { margin-left: 280px; padding: 28px; }
.cm-container { max-width: 1100px; margin: 0 auto; }
</style>

</body>
</html>
