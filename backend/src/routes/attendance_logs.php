<?php
<<<<<<< HEAD

require_once __DIR__ . '/../controllers/AttendanceLogController.php';

$db = getDB();

$controller = new AttendanceLogController($db);
=======
use App\Controllers\AttendanceLogController;

$controller = new AttendanceLogController();
>>>>>>> origin/PortReferencingUpdate

$method = $_SERVER['REQUEST_METHOD'];

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$path = str_replace('/api', '', $path);

<<<<<<< HEAD
$input = json_decode(file_get_contents('php://input'), true) ?? [];

/*
|--------------------------------------------------------------------------
| POST Scan QR & Clock In
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
| POST Scan QR & Clock In (authenticated)
>>>>>>> origin/PortReferencingUpdate
|--------------------------------------------------------------------------
*/

if ($method === 'POST' && $path === '/attendance/scan') {

    $controller->scan($input);

}

/*
|--------------------------------------------------------------------------
<<<<<<< HEAD
| POST Clock Out
=======
| POST Clock Out (authenticated; owner or admin)
>>>>>>> origin/PortReferencingUpdate
|--------------------------------------------------------------------------
*/

if ($method === 'POST' && $path === '/attendance/clock-out') {

    $controller->clockOut($input);

}

/*
|--------------------------------------------------------------------------
<<<<<<< HEAD
| GET Present Employees
=======
| GET My Attendance Today (authenticated)
|--------------------------------------------------------------------------
*/

if ($method === 'GET' && $path === '/attendance/mine') {

    $controller->mine();

}

/*
|--------------------------------------------------------------------------
| GET Present Employees (admin)
>>>>>>> origin/PortReferencingUpdate
|--------------------------------------------------------------------------
*/

if (
    $method === 'GET' &&
    preg_match('#^/attendance/present/(\d+)$#', $path, $matches)
) {

    $controller->presentEmployees((int)$matches[1]);

<<<<<<< HEAD
}
=======
}

/*
|--------------------------------------------------------------------------
| GET /api/attendance/mine (authenticated)
|--------------------------------------------------------------------------
*/

if ($method === 'GET' && $path === '/attendance/mine') {

    $controller->mine();

}
>>>>>>> origin/PortReferencingUpdate
