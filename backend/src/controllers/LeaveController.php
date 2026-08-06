<?php
namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\LeaveModel;

class LeaveController
{
    private LeaveModel $leaveModel;
    private AuthMiddleware $auth;

    private array $allowedTypes = [
        'annual',
        'sick',
        'family responsibility',
        'study leave',
        'maternity leave',
        'paternity leave',
        'unpaid',
    ];

    public function __construct()
    {
        $this->leaveModel = new LeaveModel();
        $this->auth = new AuthMiddleware();
    }

    public function my(): void
    {
        $payload = $this->auth->requireLogin();
        $userId = (int)($payload['user_id'] ?? 0);

        jsonResponse([
            'success' => true,
            'data' => $this->leaveModel->getByUser($userId),
        ]);
    }

    public function index(): void
    {
        $this->auth->requireAdmin();
        $status = $_GET['status'] ?? null;

        if ($status !== null && !in_array($status, ['pending', 'approved', 'rejected'], true)) {
            jsonResponse(['error' => 'Invalid leave status filter'], 400);
        }

        jsonResponse([
            'success' => true,
            'data' => $this->leaveModel->getAll($status),
        ]);
    }

    public function store(array $input): void
    {
        $payload = $this->auth->requireLogin();

        $leaveType = strtolower(trim((string)($input['leave_type'] ?? '')));
        $startDate = trim((string)($input['start_date'] ?? ''));
        $endDate = trim((string)($input['end_date'] ?? ''));
        $reason = trim((string)($input['reason'] ?? ''));

        if (!in_array($leaveType, $this->allowedTypes, true)) {
            jsonResponse(['error' => 'Leave type is required'], 400);
        }

        if (!$this->isDate($startDate)) {
            jsonResponse(['error' => 'Start date is required'], 400);
        }

        if (!$this->isDate($endDate)) {
            jsonResponse(['error' => 'End date is required'], 400);
        }

        if (strtotime($endDate) < strtotime($startDate)) {
            jsonResponse(['error' => 'End date cannot be before start date'], 400);
        }

        if (strlen($reason) > 225) {
            jsonResponse(['error' => 'Reason cannot exceed 225 characters'], 400);
        }

        try {
            $leave = $this->leaveModel->create([
                'user_id' => (int)$payload['user_id'],
                'leave_type' => $leaveType,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'reason' => $reason,
            ]);

            jsonResponse([
                'success' => true,
                'message' => 'Leave request submitted',
                'data' => $leave,
            ], 201);
        } catch (\Throwable $e) {
            jsonResponse(['error' => 'Could not submit leave request'], 500);
        }
    }

    public function approve(int $id): void
    {
        $this->changeStatus($id, 'approved');
    }

    public function reject(int $id): void
    {
        $this->changeStatus($id, 'rejected');
    }

    private function changeStatus(int $id, string $status): void
    {
        $this->auth->requireAdmin();

        if ($id <= 0) {
            jsonResponse(['error' => 'Invalid leave request id'], 400);
        }

        $leave = $this->leaveModel->updateStatus($id, $status);
        if (!$leave) {
            jsonResponse(['error' => 'Leave request not found'], 404);
        }

        jsonResponse([
            'success' => true,
            'message' => 'Leave request ' . $status,
            'data' => $leave,
        ]);
    }

    private function isDate(string $date): bool
    {
        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
        return $parsed && $parsed->format('Y-m-d') === $date;
    }
}
