<?php
// Leave balances: reads the same tables updated by leave approval.
namespace App\Models;

use PDO;

class LeaveBalanceModel
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? getDB();
    }

    /**
     * Balances for one employee, one row per leave type.
     */
    public function getForUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT lt.code AS leave_type, lt.name AS leave_type_label,
                    COALESCE(elb.allocated_days, lt.default_days) AS allocated_days,
                    COALESCE(elb.used_days, 0) AS days_used,
                    COALESCE(elb.remaining_days, lt.default_days) AS days_remaining,
                    (lt.code = 'unpaid') AS unlimited,
                    CONCAT(YEAR(CURDATE()), '-01-01') AS cycle_start,
                    CONCAT(YEAR(CURDATE()), '-12-31') AS cycle_end
             FROM leave_types lt
             LEFT JOIN employee_leave_balances elb
               ON elb.leave_type = lt.code AND elb.user_id = ?
              AND elb.leave_year = YEAR(CURDATE())
             ORDER BY lt.id"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Balances for every staff member, grouped by user_id — admin overview.
     */
    public function getAll(): array
    {
        $stmt = $this->db->query(
            "SELECT u.id AS user_id, u.first_name, u.last_name,
                    lt.code AS leave_type,
                    COALESCE(elb.allocated_days, lt.default_days) AS allocated_days,
                    COALESCE(elb.used_days, 0) AS days_used,
                    COALESCE(elb.remaining_days, lt.default_days) AS days_remaining,
                    (lt.code = 'unpaid') AS unlimited
             FROM users u
             CROSS JOIN leave_types lt
             LEFT JOIN employee_leave_balances elb
               ON elb.leave_type = lt.code AND elb.user_id = u.id
              AND elb.leave_year = YEAR(CURDATE())
             WHERE u.role = 'staff'
             ORDER BY u.last_name, u.first_name, lt.id"
        );

        $byUser = [];
        foreach ($stmt->fetchAll() as $row) {
            $uid = $row['user_id'];
            if (!isset($byUser[$uid])) {
                $byUser[$uid] = [
                    'user_id'  => $uid,
                    'name'     => $row['first_name'] . ' ' . $row['last_name'],
                    'balances' => [],
                ];
            }
            $byUser[$uid]['balances'][] = $row;
        }
        return array_values($byUser);
    }

    /**
     * Remaining days for one employee, one leave type. Returns null for
     * unlimited types (unpaid) rather than a numeric "remaining" value.
     */
    public function getRemainingDays(int $userId, string $leaveType): ?float
    {
        $stmt = $this->db->prepare(
            "SELECT CASE WHEN lt.code = 'unpaid' THEN NULL
                         ELSE COALESCE(elb.remaining_days, lt.default_days) END
             FROM leave_types lt
             LEFT JOIN employee_leave_balances elb
               ON elb.leave_type = lt.code AND elb.user_id = ?
              AND elb.leave_year = YEAR(CURDATE())
             WHERE lt.code = ?"
        );
        $stmt->execute([$userId, $leaveType]);
        $val = $stmt->fetchColumn();
        if ($val === false) return 0.0;
        return $val === null ? null : (float)$val;
    }

    /**
     * Working days (excludes weekends + fixed-date SA public holidays)
     * between two dates, inclusive.
     */
    public function countWorkingDays(string $startDate, string $endDate): int
    {
        // Approval currently uses inclusive calendar days, so validation must
        // use the same calculation to avoid approving more than the balance.
        $stmt = $this->db->prepare("SELECT DATEDIFF(?, ?) + 1");
        $stmt->execute([$startDate, $endDate]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Whether a leave request for $userId/$leaveType/$startDate-$endDate
     * fits within their remaining balance. Unpaid leave is never blocked.
     * Call this before inserting a new leave_requests row.
     */
    public function hasSufficientBalance(int $userId, string $leaveType, string $startDate, string $endDate): bool
    {
        if ($leaveType === 'unpaid') {
            return true;
        }
        $remaining = $this->getRemainingDays($userId, $leaveType);
        if ($remaining === null) {
            return true; // unlimited type
        }
        $requested = $this->countWorkingDays($startDate, $endDate);
        return $requested <= $remaining;
    }
}
