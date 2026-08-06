<?php
namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\LeaveRequestModel;

class LeaveRequestController
{
    private LeaveRequestModel $leaveModel;
    private AuthMiddleware $auth;

    private const VALID_TYPES = [
        'annual', 'sick', 'unpaid', 'family responsibility',
        'study leave', 'maternity leave', 'paternity leave'
    ];
    private const VALID_STATUSES = ['pending', 'approved', 'rejected'];

    public function __construct()
    {
        $this->leaveModel = new LeaveRequestModel();
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
     */
    public function index(): void
    {
        $payload = $this->auth->requireLogin();
        $status = $_GET['status'] ?? null;

        if ($payload['role'] === 'admin') {
            if ($status !== null && !in_array($status, self::VALID_STATUSES, true)) {
                jsonResponse(['error' => 'Invalid status filter'], 400);
            }
            $list = $this->leaveModel->getAll($status);
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
            $data['status'] = $input['status'];
        }

        if (empty($data)) {
            jsonResponse(['error' => 'No valid fields to update'], 400);
        }

        $this->leaveModel->update($id, $data);
        $leave = $this->leaveModel->getById($id);
        jsonResponse(['success' => true, 'data' => $leave]);
    }
}