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

    /**
     * Get leave requests for a single user.
     */
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

    /**
     * Get all leave requests, optionally filtered by status.
     */
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

    /**
     * Create a new pending leave request.
     */
    public function create(array $data): array
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
            $data['reason'] ?: null,
        ]);

        return $this->findById((int)$this->db->lastInsertId());
    }

    /**
     * Update the status of a leave request.
     * Only a pending request can be approved or rejected.
     */
    public function updateStatus(int $id, string $status, int $performedBy): ?array
    {
        $this->db->beginTransaction();
        try {
            $request = $this->db->prepare('SELECT * FROM leave_requests WHERE id = ? FOR UPDATE');
            $request->execute([$id]);
            $leave = $request->fetch();
            if (!$leave || $leave['status'] !== 'pending') {
                $this->db->rollBack();
                return null;
            }

            $days = (float)(new \DateTimeImmutable($leave['start_date']))
                ->diff(new \DateTimeImmutable($leave['end_date']))->days + 1;
            $year = (int)(new \DateTimeImmutable($leave['start_date']))->format('Y');

            $balance = $this->db->prepare(
                'SELECT * FROM employee_leave_balances WHERE user_id = ? AND leave_year = ? AND leave_type = ? FOR UPDATE'
            );
            $balance->execute([(int)$leave['user_id'], $year, $leave['leave_type']]);
            $current = $balance->fetch();

            if (!$current) {
                $default = $this->db->prepare('SELECT default_days FROM leave_types WHERE code = ?');
                $default->execute([$leave['leave_type']]);
                $allocated = (float)($default->fetchColumn() ?: 0);
                $create = $this->db->prepare(
                    'INSERT INTO employee_leave_balances (user_id, leave_year, leave_type, allocated_days, used_days) VALUES (?, ?, ?, ?, 0)'
                );
                $create->execute([(int)$leave['user_id'], $year, $leave['leave_type'], $allocated]);
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
                $update = $this->db->prepare(
                    'UPDATE employee_leave_balances SET used_days = used_days + ? WHERE id = ?'
                );
                $update->execute([$days, $current['id']]);
            }

            $updateRequest = $this->db->prepare('UPDATE leave_requests SET status = ? WHERE id = ?');
            $updateRequest->execute([$status, $id]);
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
            return $this->findById($id);
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

    public function getBalance(int $userId, int $year): array
    {
        $stmt = $this->db->prepare(
            "SELECT lt.code AS leave_type, lt.name AS leave_type_label,
                    COALESCE(elb.allocated_days, lt.default_days) AS allocated_days,
                    COALESCE(elb.used_days, 0) AS used_days,
                    COALESCE(elb.remaining_days, lt.default_days) AS remaining_days
             FROM leave_types lt
             LEFT JOIN employee_leave_balances elb
               ON elb.leave_type = lt.code AND elb.user_id = ? AND elb.leave_year = ?
             ORDER BY lt.id"
        );
        $stmt->execute([$userId, $year]);
        return $stmt->fetchAll();
    }

    /**
     * Find a leave request by ID, including user details.
     */
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
            'id'              => (int)$row['id'],
            'user_id'         => (int)$row['user_id'],
            'employee'        => trim($row['first_name'] . ' ' . $row['last_name']),
            'department'      => $row['department'],
            'position'        => $row['position'],
            'leave_type'      => $row['leave_type'],
            'leave_type_label'=> $this->labelType($row['leave_type']),
            'start_date'      => $row['start_date'],
            'end_date'        => $row['end_date'],
            'days'            => $days,
            'reason'          => $row['reason'] ?? '',
            'status'          => $row['status'] ?? 'pending',
            'status_label'    => ucfirst($row['status'] ?? 'pending'),
            'created_at'      => $row['created_at'],
            'updated_at'      => $row['updated_at'],
        ];
    }

    private function labelType(string $type): string
    {
        return match ($type) {
            'family responsibility' => 'Family Responsibility',
            'study leave'           => 'Study',
            'maternity leave'       => 'Maternity',
            'paternity leave'       => 'Paternity',
            default                 => ucwords($type),
        };
    }
}
