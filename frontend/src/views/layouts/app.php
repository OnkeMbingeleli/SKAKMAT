<?php
/**
 * Shared page shell for every "fragment" view (employees, reports, leave,
 * QR codes, emergency, clock in/out, etc). Each of those views does:
 *
 *   $page = 'admin-employees';
 *   $title = 'Employees - CheckMate';
 *   ob_start();
 *   ... html ...
 *   $content = ob_get_clean();
 *   include __DIR__ . '/../layouts/app.php';
 *
 * This file is the only place that opens <html>/<body>, loads the shared
 * stylesheet + preference script, includes the sidebar/header partials,
 * and actually echoes $content. Previously this file did none of that,
 * which is why those pages rendered blank.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$title = $title ?? 'CheckMate';
$content = $content ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="checkmate-app" data-page="<?= htmlspecialchars($page ?? '', ENT_QUOTES, 'UTF-8') ?>" data-role="<?= htmlspecialchars($_SESSION['user_role'] ?? 'staff', ENT_QUOTES, 'UTF-8') ?>">
<div class="app-shell">
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>
    <div class="main-shell">
        <?php include __DIR__ . '/../partials/header.php'; ?>
        <main class="page-content">
            <?= $content ?>
        </main>
    </div>
</div>
<script src="/assets/js/i18n.js"></script>
<script src="/assets/js/app-preferences.js"></script>
</body>
</html>
