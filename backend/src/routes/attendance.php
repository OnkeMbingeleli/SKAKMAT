<?php

require_once __DIR__ . '/../models/AttendanceModel.php';
require_once __DIR__ . '/../controllers/AttendanceController.php';

use App\Controllers\AttendanceController;
use App\Middleware\AuthMiddleware;

header('Content-Type: application/json');

// This route file is loaded with the rest of the API routes.  Do not open an
// attendance database connection or emit a response for unrelated requests.
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/api', '', $path);

if ($method !== 'GET' || $path !== '/attendance') {
    return;
}

try {
    $authenticatedUser = (new AuthMiddleware())->requireLogin();
    $controller = new AttendanceController(getDB());
    $currentUser = [
        'id' => (int) ($authenticatedUser['user_id'] ?? 0),
        'role' => $authenticatedUser['role'] ?? 'staff'
    ];

    $action = $_GET['action'] ?? 'history';

    if ($action === 'history') {
        $controller->getHistory($currentUser, $_GET);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Action not found']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection error: ' . $e->getMessage()]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
