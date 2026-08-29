<?php
namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\QRSessionModel;

class QRSessionController
{
    private QRSessionModel $session;
    private AuthMiddleware $auth;

    public function __construct()
    {
        $this->session = new QRSessionModel();
        $this->auth = new AuthMiddleware();
    }

    // POST /api/qr-sessions/enable (admin only)
    public function enable(array $input): void
    {
        // created_by is taken from the authenticated admin's token,
        // never trusted from the request body.
        $payload = $this->auth->requireAdmin();

        if (
            !isset($input['clock_in_deadline']) ||
            !isset($input['clock_out_deadline'])
        ) {
            jsonResponse([
                "success" => false,
                "message" => "Missing required fields."
            ], 400);
        }

        $id = $this->session->enable(
            $payload['user_id'],
            $input['clock_in_deadline'],
            $input['clock_out_deadline']
        );

        if (!$id) {
            jsonResponse([
                "success" => false,
                "message" => "Today's QR session already exists."
            ], 409);
        }

        jsonResponse([
            "success" => true,
            "session_id" => $id
        ], 201);
    }

    // PATCH /api/qr-sessions/{id}/disable (admin only)
    public function disable($id): void
    {
        $this->auth->requireAdmin();

        $success = $this->session->disable($id);

        jsonResponse([
            "success" => $success
        ]);
    }

    // GET /api/qr-sessions/active (any authenticated user)
    public function active(): void
    {
        $this->auth->requireLogin();

        jsonResponse([
            "success" => true,
            "data" => $this->session->active()
        ]);
    }
}
