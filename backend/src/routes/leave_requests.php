<?php
use App\Controllers\LeaveRequestController;

$controller = new LeaveRequestController();
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove /api prefix if present
$path = str_replace('/api', '', $path);

$input = [];
if (
    ($method === 'POST' && $path === '/leave-requests') ||
    ($method === 'PATCH' && preg_match('#^/leave-requests/(\d+)$#', $path))
) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
}

// POST /api/leave-requests – create
if ($method === 'POST' && $path === '/leave-requests') {
    $controller->store($input);
}

// GET /api/leave-requests – list (admin: all; staff: own)
if ($method === 'GET' && $path === '/leave-requests') {
    $controller->index();
}

// GET /api/leave-requests/{id} – single
if ($method === 'GET' && preg_match('#^/leave-requests/(\d+)$#', $path, $matches)) {
    $controller->show((int)$matches[1]);
}

// PATCH /api/leave-requests/{id} – update
if ($method === 'PATCH' && preg_match('#^/leave-requests/(\d+)$#', $path, $matches)) {
    $controller->update((int)$matches[1], $input);
}
