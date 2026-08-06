<?php

require_once __DIR__ . '/../controllers/QRCodeController.php';

$db = getDB();

$controller = new QRCodeController($db);

$method = $_SERVER['REQUEST_METHOD'];

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$path = str_replace('/api','',$path);

$input = json_decode(file_get_contents('php://input'), true) ?? [];

/*
|--------------------------------------------------------------------------
| POST Generate QR
|--------------------------------------------------------------------------
*/

if ($method === 'POST' && $path === '/qr-codes/generate') {

    $controller->generate($input);

}

/*
|--------------------------------------------------------------------------
| GET Active QR
|--------------------------------------------------------------------------
*/

if ($method === 'GET' && $path === '/qr-codes/active') {

    $controller->active();

}

/*
|--------------------------------------------------------------------------
| PATCH Use QR
|--------------------------------------------------------------------------
*/

if ($method === 'PATCH' && $path === '/qr-codes/use') {

    $controller->use($input);

}