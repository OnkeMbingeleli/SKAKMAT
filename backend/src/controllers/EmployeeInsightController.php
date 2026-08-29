<?php
namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Services\EmployeeInsightService;

class EmployeeInsightController
{
    private AuthMiddleware $auth;
    private EmployeeInsightService $service;

    public function __construct()
    {
        $this->auth = new AuthMiddleware();
        $this->service = new EmployeeInsightService();
    }

    public function show(int $userId): void
    {
        $this->auth->requireAdmin();
        $analysis = $this->service->analyze($userId);
        if (!$analysis) jsonResponse(['success' => false, 'error' => 'Employee not found'], 404);
        jsonResponse(['success' => true, 'data' => $analysis]);
    }

    /**
     * GET /users/me/leave-balance
     * Any logged-in employee can see their own allocated/used/remaining
     * RSA leave days — no admin rights required.
     */
    public function myBalance(): void
    {
        $payload = $this->auth->requireLogin();
        $balance = $this->service->leaveBalance((int)$payload['user_id']);
        if ($balance === null) jsonResponse(['success' => false, 'error' => 'Employee not found'], 404);
        jsonResponse(['success' => true, 'data' => $balance]);
    }
}