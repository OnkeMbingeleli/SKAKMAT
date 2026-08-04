<?php
require_once __DIR__ . '/../src/bootstrap.php';
// Qaasim fvcked up
// =============================================
// TEST ROUTE – Database connectivity check
// =============================================
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($method === 'GET' && $path === '/test-db') {
    try {
        $db = getDB();
        // Run a simple query to verify
        $stmt = $db->query('SELECT 1');
        jsonResponse([
            'success' => true,
            'message' => 'Database connection successful'
        ]);
    } catch (\Throwable $e) {
        http_response_code(500);
        jsonResponse([
            'success' => false,
            'message' => 'Database connection failed',
            'error'   => $e->getMessage()
        ]);
    }
    exit;
}

// Load all route files
foreach (glob(__DIR__ . '/../src/routes/*.php') as $routeFile) {
    require $routeFile;
}