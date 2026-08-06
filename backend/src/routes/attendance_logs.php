<?php

require_once __DIR__ . '/../controllers/AttendanceLogController.php';

$db = getDB();

$controller = new AttendanceLogController($db);

$method = $_SERVER['REQUEST_METHOD'];

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$path = str_replace('/api', '', $path);

$input = json_decode(file_get_contents('php://input'), true) ?? [];

/*
|--------------------------------------------------------------------------
| POST Scan QR & Clock In
|--------------------------------------------------------------------------
*/

if ($method === 'POST' && $path === '/attendance/scan') {

    $controller->scan($input);

}

/*
|--------------------------------------------------------------------------
| POST Clock Out
|--------------------------------------------------------------------------
*/

if ($method === 'POST' && $path === '/attendance/clock-out') {

    $controller->clockOut($input);

}

/*
|--------------------------------------------------------------------------
| GET Present Employees
|--------------------------------------------------------------------------
*/

if (
    $method === 'GET' &&
    preg_match('#^/attendance/present/(\d+)$#', $path, $matches)
) {

    $controller->presentEmployees((int)$matches[1]);

}