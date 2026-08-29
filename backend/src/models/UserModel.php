<?php
namespace App\Models;

use PDO;

class UserModel
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

    /**
     * Get all users (no role filter).
     */
    public function getAllUsers(): array
    {
        $stmt = $this->db()->query("SELECT id, first_name, last_name, email, role, department, position, created_at, updated_at FROM users");
        return $stmt->fetchAll();
    }

    /**
     * Search and paginate users with optional attendance summary.
     */
    public function getUsers(array $filters = [], bool $withAttendance = false, int $limit = 20, int $offset = 0): array
    {
        $params = [];
        $where = ['1=1'];

        if (!empty($filters['role'])) {
            $where[] = 'u.role = ?';
            $params[] = $filters['role'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(CONCAT_WS(" ", u.first_name, u.last_name, u.email, u.department, u.position) LIKE ?)';
            $params[] = '%' . trim($filters['search']) . '%';
        }

        if (!empty($filters['department'])) {
            $where[] = 'u.department = ?';
            $params[] = $filters['department'];
        }

        if (!empty($filters['position'])) {
            $where[] = 'u.position = ?';
            $params[] = $filters['position'];
        }

        $whereSql = implode(' AND ', $where);

        if ($withAttendance) {
            $sql = "SELECT
                u.id, u.first_name, u.last_name, u.email, u.role, u.department, u.position,
                COALESCE(a.attendance_count, 0) AS attendance_count,
                COALESCE(a.total_checkins, 0) AS total_checkins,
                COALESCE(a.total_checkouts, 0) AS total_checkouts,
                COALESCE(a.late_arrivals, 0) AS late_arrivals,
                a.last_seen,
                CASE WHEN COALESCE(a.onsite, 0) = 1 THEN 'onsite' ELSE 'offsite' END AS status
            FROM users u
            LEFT JOIN (
                SELECT
                    al.user_id,
                    COUNT(al.id) AS attendance_count,
                    SUM(CASE WHEN al.clock_in_at IS NOT NULL THEN 1 ELSE 0 END) AS total_checkins,
                    SUM(CASE WHEN al.clock_out_at IS NOT NULL THEN 1 ELSE 0 END) AS total_checkouts,
                    SUM(CASE WHEN al.clock_in_at IS NOT NULL AND qs.clock_in_deadline IS NOT NULL AND TIME(al.clock_in_at) > qs.clock_in_deadline THEN 1 ELSE 0 END) AS late_arrivals,
                    MAX(qs.date) AS last_seen,
                    MAX(CASE WHEN al.status = 'clocked_in' AND al.clock_in_at IS NOT NULL AND al.clock_out_at IS NULL THEN 1 ELSE 0 END) AS onsite
                FROM attendance_logs al
                LEFT JOIN qr_sessions qs ON al.session_id = qs.id
                GROUP BY al.user_id
            ) a ON a.user_id = u.id
            WHERE $whereSql
            ORDER BY u.last_name ASC, u.first_name ASC
            LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            $stmt = $this->db()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        }

        $sql = "SELECT u.id, u.first_name, u.last_name, u.email, u.role, u.department, u.position, u.created_at, u.updated_at
            FROM users u
            WHERE $whereSql
            ORDER BY u.last_name ASC, u.first_name ASC
            LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countUsers(array $filters = []): int
    {
        $params = [];
        $where = ['1=1'];

        if (!empty($filters['role'])) {
            $where[] = 'role = ?';
            $params[] = $filters['role'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(CONCAT_WS(" ", first_name, last_name, email, department, position) LIKE ?)';
            $params[] = '%' . trim($filters['search']) . '%';
        }

        if (!empty($filters['department'])) {
            $where[] = 'department = ?';
            $params[] = $filters['department'];
        }

        if (!empty($filters['position'])) {
            $where[] = 'position = ?';
            $params[] = $filters['position'];
        }

        if (!empty($filters['id'])) {
            $where[] = 'id = ?';
            $params[] = $filters['id'];
        }

        $sql = 'SELECT COUNT(*) AS total FROM users WHERE ' . implode(' AND ', $where);
        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function getDepartments(?string $role = null): array
    {
        $stmt = $this->db()->query("SHOW COLUMNS FROM users LIKE 'department'");
        $column = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($column && preg_match("/^enum\\((.*)\\)$/", $column['Type'], $matches)) {
            $values = str_getcsv($matches[1], ',', "'");
            return array_values(array_filter($values, fn ($value) => $value !== ''));
        }

        $params = [];
        $where = ['department IS NOT NULL', 'department <> ""'];
        if ($role) {
            $where[] = 'role = ?';
            $params[] = $role;
        }

        $stmt = $this->db()->prepare('SELECT DISTINCT department FROM users WHERE ' . implode(' AND ', $where) . ' ORDER BY department ASC');
        $stmt->execute($params);
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'department');
    }

    public function getPositions(?string $role = null): array
    {
        $params = [];
        $where = ['position IS NOT NULL', 'position <> ""'];
        if ($role) {
            $where[] = 'role = ?';
            $params[] = $role;
        }

        $stmt = $this->db()->prepare('SELECT DISTINCT position FROM users WHERE ' . implode(' AND ', $where) . ' ORDER BY position ASC');
        $stmt->execute($params);
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'position');
    }

    public function getUserAttendanceSummary(int $id): array
    {
        $stmt = $this->db()->prepare(
            "SELECT
                COUNT(al.id) AS attendance_count,
                COALESCE(SUM(al.clock_in_at IS NOT NULL), 0) AS total_checkins,
                COALESCE(SUM(al.clock_out_at IS NOT NULL), 0) AS total_checkouts,
                COALESCE(SUM(CASE WHEN TIME(al.clock_in_at) > qs.clock_in_deadline THEN 1 ELSE 0 END), 0) AS late_arrivals,
                MAX(qs.date) AS last_seen
             FROM attendance_logs al
             JOIN qr_sessions qs ON al.session_id = qs.id
             WHERE al.user_id = ?"
        );
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'attendance_count' => (int)($result['attendance_count'] ?? 0),
            'total_checkins' => (int)($result['total_checkins'] ?? 0),
            'total_checkouts' => (int)($result['total_checkouts'] ?? 0),
            'late_arrivals' => (int)($result['late_arrivals'] ?? 0),
            'last_seen' => $result['last_seen'] ?? null,
            'status' => $this->getCurrentStatus($id),
        ];
    }

    /**
     * Get all staff members.
     */
    public function getAllStaff(): array
    {
        $stmt = $this->db()->prepare("SELECT id, first_name, last_name, email, role, department, position, created_at, updated_at FROM users WHERE role = 'staff'");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Find a user by email.
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db()->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Find a user by ID.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db()->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Create a new user. Returns the new user ID.
     */
    public function createUser(array $data): int
    {
        $stmt = $this->db()->prepare(
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
        return (int)$this->db()->lastInsertId();
    }

    /**
     * Get user profile (excludes password).
     */
    public function getUserProfile(int $id): ?array
    {
        $stmt = $this->db()->prepare("SELECT id, first_name, last_name, email, role, department, position, created_at, updated_at FROM users WHERE id = ?");
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
        $stmt = $this->db()->prepare("UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?");
        return $stmt->execute($params);
    }

    /**
     * Update password for a user.
     */
    public function updatePassword(int $id, string $hashedPassword): bool
    {
        $stmt = $this->db()->prepare("UPDATE users SET password = ? WHERE id = ?");
        return $stmt->execute([$hashedPassword, $id]);
    }

    public function deleteUser(int $id): bool
    {
        $stmt = $this->db()->prepare("DELETE FROM users WHERE id = ? AND role = 'staff'");
        return $stmt->execute([$id]);
    }

    private function getCurrentStatus(int $id): string
    {
        $stmt = $this->db()->prepare(
            "SELECT COUNT(*)
             FROM attendance_logs
             WHERE user_id = ?
               AND status = 'clocked_in'
               AND clock_in_at IS NOT NULL
               AND clock_out_at IS NULL"
        );
        $stmt->execute([$id]);
        return (int)$stmt->fetchColumn() > 0 ? 'onsite' : 'offsite';
    }
}
