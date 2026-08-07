<?php
use App\Controllers\QRSessionController;

$controller = new QRSessionController();

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$path = str_replace('/api', '', $path);

$input = json_decode(file_get_contents('php://input'), true) ?? [];

/*
|--------------------------------------------------------------------------
| POST /api/qr-sessions/enable (admin)
|--------------------------------------------------------------------------
*/

if ($method === 'POST' && $path === '/qr-sessions/enable') {

    $controller->enable($input);

}

/*
|--------------------------------------------------------------------------
| PATCH /api/qr-sessions/{id}/disable (admin)
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
| GET /api/qr-sessions/active (authenticated)
|--------------------------------------------------------------------------
*/

if ($method === 'GET' && $path === '/qr-sessions/active') {

    $controller->active();

}
