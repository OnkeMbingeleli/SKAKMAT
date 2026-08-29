<?php

/*
|--------------------------------------------------------------------------
| Emergency roll call — endpoint summary
|--------------------------------------------------------------------------
|
| GET  /api/emergency/active      Current emergency + roll call, or null.
| POST /api/emergency/start       Admin. No body. Starts a new roll call.
| POST /api/emergency/mark-safe   Admin. Body: { "emergency_log_id": n }
| POST /api/emergency/end         Admin. Body: { "emergency_id": n }
|
| Full request/response shapes are documented on each method in
| App\Controllers\EmergencyLogController.
|
*/

use App\Controllers\EmergencyLogController;

$controller = new EmergencyLogController();

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
| GET Active Emergency + Roll Call (authenticated)
|--------------------------------------------------------------------------
*/

if ($method === 'GET' && $path === '/emergency/active') {

    $controller->active();

}

/*
|--------------------------------------------------------------------------
| POST Start Evacuation (admin only)
|--------------------------------------------------------------------------
*/

if ($method === 'POST' && $path === '/emergency/start') {

    $controller->start();

}

/*
|--------------------------------------------------------------------------
| POST Mark Employee Safe (admin only)
|--------------------------------------------------------------------------
*/

if ($method === 'POST' && $path === '/emergency/mark-safe') {

    $controller->markSafe($input);

}

/*
|--------------------------------------------------------------------------
| POST End Evacuation (admin only)
|--------------------------------------------------------------------------
*/

if ($method === 'POST' && $path === '/emergency/end') {

    $controller->end($input);

}