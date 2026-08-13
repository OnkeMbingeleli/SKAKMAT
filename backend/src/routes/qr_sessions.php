<?php
<<<<<<< HEAD

require_once __DIR__ . '/../controllers/QRSessionController.php';

$db = getDB();

$controller = new QRSessionController($db);
=======
use App\Controllers\QRSessionController;

$controller = new QRSessionController();
>>>>>>> origin/PortReferencingUpdate

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$path = str_replace('/api', '', $path);

<<<<<<< HEAD
$input = json_decode(file_get_contents('php://input'), true) ?? [];

/*
|--------------------------------------------------------------------------
| POST /api/qr-sessions/enable
=======
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
>>>>>>> origin/PortReferencingUpdate
|--------------------------------------------------------------------------
*/

if ($method === 'POST' && $path === '/qr-sessions/enable') {

    $controller->enable($input);

}

/*
|--------------------------------------------------------------------------
<<<<<<< HEAD
| PATCH /api/qr-sessions/{id}/disable
=======
| PATCH /api/qr-sessions/{id}/disable (admin)
>>>>>>> origin/PortReferencingUpdate
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
<<<<<<< HEAD
| GET /api/qr-sessions/active
=======
| GET /api/qr-sessions/active (authenticated)
>>>>>>> origin/PortReferencingUpdate
|--------------------------------------------------------------------------
*/

if ($method === 'GET' && $path === '/qr-sessions/active') {

    $controller->active();

<<<<<<< HEAD
}
=======
}
>>>>>>> origin/PortReferencingUpdate
