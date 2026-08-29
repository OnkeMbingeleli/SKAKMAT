<?php
namespace App\Models;

use PDO;

class EmployeeInsightModel
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? getDB();
    }

    public function getEmployeeSnapshot(int $userId): array
    {
        $user = $this->db->prepare('SELECT id, first_name, last_name, department, position FROM users WHERE id = ? AND role = "staff"');
        $user->execute([$userId]);
        $employee = $user->fetch() ?: null;
        if (!$employee) return [];

        $attendance = $this->db->prepare(
            "SELECT COUNT(*) AS records,
                    SUM(al.clock_in_at IS NOT NULL) AS checkins,
                    SUM(al.clock_out_at IS NOT NULL) AS checkouts,
                    SUM(TIME(al.clock_in_at) > qs.clock_in_deadline) AS late_arrivals
             FROM attendance_logs al
             JOIN qr_sessions qs ON qs.id = al.session_id
             WHERE al.user_id = ? AND qs.date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)"
        );
        $attendance->execute([$userId]);

        $leave = $this->db->prepare(
            "SELECT leave_type, status, start_date, end_date,
                    DATEDIFF(end_date, start_date) + 1 AS days
             FROM leave_requests
             WHERE user_id = ? AND start_date >= DATE_SUB(CURDATE(), INTERVAL 365 DAY)
             ORDER BY start_date DESC"
        );
        $leave->execute([$userId]);

        $balances = $this->db->prepare(
            "SELECT lt.code AS leave_type, lt.name AS leave_type_label,
                    COALESCE(elb.allocated_days, lt.default_days) AS allocated_days,
                    COALESCE(elb.used_days, 0) AS used_days,
                    COALESCE(elb.remaining_days, lt.default_days) AS remaining_days
             FROM leave_types lt
             LEFT JOIN employee_leave_balances elb
               ON elb.leave_type = lt.code AND elb.user_id = ? AND elb.leave_year = YEAR(CURDATE())
             ORDER BY lt.id"
        );
        $balances->execute([$userId]);

        $contract = $this->db->prepare(
            'SELECT id, start_date, end_date, contract_type, annual_leave_days, sick_leave_days, other_leave_days, notes FROM employee_contracts WHERE user_id = ? ORDER BY start_date DESC LIMIT 1'
        );
        $contract->execute([$userId]);

        $usedLeave = $this->db->prepare(
            "SELECT CASE
                        WHEN leave_type = 'annual' THEN 'annual'
                        WHEN leave_type = 'sick' THEN 'sick'
                        ELSE 'other'
                    END AS leave_type,
                    COALESCE(SUM(DATEDIFF(end_date, start_date) + 1), 0) AS approved_days
             FROM leave_requests
             WHERE user_id = ? AND status = 'approved' AND YEAR(start_date) = YEAR(CURDATE())
             GROUP BY CASE
                        WHEN leave_type = 'annual' THEN 'annual'
                        WHEN leave_type = 'sick' THEN 'sick'
                        ELSE 'other'
                    END"
        );
        $usedLeave->execute([$userId]);

        return [
            'employee' => $employee,
            'attendance' => $attendance->fetch() ?: [],
            'leave_requests' => $leave->fetchAll(),
            'leave_balances' => $balances->fetchAll(),
            'contract' => $contract->fetch() ?: null,
            'used_leave' => $usedLeave->fetchAll(),
        ];
    }

    public function saveContract(int $userId, array $data): array
    {
        $stmt = $this->db->prepare(
            'INSERT INTO employee_contracts (user_id, start_date, end_date, contract_type, annual_leave_days, sick_leave_days, other_leave_days, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $userId,
            $data['start_date'],
            $data['end_date'] ?: null,
            $data['contract_type'] ?: 'permanent',
            max(0, (float)$data['annual_leave_days']),
            max(0, (float)$data['sick_leave_days']),
            max(0, (float)$data['other_leave_days']),
            $data['notes'] ?: null,
        ]);
        return ['id' => (int)$this->db->lastInsertId()];
    }
}
