<?php

namespace App\Models;

use PDO;

class AttendanceModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Get attendance records for a single logged-in staff member
     */
    public function getStaffHistory($userId) {
        $query = "SELECT
                    attendance_logs.id,
                    qr_sessions.date,
                    TIME(attendance_logs.clock_in_at) AS check_in,
                    TIME(attendance_logs.clock_out_at) AS check_out,
                    CASE
                        WHEN attendance_logs.clock_out_at IS NULL THEN NULL
                        ELSE SEC_TO_TIME(TIMESTAMPDIFF(SECOND, attendance_logs.clock_in_at, attendance_logs.clock_out_at))
                    END AS total_hours,
                    CASE
                        WHEN attendance_logs.status = 'clocked_in' THEN 'active'
                        WHEN qr_sessions.clock_in_deadline IS NOT NULL
                             AND TIME(attendance_logs.clock_in_at) > qr_sessions.clock_in_deadline THEN 'late'
                        ELSE 'on_time'
                    END AS status
                  FROM attendance_logs
                  INNER JOIN qr_sessions ON qr_sessions.id = attendance_logs.session_id
                  WHERE attendance_logs.user_id = :user_id
                  ORDER BY qr_sessions.date DESC, attendance_logs.clock_in_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get attendance records for ALL employees (Admin View with optional filters)
     */
    public function getAllHistory($startDate = null, $endDate = null, $searchName = null) {
        $query = "SELECT
                    attendance_logs.id,
                    attendance_logs.user_id,
                    CONCAT(users.first_name, ' ', users.last_name) AS employee_name,
                    users.department AS department,
                    qr_sessions.date,
                    TIME(attendance_logs.clock_in_at) AS check_in,
                    TIME(attendance_logs.clock_out_at) AS check_out,
                    CASE
                        WHEN attendance_logs.clock_out_at IS NULL THEN NULL
                        ELSE SEC_TO_TIME(TIMESTAMPDIFF(SECOND, attendance_logs.clock_in_at, attendance_logs.clock_out_at))
                    END AS total_hours,
                    CASE
                        WHEN attendance_logs.status = 'clocked_in' THEN 'active'
                        WHEN qr_sessions.clock_in_deadline IS NOT NULL
                             AND TIME(attendance_logs.clock_in_at) > qr_sessions.clock_in_deadline THEN 'late'
                        ELSE 'on_time'
                    END AS status
                  FROM attendance_logs
                  INNER JOIN qr_sessions ON qr_sessions.id = attendance_logs.session_id
                  INNER JOIN users ON users.id = attendance_logs.user_id
                  WHERE 1=1";

        // Dynamic Filtering
        if (!empty($startDate)) {
            $query .= " AND qr_sessions.date >= :start_date";
        }

        if (!empty($endDate)) {
            $query .= " AND qr_sessions.date <= :end_date";
        }

        if (!empty($searchName)) {
            $query .= " AND CONCAT(users.first_name, ' ', users.last_name) LIKE :search_name";
        }

        $query .= " ORDER BY qr_sessions.date DESC, attendance_logs.clock_in_at DESC";

        $stmt = $this->conn->prepare($query);

        if (!empty($startDate)) {
            $stmt->bindParam(':start_date', $startDate);
        }
        if (!empty($endDate)) {
            $stmt->bindParam(':end_date', $endDate);
        }

        if (!empty($searchName)) {
            $searchParam = "%{$searchName}%";
            $stmt->bindParam(':search_name', $searchParam);
        }

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
