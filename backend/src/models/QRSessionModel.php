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

    // Enable today's QR Session. If a session for today already exists
    // (even a disabled one, e.g. from an earlier Enable/Disable toggle),
    // reactivate it instead of failing — "Enable" should always just work.
    public function enable($createdBy, $clockInDeadline, $clockOutDeadline)
    {
        $check = $this->db->prepare("
            SELECT id, status
            FROM qr_sessions
            WHERE date = CURDATE()
            LIMIT 1
        ");
        $check->execute();
        $existing = $check->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $update = $this->db->prepare("
                UPDATE qr_sessions
                SET
                    status = 'active',
                    enabled_at = NOW(),
                    disabled_at = NULL,
                    clock_in_deadline = ?,
                    clock_out_deadline = ?
                WHERE id = ?
            ");
            $update->execute([$clockInDeadline, $clockOutDeadline, $existing['id']]);
            return (int)$existing['id'];
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

        return (int)$this->db->lastInsertId();
    }

    // Disable active session
    public function disable($id)
    {
        $this->db->beginTransaction();

        try {
            $sql = "
            UPDATE qr_sessions
            SET
                status='disabled',
                disabled_at=NOW()
            WHERE id=? AND status='active'
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);

            if ($stmt->rowCount() !== 1) {
                $this->db->rollBack();
                return false;
            }

            // Disabled sessions must not leave a previously displayed code
            // usable. `used` is supported by the existing QR-code schema.
            $expire = $this->db->prepare("\n                UPDATE qr_codes\n                SET status='used', used_at=NOW()\n                WHERE session_id=? AND status='active'\n            ");
            $expire->execute([$id]);

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    // Active session
    public function active()
    {
        $stmt = $this->db->query("
            SELECT *
            FROM qr_sessions
            WHERE status='active'
              AND date = CURDATE()
            ORDER BY id DESC
            LIMIT 1
        ");

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
