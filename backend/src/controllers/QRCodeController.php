<?php
namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\QRCodeModel;
use App\Models\QRSessionModel;

class QRCodeController
{
    private QRCodeModel $qr;
    private QRSessionModel $session;
    private AuthMiddleware $auth;

    public function __construct()
    {
        $this->qr = new QRCodeModel();
        $this->session = new QRSessionModel();
        $this->auth = new AuthMiddleware();
    }

    // POST /api/qr-codes/generate (admin only)
    public function generate(array $input): void
    {
        $this->auth->requireAdmin();

        if (!isset($input['session_id'])) {
            jsonResponse([
                "success" => false,
                "message" => "session_id is required."
            ], 400);
        }

        $activeSession = $this->session->active();
        if (!$activeSession || (int)$activeSession['id'] !== (int)$input['session_id']) {
            jsonResponse([
                "success" => false,
                "message" => "QR codes can only be generated for the active session."
            ], 409);
        }

        $qr = $this->qr->generate((int)$input['session_id']);

        jsonResponse([
            "success" => true,
            "data" => $qr
        ], 201);
    }

    // GET /api/qr-codes/active (admin only)
    public function active(): void
    {
        $this->auth->requireAdmin();

        jsonResponse([
            "success" => true,
            "data" => $this->qr->active()
        ]);
    }

    // PATCH /api/qr-codes/use (admin only)
    public function use(array $input): void
    {
        $this->auth->requireAdmin();

        if (
            !isset($input['id']) ||
            !isset($input['user_id'])
        ) {
            jsonResponse([
                "success" => false,
                "message" => "Missing fields."
            ], 400);
        }

        $success = $this->qr->use(
            $input['id'],
            $input['user_id']
        );

        jsonResponse([
            "success" => $success
        ]);
    }
}
