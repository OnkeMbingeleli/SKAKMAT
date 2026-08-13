<?php
namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\AttendanceLogModel;

class AttendanceLogController
{
    private AttendanceLogModel $attendance;
    private AuthMiddleware $auth;

    public function __construct()
    {
        $this->attendance = new AttendanceLogModel();
        $this->auth = new AuthMiddleware();
    }

    /**
     * POST /api/attendance/scan (authenticated)
     *
     * Body:
     * {
     *     "token":"xxxxxxxx"
     * }
     *
     * The clocking-in user is taken from the JWT, never from the
     * request body, so a staff member can't clock in on someone else's behalf.
     */
    public function scan(array $input): void
    {
        $payload = $this->auth->requireLogin();
        $userId = (int)$payload['user_id'];

        if (!isset($input['token'])) {
            jsonResponse([
                "success" => false,
                "message" => "token is required."
            ], 400);
        }

        $result = $this->attendance->scan(
            $input['token'],
            $userId
        );

        if (!$result["success"]) {
            jsonResponse($result, 400);
        }

        jsonResponse($result, 201);
    }

    /**
     * POST /api/attendance/clock-out (authenticated; owner or admin)
     */
    public function clockOut(array $input): void
    {
        $payload = $this->auth->requireLogin();

        if (!isset($input['attendance_id'])) {
            jsonResponse([
                "success" => false,
                "message" => "attendance_id is required."
            ], 400);
        }

        $attendanceId = (int)$input['attendance_id'];

        if ($payload['role'] !== 'admin') {
            $owns = $this->attendance->belongsToUser($attendanceId, (int)$payload['user_id']);
            if (!$owns) {
                jsonResponse([
                    "success" => false,
                    "message" => "Unauthorized"
                ], 403);
            }
        }

        $success = $this->attendance->clockOut($attendanceId);

        jsonResponse([
            "success" => $success
        ]);
    }

    /**
     * GET /api/attendance/mine (authenticated)
     * Today's attendance record for the logged-in user, if any.
     */
    public function mine(): void
    {
        $payload = $this->auth->requireLogin();

        jsonResponse([
            "success" => true,
            "data" => $this->attendance->getTodayForUser((int)$payload['user_id'])
        ]);
    }

    /**
     * GET /api/attendance/present/{sessionId} (admin only)
     */
    public function presentEmployees($sessionId): void
    {
        $this->auth->requireAdmin();

        jsonResponse([
            "success" => true,
            "data" => $this->attendance->getPresentEmployees($sessionId)
        ]);
    }
}
