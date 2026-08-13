<?php
<<<<<<< HEAD

require_once __DIR__ . '/../models/QRCodeModel.php';
=======
namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\QRCodeModel;
>>>>>>> origin/PortReferencingUpdate

class QRCodeController
{
    private QRCodeModel $qr;
<<<<<<< HEAD

    public function __construct(PDO $db)
    {
        $this->qr = new QRCodeModel($db);
    }

    // POST /api/qr-codes/generate
    public function generate(array $input)
    {
=======
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

>>>>>>> origin/PortReferencingUpdate
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

<<<<<<< HEAD
    // GET /api/qr-codes/active
    public function active()
    {
=======
    // GET /api/qr-codes/active (admin only)
    public function active(): void
    {
        $this->auth->requireAdmin();

>>>>>>> origin/PortReferencingUpdate
        jsonResponse([
            "success" => true,
            "data" => $this->qr->active()
        ]);
    }

<<<<<<< HEAD
    // PATCH /api/qr-codes/use
    public function use(array $input)
    {
=======
    // PATCH /api/qr-codes/use (admin only)
    public function use(array $input): void
    {
        $this->auth->requireAdmin();

>>>>>>> origin/PortReferencingUpdate
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
<<<<<<< HEAD
}
=======
}
>>>>>>> origin/PortReferencingUpdate
