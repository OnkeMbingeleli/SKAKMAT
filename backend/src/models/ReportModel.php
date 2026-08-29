<?php

namespace App\Models;

use PDO;

class ReportModel
{
    private ?PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db;
    }

    private function db(): PDO
    {
        if ($this->db === null) {
            $this->db = getDB();
        }

        return $this->db;
    }

    private function buildFilters(
        string $startDate,
        string $endDate,
        ?string $department,
        ?int $employeeId
    ): array {
        $where = ['qs.date BETWEEN ? AND ?'];
        $params = [$startDate, $endDate];

        if ($department) {
            $where[] = 'u.department = ?';
            $params[] = $department;
        }

        if ($employeeId) {
            $where[] = 'u.id = ?';
            $params[] = $employeeId;
        }

        return [$where, $params];
    }

    public function getSummary(
        string $startDate,
        string $endDate,
        ?string $department = null,
        ?int $employeeId = null
    ): array {
        [$where, $params] = $this->buildFilters(
            $startDate,
            $endDate,
            $department,
            $employeeId
        );

        $whereSql = implode(' AND ', $where);

        $sql = "SELECT
                    COUNT(al.id) AS attendance_count,
                    COALESCE(
                        SUM(
                            CASE
                                WHEN al.clock_in_at IS NOT NULL THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS total_checkins,
                    COALESCE(
                        SUM(
                            CASE
                                WHEN al.clock_out_at IS NOT NULL THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS total_checkouts,
                    COALESCE(
                        SUM(
                            CASE
                                WHEN TIME(al.clock_in_at) > qs.clock_in_deadline
                                THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS late_arrivals
                FROM attendance_logs al
                JOIN qr_sessions qs ON al.session_id = qs.id
                JOIN users u ON al.user_id = u.id
                WHERE $whereSql";

        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);

        $result = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $presence = $this->getPresenceSummary($department, $employeeId);

        return [
            'attendance_count' => (int)($result['attendance_count'] ?? 0),
            'total_checkins' => (int)($result['total_checkins'] ?? 0),
            'total_checkouts' => (int)($result['total_checkouts'] ?? 0),
            'late_arrivals' => (int)($result['late_arrivals'] ?? 0),
            'absentees' => $this->getAbsentCount(
                $startDate,
                $endDate,
                $department,
                $employeeId
            ),
            'onsite' => $presence['onsite'],
            'offsite' => max(
                0,
                $presence['staff_count'] - $presence['onsite']
            ),
        ];
    }

    /**
     * Daily attendance.
     *
     * One row per calendar day.
     */
    public function getDaily(
        string $startDate,
        string $endDate,
        ?string $department = null,
        ?int $employeeId = null
    ): array {
        [$where, $params] = $this->buildFilters(
            $startDate,
            $endDate,
            $department,
            $employeeId
        );

        $whereSql = implode(' AND ', $where);

        $sql = "SELECT
                    DATE_FORMAT(qs.date, '%Y-%m-%d') AS period,
                    MIN(qs.date) AS start_date,
                    MAX(qs.date) AS end_date,
                    COUNT(DISTINCT al.user_id) AS present_count,
                    COALESCE(
                        SUM(
                            CASE
                                WHEN al.clock_in_at IS NOT NULL THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS total_checkins,
                    COALESCE(
                        SUM(
                            CASE
                                WHEN al.clock_out_at IS NOT NULL THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS total_checkouts,
                    COALESCE(
                        SUM(
                            CASE
                                WHEN TIME(al.clock_in_at) > qs.clock_in_deadline
                                THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS late_arrivals
                FROM attendance_logs al
                JOIN qr_sessions qs ON al.session_id = qs.id
                JOIN users u ON al.user_id = u.id
                WHERE $whereSql
                GROUP BY qs.date
                ORDER BY qs.date ASC";

        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Weekly attendance.
     *
     * Uses aggregated date values so MySQL ONLY_FULL_GROUP_BY is satisfied.
     */
    public function getWeekly(
        string $startDate,
        string $endDate,
        ?string $department = null,
        ?int $employeeId = null
    ): array {
        [$where, $params] = $this->buildFilters(
            $startDate,
            $endDate,
            $department,
            $employeeId
        );

        $whereSql = implode(' AND ', $where);

        $sql = "SELECT
                    CONCAT(
                        YEAR(MIN(qs.date)),
                        '-W',
                        LPAD(WEEK(MIN(qs.date), 1), 2, '0')
                    ) AS period,
                    MIN(qs.date) AS start_date,
                    MAX(qs.date) AS end_date,
                    COUNT(DISTINCT al.user_id) AS present_count,
                    COALESCE(
                        SUM(
                            CASE
                                WHEN al.clock_in_at IS NOT NULL THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS total_checkins,
                    COALESCE(
                        SUM(
                            CASE
                                WHEN al.clock_out_at IS NOT NULL THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS total_checkouts,
                    COALESCE(
                        SUM(
                            CASE
                                WHEN TIME(al.clock_in_at) > qs.clock_in_deadline
                                THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS late_arrivals
                FROM attendance_logs al
                JOIN qr_sessions qs ON al.session_id = qs.id
                JOIN users u ON al.user_id = u.id
                WHERE $whereSql
                GROUP BY YEAR(qs.date), WEEK(qs.date, 1)
                ORDER BY
                    YEAR(qs.date) ASC,
                    WEEK(qs.date, 1) ASC";

        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Monthly attendance.
     *
     * Uses aggregated date values so MySQL ONLY_FULL_GROUP_BY is satisfied.
     */
    public function getMonthly(
        string $startDate,
        string $endDate,
        ?string $department = null,
        ?int $employeeId = null
    ): array {
        [$where, $params] = $this->buildFilters(
            $startDate,
            $endDate,
            $department,
            $employeeId
        );

        $whereSql = implode(' AND ', $where);

        $sql = "SELECT
                    CONCAT(
                        YEAR(MIN(qs.date)),
                        '-',
                        LPAD(MONTH(MIN(qs.date)), 2, '0')
                    ) AS period,
                    MIN(qs.date) AS start_date,
                    MAX(qs.date) AS end_date,
                    COUNT(DISTINCT al.user_id) AS present_count,
                    COALESCE(
                        SUM(
                            CASE
                                WHEN al.clock_in_at IS NOT NULL THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS total_checkins,
                    COALESCE(
                        SUM(
                            CASE
                                WHEN al.clock_out_at IS NOT NULL THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS total_checkouts,
                    COALESCE(
                        SUM(
                            CASE
                                WHEN TIME(al.clock_in_at) > qs.clock_in_deadline
                                THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS late_arrivals
                FROM attendance_logs al
                JOIN qr_sessions qs ON al.session_id = qs.id
                JOIN users u ON al.user_id = u.id
                WHERE $whereSql
                GROUP BY YEAR(qs.date), MONTH(qs.date)
                ORDER BY
                    YEAR(qs.date) ASC,
                    MONTH(qs.date) ASC";

        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAttendanceRows(
        string $startDate,
        string $endDate,
        ?string $department = null,
        ?int $employeeId = null,
        int $limit = 20,
        int $offset = 0
    ): array {
        [$where, $params] = $this->buildFilters(
            $startDate,
            $endDate,
            $department,
            $employeeId
        );

        $whereSql = implode(' AND ', $where);

        $sql = "SELECT
                    al.id,
                    u.id AS user_id,
                    u.first_name,
                    u.last_name,
                    u.department,
                    u.position,
                    qs.date,
                    al.clock_in_at,
                    al.clock_out_at,
                    al.status,
                    CASE
                        WHEN TIME(al.clock_in_at) > qs.clock_in_deadline
                        THEN 1
                        ELSE 0
                    END AS is_late
                FROM attendance_logs al
                JOIN qr_sessions qs ON al.session_id = qs.id
                JOIN users u ON al.user_id = u.id
                WHERE $whereSql
                ORDER BY
                    qs.date DESC,
                    u.last_name ASC,
                    u.first_name ASC
                LIMIT ? OFFSET ?";

        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAttendanceRows(
        string $startDate,
        string $endDate,
        ?string $department = null,
        ?int $employeeId = null
    ): int {
        [$where, $params] = $this->buildFilters(
            $startDate,
            $endDate,
            $department,
            $employeeId
        );

        $whereSql = implode(' AND ', $where);

        $sql = "SELECT COUNT(*) AS total
                FROM attendance_logs al
                JOIN qr_sessions qs ON al.session_id = qs.id
                JOIN users u ON al.user_id = u.id
                WHERE $whereSql";

        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    public function getTopEmployees(
        string $startDate,
        string $endDate,
        ?string $department = null,
        ?int $employeeId = null,
        int $limit = 10
    ): array {
        [$where, $params] = $this->buildFilters(
            $startDate,
            $endDate,
            $department,
            $employeeId
        );

        $whereSql = implode(' AND ', $where);

        $sql = "SELECT
                    u.id,
                    u.first_name,
                    u.last_name,
                    u.department,
                    u.position,
                    COUNT(al.id) AS attendance_count,
                    COALESCE(
                        SUM(
                            CASE
                                WHEN al.clock_in_at IS NOT NULL THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS total_checkins,
                    COALESCE(
                        SUM(
                            CASE
                                WHEN al.clock_out_at IS NOT NULL THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS total_checkouts,
                    COALESCE(
                        SUM(
                            CASE
                                WHEN TIME(al.clock_in_at) > qs.clock_in_deadline
                                THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS late_arrivals
                FROM attendance_logs al
                JOIN qr_sessions qs ON al.session_id = qs.id
                JOIN users u ON al.user_id = u.id
                WHERE $whereSql
                GROUP BY u.id
                ORDER BY
                    attendance_count DESC,
                    u.last_name ASC,
                    u.first_name ASC
                LIMIT ?";

        $params[] = $limit;

        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getAbsentCount(
        string $startDate,
        string $endDate,
        ?string $department = null,
        ?int $employeeId = null
    ): int {
        $sessionCountStmt = $this->db()->prepare(
            'SELECT COUNT(*)
             FROM qr_sessions
             WHERE date BETWEEN ? AND ?'
        );

        $sessionCountStmt->execute([
            $startDate,
            $endDate
        ]);

        if ((int)$sessionCountStmt->fetchColumn() === 0) {
            return 0;
        }

        $params = [];
        $where = ['u.role = "staff"'];

        if ($department) {
            $where[] = 'u.department = ?';
            $params[] = $department;
        }

        if ($employeeId) {
            $where[] = 'u.id = ?';
            $params[] = $employeeId;
        }

        $sql = "SELECT COUNT(*)
                FROM users u
                WHERE " . implode(' AND ', $where) . "
                AND NOT EXISTS (
                    SELECT 1
                    FROM attendance_logs al
                    JOIN qr_sessions qs ON al.session_id = qs.id
                    WHERE al.user_id = u.id
                    AND qs.date BETWEEN ? AND ?
                )";

        $params[] = $startDate;
        $params[] = $endDate;

        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    public function getStaffCount(
        ?string $department = null,
        ?int $employeeId = null
    ): int {
        $params = [];
        $where = ['u.role = "staff"'];

        if ($department) {
            $where[] = 'u.department = ?';
            $params[] = $department;
        }

        if ($employeeId) {
            $where[] = 'u.id = ?';
            $params[] = $employeeId;
        }

        $sql = 'SELECT COUNT(*)
                FROM users u
                WHERE ' . implode(' AND ', $where);

        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    public function getCurrentPresenceCount(
        ?string $department = null,
        ?int $employeeId = null
    ): int {
        return $this->getPresenceSummary(
            $department,
            $employeeId
        )['onsite'];
    }

    private function getPresenceSummary(
        ?string $department = null,
        ?int $employeeId = null
    ): array {
        $params = [];
        $where = ['u.role = "staff"'];

        if ($department) {
            $where[] = 'u.department = ?';
            $params[] = $department;
        }

        if ($employeeId) {
            $where[] = 'u.id = ?';
            $params[] = $employeeId;
        }

        $whereSql = implode(' AND ', $where);

        $sql = "SELECT
                    COUNT(DISTINCT u.id) AS staff_count,
                    COUNT(
                        DISTINCT CASE
                            WHEN al.status = 'clocked_in'
                            AND al.clock_in_at IS NOT NULL
                            AND al.clock_out_at IS NULL
                            THEN u.id
                        END
                    ) AS onsite
                FROM users u
                LEFT JOIN attendance_logs al
                    ON al.user_id = u.id
                WHERE $whereSql";

        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);

        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'staff_count' => (int)($row['staff_count'] ?? 0),
            'onsite' => (int)($row['onsite'] ?? 0),
        ];
    }
}
