<?php
namespace App\Models;

use PDO;

class AttendanceLogModel
{
    private PDO $db;
    private QRCodeModel $qrModel;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? getDB();
        $this->qrModel = new QRCodeModel($this->db);
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

        $attendanceId = (int)$this->db->lastInsertId();

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
            "attendance_id" => $attendanceId,
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
     * Check whether an attendance record belongs to a given user
     * (used to stop staff from clocking each other out).
     */
    public function belongsToUser(int $attendanceId, int $userId): bool
    {
        $stmt = $this->db->prepare("
            SELECT id
            FROM attendance_logs
            WHERE id = ?
            AND user_id = ?
            LIMIT 1
        ");

        $stmt->execute([$attendanceId, $userId]);

        return (bool)$stmt->fetch();
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

    public function getAllAttendanceRecords()
    {
        $stmt = $this->db->prepare("
            SELECT 
                CONCAT(u.first_name, ' ', u.last_name) AS NAME,
                u.department AS DEPARTMENT,
                q.date AS DATE,
                a.clock_in_at AS 'CHECK IN',
                a.clock_out_at AS 'CHECK OUT',
                TIMEDIFF(a.clock_out_at, a.clock_in_at) AS 'HOURS WORKED',
                CASE
                    WHEN a.clock_in_at IS NULL THEN 'NOT CLOCKED IN'
                    WHEN TIME(a.clock_in_at) <= q.clock_in_deadline THEN 'ON TIME'
                    WHEN TIME(a.clock_in_at) > q.clock_in_deadline THEN 'LATE'
                END AS STATUS

            FROM `railway`.`attendance_logs` a
            JOIN `railway`.`users` u
                ON a.user_id = u.id
            JOIN `railway`.`qr_sessions` q 
                ON a.session_id = q.id
            ORDER BY q.date DESC;
        ");

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getMyAttendanceRecords(int $userId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                CONCAT(u.first_name, ' ', u.last_name) AS NAME,
                u.department AS DEPARTMENT,
                q.date AS DATE,
                a.clock_in_at AS 'CHECK IN',
                a.clock_out_at AS 'CHECK OUT',
                TIMEDIFF(a.clock_out_at, a.clock_in_at) AS 'HOURS WORKED',
                CASE
                    WHEN a.clock_in_at IS NULL THEN 'NOT CLOCKED IN'
                    WHEN TIME(a.clock_in_at) <= q.clock_in_deadline THEN 'ON TIME'
                    WHEN TIME(a.clock_in_at) > q.clock_in_deadline THEN 'LATE'
                END AS STATUS

            FROM `railway`.`attendance_logs` a
            JOIN `railway`.`users` u
                ON a.user_id = u.id
            JOIN `railway`.`qr_sessions` q 
                ON a.session_id = q.id
            WHERE a.user_id = ?    
            ORDER BY q.date DESC;
        ");

        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}
