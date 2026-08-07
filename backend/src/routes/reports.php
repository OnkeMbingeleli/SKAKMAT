<?php
use App\Controllers\ReportController;

$controller = new ReportController();
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/api', '', $path);

if ($method === 'GET' && $path === '/reports') {
    $controller->index();
}
