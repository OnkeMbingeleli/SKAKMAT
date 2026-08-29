<?php
use App\Controllers\LeaveController;

$controller = new LeaveController();

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/api', '', $path);

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if ($rawInput !== '' && !is_array($input)) {
    jsonResponse([
        'success' => false,
        'message' => 'Invalid JSON body.'
    ], 400);
}

$input = $input ?? [];

/*
|--------------------------------------------------------------------------
| GET /api/leaves/my (authenticated)
|--------------------------------------------------------------------------
*/
if ($method === 'GET' && $path === '/leaves/my') {
    $controller->my();
}

/*
|--------------------------------------------------------------------------
| GET /api/leaves (admin only)
|--------------------------------------------------------------------------
*/
if ($method === 'GET' && $path === '/leaves') {
    $controller->index();
}

/*
|--------------------------------------------------------------------------
| POST /api/leaves (authenticated)
|--------------------------------------------------------------------------
*/
if ($method === 'POST' && $path === '/leaves') {
    $controller->store($input);
}

/*
|--------------------------------------------------------------------------
| PATCH /api/leaves/{id}/approve (admin only)
|--------------------------------------------------------------------------
*/
if ($method === 'PATCH' && preg_match('#^/leaves/(\d+)/approve$#', $path, $matches)) {
    $controller->approve((int)$matches[1]);
}

/*
|--------------------------------------------------------------------------
| PATCH /api/leaves/{id}/reject (admin only)
|--------------------------------------------------------------------------
*/
if ($method === 'PATCH' && preg_match('#^/leaves/(\d+)/reject$#', $path, $matches)) {
    $controller->reject((int)$matches[1]);
}