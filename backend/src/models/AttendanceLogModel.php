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
     * Scan QR. First scan of the day clocks the employee in; if they're
     * already clocked in for this session, the same scan clocks them out
     * instead (so "scan to sign in, scan again to sign out" just works).
     * Either way, the QR is rotated so the used code can't be reused.
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

        // Is this user already clocked in for this session?
        $check = $this->db->prepare("
            SELECT id, clock_in_at
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

        $openRecord = $check->fetch();

        // Rotate the QR regardless of which branch we take below — a used
        // code (whether for clock-in or clock-out) should never be scannable
        // again.
        if (!$this->qrModel->use($qr['id'], $userId)) {
            return [
                "success" => false,
                "message" => "This QR code has already been used. Scan the new code instead."
            ];
        }
        $nextQr = $this->qrModel->generate($qr['session_id']);

        if ($openRecord) {
            // ---- Second scan of the day: clock out ----
            $this->clockOut((int)$openRecord['id']);

            $durationStmt = $this->db->prepare("
                SELECT TIMEDIFF(clock_out_at, clock_in_at) AS duration
                FROM attendance_logs
                WHERE id = ?
            ");
            $durationStmt->execute([$openRecord['id']]);
            $duration = $durationStmt->fetchColumn();

            return [
                "success" => true,
                "action" => "clock_out",
                "message" => "Clock out successful. You were logged in for " . ($duration ?: '0:00:00') . ".",
                "attendance_id" => (int)$openRecord['id'],
                "duration" => $duration,
                "next_qr" => $nextQr
            ];
        }

        // ---- First scan of the day: clock in ----
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

        return [
            "success" => true,
            "action" => "clock_in",
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

    /**
     * Today's attendance record for one user, or null if they haven't
     * scanned yet today. Used by the Clock In/Out page to decide whether
     * the next scan should clock the person in or out.
     */
    public function getTodayRecord(int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT a.id, a.status, a.clock_in_at, a.clock_out_at
            FROM attendance_logs a
            JOIN qr_sessions q ON a.session_id = q.id
            WHERE a.user_id = ?
            AND q.date = CURDATE()
            ORDER BY a.id DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

}
