<?php
use App\Controllers\UserController;

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