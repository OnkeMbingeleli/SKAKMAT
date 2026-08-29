<?php
use App\Controllers\LeaveBalanceController;
use App\Controllers\EmployeeInsightController;

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/api', '', $path);

$controller = new LeaveBalanceController();
$insightController = new EmployeeInsightController();

if ($method === 'GET' && $path === '/users/me/leave-balance') {
    $insightController->myBalance();
}

if ($method === 'GET' && $path === '/leave-balance') {
    $controller->mine();
}

if ($method === 'GET' && $path === '/leave-balance/all') {
    $controller->all();
}

if ($method === 'GET' && preg_match('#^/leave-balance/(\d+)$#', $path, $matches)) {
    $controller->show((int)$matches[1]);
}
