<?php
<<<<<<< HEAD

require_once __DIR__ . '/../models/QRSessionModel.php';
=======
namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\QRSessionModel;
>>>>>>> origin/PortReferencingUpdate

class QRSessionController
{
    private QRSessionModel $session;
<<<<<<< HEAD

    public function __construct(PDO $db)
    {
        $this->session = new QRSessionModel($db);
    }

    // POST
    public function enable(array $input)
    {
        if (
            !isset($input['created_by']) ||
=======
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
>>>>>>> origin/PortReferencingUpdate
            !isset($input['clock_in_deadline']) ||
            !isset($input['clock_out_deadline'])
        ) {
            jsonResponse([
                "success" => false,
                "message" => "Missing required fields."
            ], 400);
        }

        $id = $this->session->enable(
<<<<<<< HEAD
            $input['created_by'],
=======
            $payload['user_id'],
>>>>>>> origin/PortReferencingUpdate
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

<<<<<<< HEAD
    // PATCH
    public function disable($id)
    {
=======
    // PATCH /api/qr-sessions/{id}/disable (admin only)
    public function disable($id): void
    {
        $this->auth->requireAdmin();

>>>>>>> origin/PortReferencingUpdate
        $success = $this->session->disable($id);

        jsonResponse([
            "success" => $success
        ]);
    }

<<<<<<< HEAD
    // GET
    public function active()
    {
        jsonResponse(
            $this->session->active()
        );
    }
}
=======
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
>>>>>>> origin/PortReferencingUpdate
