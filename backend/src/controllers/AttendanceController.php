<?php

namespace App\Controllers;

use App\Models\AttendanceModel;

class AttendanceController {
    private $attendanceModel;

    public function __construct($db) {
        $this->attendanceModel = new AttendanceModel($db);
    }

    /**
     * Get history based on logged-in user's role
     */
    public function getHistory($currentUser, $queryParams) {
        header('Content-Type: application/json');

        // Check if user is logged in
        if (!$currentUser) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        // If Admin, fetch all records (with optional filters)
        if ($currentUser['role'] === 'admin') {
            $startDate = $queryParams['start_date'] ?? null;
            $endDate = $queryParams['end_date'] ?? null;
            $search = $queryParams['search'] ?? null;

            $data = $this->attendanceModel->getAllHistory($startDate, $endDate, $search);
            echo json_encode(['success' => true, 'role' => 'admin', 'data' => $data]);
            return;
        }

        // If Staff, fetch ONLY their own records
        $data = $this->attendanceModel->getStaffHistory($currentUser['id']);
        echo json_encode(['success' => true, 'role' => 'staff', 'data' => $data]);
    }
}
