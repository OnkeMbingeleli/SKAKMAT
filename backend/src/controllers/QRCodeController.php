<?php

require_once __DIR__ . '/../models/QRCodeModel.php';

class QRCodeController
{
    private QRCodeModel $qr;

    public function __construct(PDO $db)
    {
        $this->qr = new QRCodeModel($db);
    }

    // POST /api/qr-codes/generate
    public function generate(array $input)
    {
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

    // GET /api/qr-codes/active
    public function active()
    {
        jsonResponse([
            "success" => true,
            "data" => $this->qr->active()
        ]);
    }

    // PATCH /api/qr-codes/use
    public function use(array $input)
    {
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