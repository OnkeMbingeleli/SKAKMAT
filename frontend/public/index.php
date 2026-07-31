<?php
/**
 * Frontend Router
 * Routes requests to appropriate view files
 */

// Get the requested page
$page = $_GET['page'] ?? 'login';

// Define available pages
$pages = [
    'login' => '../src/views/login.php',
    'dashboard' => '../src/views/dashboard.php',
    'attendance-history' => '../src/views/attendance-history.php',
    'settings' => '../src/views/settings.php',
    'signup' => '../src/views/signup.php',
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
