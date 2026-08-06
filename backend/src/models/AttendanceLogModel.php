<?php

require_once __DIR__ . '/QRCodeModel.php';

class AttendanceLogModel
{
    private PDO $db;
    private QRCodeModel $qrModel;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->qrModel = new QRCodeModel($db);
    }

    /**
     * Scan QR, clock in employee and rotate QR
     */
    public function scan(string $token, int $userId)
    {
        // Find active QR
        $qr = $this->qrModel->findByToken($token);

        if (!$qr) {
            return [
                "success" => false,
                "message" => "Invalid or expired QR code."
            ];
        }

        // Prevent duplicate clock-ins
        $check = $this->db->prepare("
            SELECT id
            FROM attendance_logs
            WHERE
                user_id = ?
            AND session_id = ?
            AND status = 'clocked_in'
            LIMIT 1
        ");

        $check->execute([
            $userId,
            $qr['session_id']
        ]);

        if ($check->fetch()) {
            return [
                "success" => false,
                "message" => "You have already clocked in."
            ];
        }

        // Create attendance record
        $insert = $this->db->prepare("
            INSERT INTO attendance_logs
            (
                user_id,
                session_id,
                qr_code_id,
                clock_in_at,
                status
            )
            VALUES
            (
                ?,
                ?,
                ?,
                NOW(),
                'clocked_in'
            )
        ");

        $insert->execute([
            $userId,
            $qr['session_id'],
            $qr['id']
        ]);

        // Mark current QR as used
        $this->qrModel->use(
            $qr['id'],
            $userId
        );

        // Generate next QR automatically
        $nextQr = $this->qrModel->generate(
            $qr['session_id']
        );

        return [
            "success" => true,
            "message" => "Clock in successful.",
            "attendance_id" => $this->db->lastInsertId(),
            "next_qr" => $nextQr
        ];
    }

    /**
     * Clock out
     */
    public function clockOut($attendanceId)
    {
        $stmt = $this->db->prepare("
            UPDATE attendance_logs
            SET
                status='clocked_out',
                clock_out_at=NOW()
            WHERE id=?
        ");

        return $stmt->execute([$attendanceId]);
    }

    /**
     * Present employees
     */
    public function getPresentEmployees($sessionId)
    {
        $stmt = $this->db->prepare("
            SELECT
                attendance_logs.id,
                users.id AS user_id,
                users.first_name,
                users.last_name,
                users.department,
                attendance_logs.clock_in_at
            FROM attendance_logs
            INNER JOIN users
                ON users.id = attendance_logs.user_id
            WHERE
                attendance_logs.session_id = ?
            AND attendance_logs.status='clocked_in'
        ");

        $stmt->execute([$sessionId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}