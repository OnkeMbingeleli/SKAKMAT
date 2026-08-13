<?php
namespace App\Models;

use PDO;

class QRSessionModel
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? getDB();
    }

    // Enable today's QR Session
    public function enable($createdBy, $clockInDeadline, $clockOutDeadline)
    {
        // Check if today's session already exists
        $check = $this->db->prepare("
            SELECT id
            FROM qr_sessions
            WHERE date = CURDATE()
            LIMIT 1
        ");

        $check->execute();

        if ($check->fetch()) {
            return false;
        }

        $sql = "
            INSERT INTO qr_sessions
            (
                created_by,
                date,
                status,
                enabled_at,
                clock_in_deadline,
                clock_out_deadline,
                created_at
            )
            VALUES
            (
                ?,
                CURDATE(),
                'active',
                NOW(),
                ?,
                ?,
                NOW()
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            $createdBy,
            $clockInDeadline,
            $clockOutDeadline
        ]);

        return $this->db->lastInsertId();
    }

    // Disable active session
    public function disable($id)
    {
        $sql = "
            UPDATE qr_sessions
            SET
                status='disabled',
                disabled_at=NOW()
            WHERE id=?
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([$id]);
    }

    // Active session
    public function active()
    {
        $stmt = $this->db->query("
            SELECT *
            FROM qr_sessions
            WHERE status='active'
            LIMIT 1
        ");

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
