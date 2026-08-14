<?php
// Users: CRUD, auth lookups, filtered/paginated listing, and attendance-summary rollups.
namespace App\Models;

use PDO;

class UserModel
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? getDB();
    }

    /**
     * Get all users (no role filter).
     */
    public function getAllUsers(): array
    {
        $stmt = $this->db->query("SELECT id, first_name, last_name, email, role, department, position, created_at, updated_at FROM users");
        return $stmt->fetchAll();
    }

    /**
     * Get all staff members.
     */
    public function getAllStaff(): array
    {
        $stmt = $this->db->prepare("SELECT id, first_name, last_name, email, role, department, position, created_at, updated_at FROM users WHERE role = 'staff'");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Build the WHERE clause shared by getUsers()/countUsers().
     * Supported filters: role, search (name/email), department, position.
     */
    private function buildUserFilters(array $filters): array
    {
        $where = ['1 = 1'];
        $params = [];

        if (!empty($filters['role'])) {
            $where[] = 'role = ?';
            $params[] = $filters['role'];
        }

        if (!empty($filters['department'])) {
            $where[] = 'department = ?';
            $params[] = $filters['department'];
        }

        if (!empty($filters['position'])) {
            $where[] = 'position = ?';
            $params[] = $filters['position'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR CONCAT(first_name, " ", last_name) LIKE ?)';
            $like = '%' . $filters['search'] . '%';
            array_push($params, $like, $like, $like, $like);
        }

        return [implode(' AND ', $where), $params];
    }

    /**
     * Get a filtered, paginated list of users. When $withAttendance is true,
     * each row also carries attendance_count/total_checkins/total_checkouts/
     * late_arrivals aggregated from attendance_logs.
     */
    public function getUsers(array $filters = [], bool $withAttendance = false, int $limit = 20, int $offset = 0): array
    {
        [$whereSql, $params] = $this->buildUserFilters($filters);

        if ($withAttendance) {
            $sql = "SELECT u.id, u.first_name, u.last_name, u.email, u.role, u.department, u.position,
                        u.created_at, u.updated_at, 'active' AS status,
                        COUNT(al.id) AS attendance_count,
                        SUM(al.clock_in_at IS NOT NULL) AS total_checkins,
                        SUM(al.clock_out_at IS NOT NULL) AS total_checkouts,
                        SUM(CASE WHEN al.clock_in_at > qs.clock_in_deadline THEN 1 ELSE 0 END) AS late_arrivals
                    FROM users u
                    LEFT JOIN attendance_logs al ON al.user_id = u.id
                    LEFT JOIN qr_sessions qs ON qs.id = al.session_id
                    WHERE $whereSql
                    GROUP BY u.id
                    ORDER BY u.last_name ASC, u.first_name ASC
                    LIMIT ? OFFSET ?";
        } else {
            $sql = "SELECT id, first_name, last_name, email, role, department, position, created_at, updated_at, 'active' AS status
                    FROM users u
                    WHERE $whereSql
                    ORDER BY last_name ASC, first_name ASC
                    LIMIT ? OFFSET ?";
        }

        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Count users matching the same filters as getUsers(), for pagination.
     */
    public function countUsers(array $filters = []): int
    {
        [$whereSql, $params] = $this->buildUserFilters($filters);
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE $whereSql");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Distinct departments currently in use (for filter dropdowns).
     */
    public function getDepartments(): array
    {
        $stmt = $this->db->query("SELECT DISTINCT department FROM users WHERE department IS NOT NULL ORDER BY department ASC");
        return array_column($stmt->fetchAll(), 'department');
    }

    /**
     * Distinct positions currently in use (for filter dropdowns).
     */
    public function getPositions(): array
    {
        $stmt = $this->db->query("SELECT DISTINCT position FROM users WHERE position IS NOT NULL AND position <> '' ORDER BY position ASC");
        return array_column($stmt->fetchAll(), 'position');
    }

    /**
     * Attendance rollup for a single user (used by the employee detail panel).
     */
    public function getUserAttendanceSummary(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                COUNT(al.id) AS attendance_count,
                SUM(al.clock_in_at IS NOT NULL) AS total_checkins,
                SUM(al.clock_out_at IS NOT NULL) AS total_checkouts,
                SUM(CASE WHEN al.clock_in_at > qs.clock_in_deadline THEN 1 ELSE 0 END) AS late_arrivals,
                MAX(COALESCE(al.clock_out_at, al.clock_in_at)) AS last_seen
             FROM attendance_logs al
             JOIN qr_sessions qs ON qs.id = al.session_id
             WHERE al.user_id = ?"
        );
        $stmt->execute([$userId]);
        $result = $stmt->fetch() ?: [];

        return [
            'attendance_count' => (int)($result['attendance_count'] ?? 0),
            'total_checkins'   => (int)($result['total_checkins'] ?? 0),
            'total_checkouts'  => (int)($result['total_checkouts'] ?? 0),
            'late_arrivals'    => (int)($result['late_arrivals'] ?? 0),
            'last_seen'        => $result['last_seen'] ?? null,
        ];
    }

    /**
     * Find a user by email.
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Find a user by ID.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Create a new user. Returns the new user ID.
     */
    public function createUser(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO users (first_name, last_name, email, role, department, position, password)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['role'],
            $data['department'],
            $data['position'],
            password_hash($data['password'], PASSWORD_BCRYPT),
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Get user profile (excludes password).
     */
    public function getUserProfile(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT id, first_name, last_name, email, role, department, position, created_at, updated_at FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Update user fields (except password).
     */
    public function updateUser(int $id, array $data): bool
    {
        $allowed = ['first_name', 'last_name', 'email', 'department', 'position', 'role'];
        $fields = [];
        $params = [];
        foreach ($allowed as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = "$col = ?";
                $params[] = $data[$col];
            }
        }
        if (empty($fields)) return false;
        $params[] = $id;
        $stmt = $this->db->prepare("UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?");
        return $stmt->execute($params);
    }

    /**
     * Update password for a user.
     */
    public function updatePassword(int $id, string $hashedPassword): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
        return $stmt->execute([$hashedPassword, $id]);
    }
}
