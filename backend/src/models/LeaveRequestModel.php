<?php
namespace App\Models;

use PDO;

class LeaveRequestModel
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? getDB();
    }

    /**
     * Create a new leave request.
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO leave_requests (user_id, leave_type, start_date, end_date, reason, status)
             VALUES (?, ?, ?, ?, ?, 'pending')"
        );
        $stmt->execute([
            $data['user_id'],
            $data['leave_type'],
            $data['start_date'],
            $data['end_date'],
            $data['reason'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Get a single leave request by ID.
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT lr.*, COALESCE(CONCAT(u.first_name, ' ', u.last_name), 'Unknown User') AS user_name
             FROM leave_requests lr
             LEFT JOIN users u ON u.id = lr.user_id
             WHERE lr.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Get all leave requests for a specific user.
     */
    public function getByUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT lr.*, COALESCE(CONCAT(u.first_name, ' ', u.last_name), 'Unknown User') AS user_name
             FROM leave_requests lr
             LEFT JOIN users u ON u.id = lr.user_id
             WHERE lr.user_id = ?"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Get all leave requests (admin).
     */
    public function getAll(?string $status = null): array
    {
        $sql = "SELECT lr.*, COALESCE(CONCAT(u.first_name, ' ', u.last_name), 'Unknown User') AS user_name
                FROM leave_requests lr
                LEFT JOIN users u ON u.id = lr.user_id";

        $params = [];
        if ($status !== null) {
            $sql .= " WHERE lr.status = ?";
            $params[] = $status;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Update a leave request.
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [];
        foreach (['leave_type', 'start_date', 'end_date', 'reason', 'status'] as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = "$col = ?";
                $params[] = $data[$col];
            }
        }
        if (empty($fields)) return false;
        $params[] = $id;
        $stmt = $this->db->prepare("UPDATE leave_requests SET " . implode(', ', $fields) . " WHERE id = ?");
        return $stmt->execute($params);
    }

    /**
     * Delete a leave request (optional).
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM leave_requests WHERE id = ?");
        return $stmt->execute([$id]);
    }
}