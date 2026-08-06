<?php

require_once __DIR__ . '/../models/AttendanceLogModel.php';

class AttendanceLogController
{
    private AttendanceLogModel $attendance;

    public function __construct(PDO $db)
    {
        $this->attendance = new AttendanceLogModel($db);
    }

    /**
     * POST /api/attendance/scan
     *
     * Body:
     * {
     *     "token":"xxxxxxxx",
     *     "user_id":1
     * }
     */
    public function scan(array $input)
    {
        if (
            !isset($input['token']) ||
            !isset($input['user_id'])
        ) {

            jsonResponse([
                "success" => false,
                "message" => "token and user_id are required."
            ], 400);

        }

        $result = $this->attendance->scan(
            $input['token'],
            $input['user_id']
        );

        if (!$result["success"]) {
            jsonResponse($result, 400);
        }

        jsonResponse($result, 201);
    }

    /**
     * POST /api/attendance/clock-out
     */
    public function clockOut(array $input)
    {
        if (!isset($input['attendance_id'])) {

            jsonResponse([
                "success" => false,
                "message" => "attendance_id is required."
            ], 400);

        }

        $success = $this->attendance->clockOut(
            $input['attendance_id']
        );

        jsonResponse([
            "success" => $success
        ]);
    }

    /**
     * GET /api/attendance/present/{sessionId}
     */
    public function presentEmployees($sessionId)
    {
        jsonResponse([
            "success" => true,
            "data" => $this->attendance->getPresentEmployees($sessionId)
        ]);
    }
}