<?php
use App\Controllers\QRSessionController;

$controller = new QRSessionController();

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$path = str_replace('/api', '', $path);

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if ($rawInput !== '' && !is_array($input)) {
    jsonResponse([
        "success" => false,
        "message" => "Invalid JSON body."
    ], 400);
}

$input = $input ?? [];

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
