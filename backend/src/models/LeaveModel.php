<?php
namespace App\Models;

use PDO;

class LeaveModel
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? getDB();
    }

    public function getByUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT lr.*, u.first_name, u.last_name, u.department, u.position
             FROM leave_requests lr
             INNER JOIN users u ON u.id = lr.user_id
             WHERE lr.user_id = ?
             ORDER BY lr.created_at DESC, lr.id DESC"
        );
        $stmt->execute([$userId]);
        return $this->formatRows($stmt->fetchAll());
    }

    public function getAll(?string $status = null): array
    {
        $sql = "SELECT lr.*, u.first_name, u.last_name, u.department, u.position
                FROM leave_requests lr
                INNER JOIN users u ON u.id = lr.user_id";
        $params = [];

        if ($status !== null) {
            $sql .= " WHERE lr.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY lr.created_at DESC, lr.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $this->formatRows($stmt->fetchAll());
    }

    public function create(array $data): array
    {
        $this->db->beginTransaction();

        try {
            $nextIdStmt = $this->db->query("SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM leave_requests FOR UPDATE");
            $nextId = (int)$nextIdStmt->fetch()['next_id'];

            $stmt = $this->db->prepare(
                "INSERT INTO leave_requests (id, user_id, leave_type, start_date, end_date, reason, status)
                 VALUES (?, ?, ?, ?, ?, ?, 'pending')"
            );
            $stmt->execute([
                $nextId,
                $data['user_id'],
                $data['leave_type'],
                $data['start_date'],
                $data['end_date'],
                $data['reason'] ?: null,
            ]);

            $this->db->commit();
            return $this->findById($nextId);
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateStatus(int $id, string $status): ?array
    {
        $stmt = $this->db->prepare("UPDATE leave_requests SET status = ? WHERE id = ? AND status = 'pending'");
        $stmt->execute([$status, $id]);

        if ($stmt->rowCount() === 0) {
            return $this->findById($id);
        }

        return $this->findById($id);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT lr.*, u.first_name, u.last_name, u.department, u.position
             FROM leave_requests lr
             INNER JOIN users u ON u.id = lr.user_id
             WHERE lr.id = ?"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        return $this->formatRow($row);
    }

    private function formatRows(array $rows): array
    {
        return array_map(fn ($row) => $this->formatRow($row), $rows);
    }

    private function formatRow(array $row): array
    {
        $start = new \DateTimeImmutable($row['start_date']);
        $end = new \DateTimeImmutable($row['end_date']);
        $days = $start->diff($end)->days + 1;

        return [
            'id' => (int)$row['id'],
            'user_id' => (int)$row['user_id'],
            'employee' => trim($row['first_name'] . ' ' . $row['last_name']),
            'department' => $row['department'],
            'position' => $row['position'],
            'leave_type' => $row['leave_type'],
            'leave_type_label' => $this->labelType($row['leave_type']),
            'start_date' => $row['start_date'],
            'end_date' => $row['end_date'],
            'days' => $days,
            'reason' => $row['reason'] ?? '',
            'status' => $row['status'] ?? 'pending',
            'status_label' => ucfirst($row['status'] ?? 'pending'),
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
        ];
    }

    private function labelType(string $type): string
    {
        return match ($type) {
            'family responsibility' => 'Family Responsibility',
            'study leave' => 'Study',
            'maternity leave' => 'Maternity',
            'paternity leave' => 'Paternity',
            default => ucwords($type),
        };
    }
}
