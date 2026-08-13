<?php
namespace App\Models;

use PDO;

class EmergencyLogModel
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? getDB();
    }

    /**
     * The one emergency currently in progress, if any.
     */
    public function getActive(): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM emergencies
            WHERE status = 'active'
            ORDER BY id DESC
            LIMIT 1
        ");

        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Start a new evacuation roll call.
     *
     * Snapshots every employee who is currently clocked in today and
     * creates a "not confirmed" emergency_logs row for each of them,
     * so the admin only ever has to mark safe the people who were
     * actually on site when the emergency started.
     */
    public function start(int $startedBy): array
    {
        if ($this->getActive()) {
            return [
                "success" => false,
                "message" => "An emergency is already active."
            ];
        }

        $this->db->beginTransaction();

        try {
            $insertEmergency = $this->db->prepare("
                INSERT INTO emergencies
                (
                    status,
                    started_by,
                    started_at
                )
                VALUES
                (
                    'active',
                    ?,
                    NOW()
                )
            ");

            $insertEmergency->execute([$startedBy]);

            $emergencyId = (int)$this->db->lastInsertId();

            // Snapshot everyone currently clocked in (today's active session)
            $snapshot = $this->db->prepare("
                INSERT INTO emergency_logs
                (
                    emergency_id,
                    status,
                    attendance_log_id
                )
                SELECT
                    ?,
                    'not confirmed',
                    attendance_logs.id
                FROM attendance_logs
                INNER JOIN qr_sessions
                    ON qr_sessions.id = attendance_logs.session_id
                WHERE attendance_logs.status = 'clocked_in'
                AND qr_sessions.date = CURDATE()
            ");

            $snapshot->execute([$emergencyId]);

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();

            return [
                "success" => false,
                "message" => "Could not start the emergency."
            ];
        }

        return [
            "success" => true,
            "message" => "Evacuation started.",
            "emergency_id" => $emergencyId,
            "roll_call" => $this->getRollCall($emergencyId)
        ];
    }

    /**
     * Roll call for a given emergency: every snapshotted employee plus
     * their current confirmation status, pulled straight from the DB.
     */
    public function getRollCall(int $emergencyId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                emergency_logs.id AS emergency_log_id,
                emergency_logs.status,
                emergency_logs.marked_safe_at,
                users.id AS user_id,
                users.first_name,
                users.last_name,
                users.department
            FROM emergency_logs
            INNER JOIN attendance_logs
                ON attendance_logs.id = emergency_logs.attendance_log_id
            INNER JOIN users
                ON users.id = attendance_logs.user_id
            WHERE emergency_logs.emergency_id = ?
            ORDER BY users.first_name, users.last_name
        ");

        $stmt->execute([$emergencyId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Confirmed / remaining / total counts for a roll call — handy for a
     * "3 of 8 confirmed safe" progress indicator on the frontend.
     */
    public function getSummary(int $emergencyId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) AS total,
                SUM(status = 'marked safe') AS confirmed
            FROM emergency_logs
            WHERE emergency_id = ?
        ");

        $stmt->execute([$emergencyId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $total = (int)($row['total'] ?? 0);
        $confirmed = (int)($row['confirmed'] ?? 0);

        return [
            "total" => $total,
            "confirmed" => $confirmed,
            "remaining" => $total - $confirmed
        ];
    }

    /**
     * Confirm a single employee as safe.
     */
    public function markSafe(int $emergencyLogId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE emergency_logs
            SET
                status = 'marked safe',
                marked_safe_at = NOW()
            WHERE id = ?
            AND status != 'marked safe'
        ");

        $stmt->execute([$emergencyLogId]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Check an emergency_logs row belongs to a given (active) emergency,
     * so an admin can't mark safe against a stale/ended emergency by id-guessing.
     */
    public function belongsToEmergency(int $emergencyLogId, int $emergencyId): bool
    {
        $stmt = $this->db->prepare("
            SELECT id
            FROM emergency_logs
            WHERE id = ?
            AND emergency_id = ?
            LIMIT 1
        ");

        $stmt->execute([$emergencyLogId, $emergencyId]);

        return (bool)$stmt->fetch();
    }

    /**
     * End the active emergency.
     */
    public function end(int $emergencyId, int $endedBy): bool
    {
        $stmt = $this->db->prepare("
            UPDATE emergencies
            SET
                status = 'inactive',
                ended_by = ?,
                ended_at = NOW()
            WHERE id = ?
            AND status = 'active'
        ");

        $stmt->execute([$endedBy, $emergencyId]);

        return $stmt->rowCount() > 0;
    }
}