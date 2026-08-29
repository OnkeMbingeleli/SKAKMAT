<?php
namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\LeaveRequestModel;
use App\Models\LeaveBalanceModel;

class LeaveRequestController
{
    private LeaveRequestModel $leaveModel;
    private LeaveBalanceModel $balanceModel;
    private AuthMiddleware $auth;

    private const VALID_TYPES = [
        'annual', 'sick', 'unpaid', 'family responsibility',
        'study leave', 'maternity leave', 'paternity leave'
    ];
    private const VALID_STATUSES = ['pending', 'approved', 'rejected'];

    public function __construct()
    {
        $this->leaveModel = new LeaveRequestModel();
        $this->balanceModel = new LeaveBalanceModel();
        $this->auth = new AuthMiddleware();
    }

    /**
     * POST /api/leave-requests
     */
    public function store(array $input): void
    {
        $payload = $this->auth->requireLogin();
        $userId = $payload['user_id'];

        $required = ['leave_type', 'start_date', 'end_date'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                jsonResponse(['error' => "Field '$field' is required"], 400);
            }
        }

        if (!in_array($input['leave_type'], self::VALID_TYPES)) {
            jsonResponse(['error' => 'Invalid leave type'], 400);
        }

        // Block a request that would exceed the employee's remaining
        // balance for this leave type's current cycle (unpaid leave is
        // never blocked — see LeaveBalanceModel::hasSufficientBalance()).
        $sufficient = $this->balanceModel->hasSufficientBalance(
            (int)$userId,
            $input['leave_type'],
            $input['start_date'],
            $input['end_date']
        );
        if (!$sufficient) {
            $remaining = $this->balanceModel->getRemainingDays((int)$userId, $input['leave_type']);
            jsonResponse([
                'error' => $remaining !== null
                    ? "This request exceeds your remaining {$input['leave_type']} balance ({$remaining} day(s) left)."
                    : 'This request exceeds your remaining balance for this leave type.',
            ], 422);
        }

        $id = $this->leaveModel->create([
            'user_id'    => $userId,
            'leave_type' => $input['leave_type'],
            'start_date' => $input['start_date'],
            'end_date'   => $input['end_date'],
            'reason'     => $input['reason'] ?? null,
        ]);

        $leave = $this->leaveModel->getById($id);
        jsonResponse(['success' => true, 'data' => $leave], 201);
    }

    /**
     * GET /api/leave-requests
     * Admin: all requests, optionally filtered by ?status=pending|approved|rejected.
     * Staff: only their own requests.
     */
    public function index(): void
    {
        $payload = $this->auth->requireLogin();

        if ($payload['role'] === 'admin') {
            $status = $_GET['status'] ?? null;
            if ($status !== null && $status !== '' && !in_array($status, self::VALID_STATUSES, true)) {
                jsonResponse(['error' => 'Invalid status filter'], 400);
            }
            $list = $this->leaveModel->getAll($status !== '' ? $status : null);
        } else {
            $list = $this->leaveModel->getByUser($payload['user_id']);
        }

        jsonResponse(['success' => true, 'data' => $list]);
    }

    /**
     * GET /api/leave-requests/{id}
     */
    public function show(int $id): void
    {
        $payload = $this->auth->requireLogin();
        $leave = $this->leaveModel->getById($id);
        if (!$leave) {
            jsonResponse(['error' => 'Leave request not found'], 404);
        }

        // Only admin or owner can view
        if ($payload['role'] !== 'admin' && $leave['user_id'] != $payload['user_id']) {
            jsonResponse(['error' => 'Unauthorized'], 403);
        }

        jsonResponse(['success' => true, 'data' => $leave]);
    }

    /**
     * PATCH /api/leave-requests/{id}
     */
    public function update(int $id, array $input): void
    {
        $payload = $this->auth->requireLogin();
        $leave = $this->leaveModel->getById($id);
        if (!$leave) {
            jsonResponse(['error' => 'Leave request not found'], 404);
        }

        // Only admin or owner can update
        if ($payload['role'] !== 'admin' && $leave['user_id'] != $payload['user_id']) {
            jsonResponse(['error' => 'Unauthorized'], 403);
        }

        $data = [];
        // Regular users can only edit reason, dates, and type (if still pending).
        if ($payload['role'] !== 'admin' && $leave['status'] !== 'pending') {
            jsonResponse(['error' => 'Only pending requests can be edited'], 400);
        }

        foreach (['leave_type', 'start_date', 'end_date', 'reason'] as $field) {
            if (isset($input[$field])) {
                if ($field === 'leave_type' && !in_array($input[$field], self::VALID_TYPES)) {
                    jsonResponse(['error' => 'Invalid leave type'], 400);
                }
                $data[$field] = $input[$field];
            }
        }

        // Admin can also update status
        if ($payload['role'] === 'admin' && isset($input['status'])) {
            if (!in_array($input['status'], self::VALID_STATUSES)) {
                jsonResponse(['error' => 'Invalid status'], 400);
            }
            if (count($data) > 0) {
                jsonResponse(['error' => 'Update status separately from leave details'], 400);
            }
            try {
                $updated = $this->leaveModel->updateStatus($id, $input['status'], (int)$payload['user_id']);
            } catch (\RuntimeException $error) {
                jsonResponse(['error' => $error->getMessage()], 409);
            }
            if (!$updated) {
                jsonResponse(['error' => 'Leave request has already been processed'], 409);
            }
            jsonResponse(['success' => true, 'data' => $updated]);
        }

        if (empty($data)) {
            jsonResponse(['error' => 'No valid fields to update'], 400);
        }

        $this->leaveModel->update($id, $data);
        $leave = $this->leaveModel->getById($id);
        jsonResponse(['success' => true, 'data' => $leave]);
    }
}
