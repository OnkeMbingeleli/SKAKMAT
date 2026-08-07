<?php

class QRCodeModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // Generate a new QR Code
    public function generate($sessionId)
    {
        $token = bin2hex(random_bytes(32));

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
            SELECT *
            FROM qr_codes
            WHERE status='active'
            ORDER BY id DESC
            LIMIT 1
        ";

        return $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);
    }

    // Find an active QR Code by token
    public function findByToken($token)
    {
        $sql = "
            SELECT *
            FROM qr_codes
            WHERE token = ?
            AND status = 'active'
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$token]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
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
            WHERE id=?
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            $userId,
            $id
        ]);
    }
}