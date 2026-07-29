<?php
use App\Controllers\UserController;

$controller = new UserController();
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove /api prefix if present
$path = str_replace('/api', '', $path);

// ------------------- PUBLIC ROUTES -------------------
if ($method === 'POST' && $path === '/login') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $controller->login($input);
}

// ------------------- AUTHENTICATED ROUTES -------------------
if ($method === 'GET' && $path === '/profile') {
    $controller->getProfile();
}

// ------------------- ADMIN ROUTES -------------------
if ($method === 'GET' && $path === '/users') {
    $controller->index();
}

if ($method === 'GET' && $path === '/users/staff') {
    $controller->staff();
}

if ($method === 'POST' && $path === '/users') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $controller->store($input);
}