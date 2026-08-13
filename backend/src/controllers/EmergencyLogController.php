<?php
namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\EmergencyLogModel;

class EmergencyLogController
{
    private EmergencyLogModel $emergency;
    private AuthMiddleware $auth;

    public function __construct()
    {
        $this->emergency = new EmergencyLogModel();
        $this->auth = new AuthMiddleware();
    }

    /**
     * GET /api/emergency/active (authenticated)
     *
     * What the Emergency page should call on load, and it's safe to
     * poll — it's always a fresh read straight from the database, so
     * two admins on the page at once will never see stale data.
     *
     * No active emergency:
     * {
     *     "success": true,
     *     "data": null
     * }
     *
     * Active emergency:
     * {
     *     "success": true,
     *     "data": {
     *         "emergency": {
     *             "id": 4,
     *             "status": "active",
     *             "started_by": 1,
     *             "started_at": "2026-08-13 10:44:00",
     *             "ended_by": null,
     *             "ended_at": null
     *         },
     *         "summary": {
     *             "total": 5,
     *             "confirmed": 2,
     *             "remaining": 3
     *         },
     *         "roll_call": [
     *             {
     *                 "emergency_log_id": 12,
     *                 "status": "not confirmed",
     *                 "marked_safe_at": null,
     *                 "user_id": 7,
     *                 "first_name": "Thabo",
     *                 "last_name": "Nkosi",
     *                 "department": "Engineering"
     *             }
     *         ]
     *     }
     * }
     *
     * "data" is null vs an object is the switch the frontend uses to
     * decide between the "No active emergency" banner and the roll
     * call table. Each roll_call row's "status" is exactly
     * "not confirmed" or "marked safe" (matches the DB enum verbatim,
     * spaces included) — safe to render directly or map to a badge.
     */
    public function active(): void
    {
        $this->auth->requireLogin();

        $active = $this->emergency->getActive();

        if (!$active) {
            jsonResponse([
                "success" => true,
                "data" => null
            ]);
        }

        $emergencyId = (int)$active['id'];

        jsonResponse([
            "success" => true,
            "data" => [
                "emergency" => $active,
                "summary" => $this->emergency->getSummary($emergencyId),
                "roll_call" => $this->emergency->getRollCall($emergencyId)
            ]
        ]);
    }

    /**
     * POST /api/emergency/start (admin only)
     *
     * No body needed. Snapshots every employee currently clocked in
     * today as "not confirmed" — the roll call only ever contains
     * people who were actually on site.
     *
     * Success (201):
     * {
     *     "success": true,
     *     "message": "Evacuation started.",
     *     "emergency_id": 4,
     *     "summary": { "total": 5, "confirmed": 0, "remaining": 5 },
     *     "roll_call": [ ... same shape as GET /emergency/active ... ]
     * }
     *
     * Failure (400) — an emergency is already running:
     * {
     *     "success": false,
     *     "message": "An emergency is already active."
     * }
     *
     * The frontend should disable/hide "Start evacuation" while
     * GET /emergency/active is already returning data, so this 400
     * should only really surface as a race-condition guard.
     */
    public function start(): void
    {
        $payload = $this->auth->requireAdmin();

        $result = $this->emergency->start((int)$payload['user_id']);

        if (!$result["success"]) {
            jsonResponse($result, 400);
        }

        $emergencyId = (int)$result['emergency_id'];
        $result['summary'] = $this->emergency->getSummary($emergencyId);

        jsonResponse($result, 201);
    }

    /**
     * POST /api/emergency/mark-safe (admin only)
     *
     * Request body:
     * {
     *     "emergency_log_id": 12
     * }
     *
     * Use the "emergency_log_id" from a roll_call row (not the user_id)
     * — that's what ties the confirmation to this specific emergency,
     * so marking safe never bleeds into a different/past emergency.
     *
     * Success:
     * {
     *     "success": true,
     *     "summary": { "total": 5, "confirmed": 3, "remaining": 2 },
     *     "roll_call": [ ... full refreshed roll call ... ]
     * }
     *
     * Returning the whole roll_call means the frontend can just
     * replace its list wholesale after a click — no manual row
     * patching needed.
     *
     * Errors:
     * 400 { "success": false, "message": "emergency_log_id is required." }
     * 400 { "success": false, "message": "There is no active emergency." }
     * 404 { "success": false, "message": "That employee is not part of the active emergency." }
     */
    public function markSafe(array $input): void
    {
        $this->auth->requireAdmin();

        if (!isset($input['emergency_log_id'])) {
            jsonResponse([
                "success" => false,
                "message" => "emergency_log_id is required."
            ], 400);
        }

        $active = $this->emergency->getActive();

        if (!$active) {
            jsonResponse([
                "success" => false,
                "message" => "There is no active emergency."
            ], 400);
        }

        $emergencyId = (int)$active['id'];
        $emergencyLogId = (int)$input['emergency_log_id'];

        if (!$this->emergency->belongsToEmergency($emergencyLogId, $emergencyId)) {
            jsonResponse([
                "success" => false,
                "message" => "That employee is not part of the active emergency."
            ], 404);
        }

        $this->emergency->markSafe($emergencyLogId);

        jsonResponse([
            "success" => true,
            "summary" => $this->emergency->getSummary($emergencyId),
            "roll_call" => $this->emergency->getRollCall($emergencyId)
        ]);
    }

    /**
     * POST /api/emergency/end (admin only)
     *
     * Request body:
     * {
     *     "emergency_id": 4
     * }
     *
     * Success:
     * {
     *     "success": true,
     *     "message": "Evacuation ended."
     * }
     *
     * Failure (400) — already ended, or id doesn't match an active one:
     * {
     *     "success": false,
     *     "message": "Could not end the emergency."
     * }
     *
     * After this call, GET /emergency/active will go back to
     * { "data": null } — that's the frontend's cue to swap back to
     * the "No active emergency" state.
     */
    public function end(array $input): void
    {
        $payload = $this->auth->requireAdmin();

        if (!isset($input['emergency_id'])) {
            jsonResponse([
                "success" => false,
                "message" => "emergency_id is required."
            ], 400);
        }

        $success = $this->emergency->end(
            (int)$input['emergency_id'],
            (int)$payload['user_id']
        );

        if (!$success) {
            jsonResponse([
                "success" => false,
                "message" => "Could not end the emergency."
            ], 400);
        }

        jsonResponse([
            "success" => true,
            "message" => "Evacuation ended."
        ]);
    }
}