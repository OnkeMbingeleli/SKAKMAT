<?php
// Leave balance endpoints: own balance (any staff), full overview (admin).
namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\LeaveBalanceModel;

class LeaveBalanceController
{
    private LeaveBalanceModel $balanceModel;
    private AuthMiddleware $auth;

    public function __construct()
    {
        $this->balanceModel = new LeaveBalanceModel();
        $this->auth = new AuthMiddleware();
    }

    /**
     * GET /api/leave-balance — the logged-in user's own balances.
     * Used on the staff dashboard and the leave request page.
     */
    public function mine(): void
    {
        $payload = $this->auth->requireLogin();
        $balances = $this->balanceModel->getForUser((int)$payload['user_id']);
        jsonResponse(['success' => true, 'data' => $balances]);
    }

    /**
     * GET /api/leave-balance/all — every staff member's balances (admin only).
     * Used on the admin Leave Balance Overview page.
     */
    public function all(): void
    {
        $this->auth->requireAdmin();
        $balances = $this->balanceModel->getAll();
        jsonResponse(['success' => true, 'data' => $balances]);
    }

    /**
     * GET /api/leave-balance/{userId} — a specific employee's balances (admin only).
     */
    public function show(int $userId): void
    {
        $this->auth->requireAdmin();
        $balances = $this->balanceModel->getForUser($userId);
        jsonResponse(['success' => true, 'data' => $balances]);
    }
}
