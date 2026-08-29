<?php
namespace App\Models;

use PDO;

class QRCodeModel
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? getDB();
    }

    // Generate a new QR Code
    public function generate($sessionId)
    {
        $token = bin2hex(random_bytes(32));

        // A session has exactly one scannable code. This also invalidates an
        // earlier code when an admin re-enables the same daily session.
        $expire = $this->db->prepare("\n            UPDATE qr_codes\n            SET status = 'used', used_at = NOW()\n            WHERE session_id = ? AND status = 'active'\n        ");
        $expire->execute([$sessionId]);

        $sql = "INSERT INTO qr_codes
                (
                    session_id,
                    token,
                    status,
                    created_at
                )
                VALUES
                (
                    ?,
                    ?,
                    'active',
                    NOW()
                )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $sessionId,
            $token
        ]);

        return [
            "id" => $this->db->lastInsertId(),
            "token" => $token
        ];
    }

    // Get active QR Code
    public function active()
    {
        $sql = "
            SELECT qr_codes.*
            FROM qr_codes
            INNER JOIN qr_sessions
                ON qr_sessions.id = qr_codes.session_id
            WHERE qr_codes.status = 'active'
              AND qr_sessions.status = 'active'
              AND qr_sessions.date = CURDATE()
            ORDER BY qr_codes.id DESC
            LIMIT 1
        ";

        return $this->db->query($sql)->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // Find an active QR Code by token
    public function findByToken($token)
    {
        $sql = "
            SELECT qr_codes.*
            FROM qr_codes
            INNER JOIN qr_sessions
                ON qr_sessions.id = qr_codes.session_id
            WHERE qr_codes.token = ?
              AND qr_codes.status = 'active'
              AND qr_sessions.status = 'active'
              AND qr_sessions.date = CURDATE()
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$token]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // Mark QR Code as used
    public function use($id, $userId)
    {
        $sql = "
            UPDATE qr_codes
            SET
                status='used',
                used_by=?,
                used_at=NOW()
            WHERE id=? AND status='active'
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            $userId,
            $id
        ]);

        return $stmt->rowCount() === 1;
    }
}
