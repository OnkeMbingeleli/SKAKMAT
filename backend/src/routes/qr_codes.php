<?php
<<<<<<< HEAD

require_once __DIR__ . '/../controllers/QRCodeController.php';

$db = getDB();

$controller = new QRCodeController($db);
=======
use App\Controllers\QRCodeController;

$controller = new QRCodeController();
>>>>>>> origin/PortReferencingUpdate

$method = $_SERVER['REQUEST_METHOD'];

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

<<<<<<< HEAD
$path = str_replace('/api','',$path);

$input = json_decode(file_get_contents('php://input'), true) ?? [];

/*
|--------------------------------------------------------------------------
| POST Generate QR
=======
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
| POST Generate QR (admin)
>>>>>>> origin/PortReferencingUpdate
|--------------------------------------------------------------------------
*/

if ($method === 'POST' && $path === '/qr-codes/generate') {

    $controller->generate($input);

}

/*
|--------------------------------------------------------------------------
<<<<<<< HEAD
| GET Active QR
=======
| GET Active QR (admin)
>>>>>>> origin/PortReferencingUpdate
|--------------------------------------------------------------------------
*/

if ($method === 'GET' && $path === '/qr-codes/active') {

    $controller->active();

}

/*
|--------------------------------------------------------------------------
<<<<<<< HEAD
| PATCH Use QR
=======
| PATCH Use QR (admin)
>>>>>>> origin/PortReferencingUpdate
|--------------------------------------------------------------------------
*/

if ($method === 'PATCH' && $path === '/qr-codes/use') {

    $controller->use($input);

<<<<<<< HEAD
}
=======
}
>>>>>>> origin/PortReferencingUpdate
