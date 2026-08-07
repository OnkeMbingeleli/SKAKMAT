<?php
namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\QRCodeModel;

class QRCodeController
{
    private QRCodeModel $qr;
    private AuthMiddleware $auth;

    public function __construct()
    {
        $this->qr = new QRCodeModel();
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

        $qr = $this->qr->generate($input['session_id']);

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
