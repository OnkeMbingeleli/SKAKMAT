<?php
namespace App\Services;

use PDO;

class SummaryBuilder
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Build an HTML summary for one user over a date range.
     * $from / $to are 'Y-m-d' strings.
     */
    public function build(array $user, string $from, string $to, string $periodLabel): string
    {
        $userId = $user['id'];

        // --- ASSUMED SCHEMA: attendance_logs(user_id, clock_in_at, clock_out_at) ---
        $stmt = $this->db->prepare(
            "SELECT clock_in_at, clock_out_at FROM attendance_logs
             WHERE user_id = ? AND DATE(clock_in_at_at) BETWEEN ? AND ?
             ORDER BY clock_in_at_at ASC"
        );
        $stmt->execute([$userId, $from, $to]);
        $attendance = $stmt->fetchAll();

        // --- ASSUMED SCHEMA: leave_requests(user_id, start_date, end_date, status, created_at) ---
        $stmt = $this->db->prepare(
            "SELECT start_date, end_date, status FROM leave_requests
             WHERE user_id = ? AND DATE(created_at) BETWEEN ? AND ?
             ORDER BY created_at DESC"
        );
        $stmt->execute([$userId, $from, $to]);
        $leaveRequests = $stmt->fetchAll();

        // --- ASSUMED SCHEMA: qr_sessions(user_id, created_at) ---
        $stmt = $this->db->prepare(
            "SELECT created_at FROM qr_sessions
             WHERE created_by = ? AND DATE(created_at) BETWEEN ? AND ?
             ORDER BY created_at DESC"
        );
        $stmt->execute([$userId, $from, $to]);
        $qrSessions = $stmt->fetchAll();

        $html = "<h2>Your {$periodLabel} summary</h2>";
        $html .= "<p>Hi " . htmlspecialchars($user['first_name']) . ", here's what happened between {$from} and {$to}:</p>";

        $html .= "<h3>Attendance</h3>";
        if (empty($attendance)) {
            $html .= "<p>No clock-ins recorded.</p>";
        } else {
            $html .= "<ul>";
            foreach ($attendance as $row) {
                $in = htmlspecialchars($row['clock_in_at']);
                $out = $row['clock_out_at'] ? htmlspecialchars($row['clock_out_at']) : 'still clocked in';
                $html .= "<li>In: {$in} — Out: {$out}</li>";
            }
            $html .= "</ul>";
        }

        $html .= "<h3>Leave requests</h3>";
        if (empty($leaveRequests)) {
            $html .= "<p>No leave requests submitted.</p>";
        } else {
            $html .= "<ul>";
            foreach ($leaveRequests as $row) {
                $html .= "<li>" . htmlspecialchars($row['start_date']) . " to " .
                    htmlspecialchars($row['end_date']) . " — " . htmlspecialchars($row['status']) . "</li>";
            }
            $html .= "</ul>";
        }

        $html .= "<h3>QR check-ins</h3>";
        if (empty($qrSessions)) {
            $html .= "<p>No QR sessions recorded.</p>";
        } else {
            $html .= "<ul>";
            foreach ($qrSessions as $row) {
                $html .= "<li>" . htmlspecialchars($row['created_at']) . "</li>";
            }
            $html .= "</ul>";
        }

        return $html;
    }
}