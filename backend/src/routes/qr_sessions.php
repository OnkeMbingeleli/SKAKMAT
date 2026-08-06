<?php

require_once __DIR__ . '/../controllers/QRSessionController.php';

$db = getDB();

$controller = new QRSessionController($db);

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$path = str_replace('/api', '', $path);

$input = json_decode(file_get_contents('php://input'), true) ?? [];

/*
|--------------------------------------------------------------------------
| POST /api/qr-sessions/enable
|--------------------------------------------------------------------------
*/

if ($method === 'POST' && $path === '/qr-sessions/enable') {

    $controller->enable($input);

}

/*
|--------------------------------------------------------------------------
| PATCH /api/qr-sessions/{id}/disable
|--------------------------------------------------------------------------
*/

if (
    $method === 'PATCH' &&
    preg_match('#^/qr-sessions/(\d+)/disable$#', $path, $matches)
) {

    $controller->disable((int)$matches[1]);

}

/*
|--------------------------------------------------------------------------
| GET /api/qr-sessions/active
|--------------------------------------------------------------------------
*/

if ($method === 'GET' && $path === '/qr-sessions/active') {

    $controller->active();

}