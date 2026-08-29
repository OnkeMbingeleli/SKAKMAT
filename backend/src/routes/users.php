<?php
use App\Controllers\UserController;
use App\Controllers\EmployeeInsightController;
use App\Controllers\EmployeeContractController;
use App\Controllers\EmployeeImportController;

$controller = new UserController();
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

// ------------------- PUBLIC -------------------
if ($method === 'POST' && $path === '/login') {
    $controller->login($input);
}

// ------------------- AUTHENTICATED -------------------
if ($method === 'GET' && $path === '/profile') {
    $controller->getProfile();
}

if ($method === 'PATCH' && $path === '/profile/password') {
    $controller->updatePassword($input);
}

// ------------------- ADMIN -------------------
if ($method === 'GET' && $path === '/users') {
    $controller->index();
}

if ($method === 'GET' && preg_match('#^/users/(\d+)$#', $path, $matches)) {
    $controller->show((int)$matches[1]);
}

if ($method === 'GET' && $path === '/users/staff') {
    $controller->staff();
}

if ($method === 'POST' && $path === '/users') {
    $controller->store($input);
}

if ($method === 'POST' && $path === '/users/staff') {
    $controller->createStaff($input);
}

if ($method === 'PATCH' && preg_match('#^/users/(\d+)$#', $path, $matches)) {
    $controller->updateUser((int)$matches[1], $input);
}

if ($method === 'DELETE' && preg_match('#^/users/(\d+)$#', $path, $matches)) {
    $controller->destroy((int)$matches[1]);
}

// ------------------- LEAVE BALANCE / AI ANALYSIS / CONTRACTS -------------------
$insightController = new EmployeeInsightController();
$contractController = new EmployeeContractController();
$importController = new EmployeeImportController();

// GET /api/users/me/leave-balance (any authenticated employee, own balance only)
if ($method === 'GET' && $path === '/users/me/leave-balance') {
    $insightController->myBalance();
}

// GET /api/users/{id}/analysis (admin only) — behaviour + RSA leave signals
if ($method === 'GET' && preg_match('#^/users/(\d+)/analysis$#', $path, $matches)) {
    $insightController->show((int)$matches[1]);
}

// POST /api/users/{id}/contract (admin only) — set/replace an employee's contract
if ($method === 'POST' && preg_match('#^/users/(\d+)/contract$#', $path, $matches)) {
    $contractController->store((int)$matches[1], $input);
}

if ($method === 'POST' && $path === '/employee-imports') {
    $importController->store($input);
}
