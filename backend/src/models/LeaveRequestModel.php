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
        $stmt = $this->db->prepare("SELECT * FROM leave_requests WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Get all leave requests for a specific user, most recent first.
     */
    public function getByUser(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM leave_requests WHERE user_id = ? ORDER BY created_at DESC, id DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Get all leave requests (admin), newest first, joined with the
     * employee's name/department/email so the admin table doesn't need a
     * second round trip per row. Optionally filtered by status.
     */
    public function getAll(?string $status = null): array
    {
        $sql = "SELECT lr.*, u.first_name, u.last_name, u.email, u.department
                FROM leave_requests lr
                JOIN users u ON u.id = lr.user_id";
        $params = [];
        if ($status !== null) {
            $sql .= " WHERE lr.status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY lr.created_at DESC, lr.id DESC";
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
     * Change status and keep the employee balance plus audit ledger in sync.
     */
    public function updateStatus(int $id, string $status, int $performedBy): ?array
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT * FROM leave_requests WHERE id = ? FOR UPDATE');
            $stmt->execute([$id]);
            $leave = $stmt->fetch();
            if (!$leave || $leave['status'] !== 'pending') {
                $this->db->rollBack();
                return null;
            }

            $start = new \DateTimeImmutable($leave['start_date']);
            $end = new \DateTimeImmutable($leave['end_date']);
            $days = (float)$start->diff($end)->days + 1;
            $year = (int)$start->format('Y');

            $balance = $this->db->prepare(
                'SELECT * FROM employee_leave_balances WHERE user_id = ? AND leave_year = ? AND leave_type = ? FOR UPDATE'
            );
            $balance->execute([(int)$leave['user_id'], $year, $leave['leave_type']]);
            $current = $balance->fetch();
            if (!$current) {
                $default = $this->db->prepare('SELECT default_days FROM leave_types WHERE code = ?');
                $default->execute([$leave['leave_type']]);
                $create = $this->db->prepare(
                    'INSERT INTO employee_leave_balances (user_id, leave_year, leave_type, allocated_days, used_days) VALUES (?, ?, ?, ?, 0)'
                );
                $create->execute([
                    (int)$leave['user_id'], $year, $leave['leave_type'], (float)($default->fetchColumn() ?: 0),
                ]);
                $balance->execute([(int)$leave['user_id'], $year, $leave['leave_type']]);
                $current = $balance->fetch();
            }

            $previous = (float)$current['remaining_days'];
            $change = $status === 'approved' ? $days : 0.0;
            $new = $previous - $change;
            if ($status === 'approved' && $new < 0 && $leave['leave_type'] !== 'unpaid') {
                $this->db->rollBack();
                throw new \RuntimeException('Insufficient leave balance');
            }

            if ($status === 'approved') {
                $used = $this->db->prepare('UPDATE employee_leave_balances SET used_days = used_days + ? WHERE id = ?');
                $used->execute([$days, $current['id']]);
            }
            $update = $this->db->prepare('UPDATE leave_requests SET status = ? WHERE id = ?');
            $update->execute([$status, $id]);

            $ledger = $this->db->prepare(
                'INSERT INTO leave_balance_ledger
                 (user_id, leave_type, leave_year, request_id, change_days, previous_balance, new_balance, action_type, performed_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $ledger->execute([
                (int)$leave['user_id'], $leave['leave_type'], $year, $id, $change,
                $previous, $new, $status === 'approved' ? 'approval' : 'rejection', $performedBy,
            ]);
            $this->db->commit();
            return $this->getById($id);
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
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
