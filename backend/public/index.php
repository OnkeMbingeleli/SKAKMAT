<?php
require_once __DIR__ . '/../src/bootstrap.php';

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

// Load all route files. Wrapped so a slow/unreachable database (PDO now
// times out after 8s instead of hanging forever) or any other uncaught
// error still comes back as clean JSON, not a blank/broken response that
// makes the frontend's fetch() fail with a confusing parse error.
try {
    foreach (glob(__DIR__ . '/../src/routes/*.php') as $routeFile) {
        require $routeFile;
    }
} catch (\PDOException $e) {
    http_response_code(503);
    jsonResponse([
        'success' => false,
        'error' => 'Could not reach the database. If it is hosted remotely (e.g. Railway), it may be asleep or unreachable — try again in a few seconds.',
    ]);
} catch (\Throwable $e) {
    http_response_code(500);
    jsonResponse([
        'success' => false,
        'error' => 'Unexpected server error: ' . $e->getMessage(),
    ]);
}