<?php
use App\Controllers\AttendanceLogController;

$controller = new AttendanceLogController();

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
| POST Scan QR & Clock In (authenticated)
|--------------------------------------------------------------------------
*/

if ($method === 'POST' && $path === '/attendance/scan') {

    $controller->scan($input);

}

/*
|--------------------------------------------------------------------------
| POST Clock Out (authenticated; owner or admin)
|--------------------------------------------------------------------------
*/

if ($method === 'POST' && $path === '/attendance/clock-out') {

    $controller->clockOut($input);

}

/*
|--------------------------------------------------------------------------
| GET Present Employees (admin)
|--------------------------------------------------------------------------
*/

if (
    $method === 'GET' &&
    preg_match('#^/attendance/present/(\d+)$#', $path, $matches)
) {

    $controller->presentEmployees((int)$matches[1]);

}
