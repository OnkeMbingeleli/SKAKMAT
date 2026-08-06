<?php

require_once __DIR__ . '/../models/QRSessionModel.php';

class QRSessionController
{
    private QRSessionModel $session;

    public function __construct(PDO $db)
    {
        $this->session = new QRSessionModel($db);
    }

    // POST
    public function enable(array $input)
    {
        if (
            !isset($input['created_by']) ||
            !isset($input['clock_in_deadline']) ||
            !isset($input['clock_out_deadline'])
        ) {
            jsonResponse([
                "success" => false,
                "message" => "Missing required fields."
            ], 400);
        }

        $id = $this->session->enable(
            $input['created_by'],
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

    // PATCH
    public function disable($id)
    {
        $success = $this->session->disable($id);

        jsonResponse([
            "success" => $success
        ]);
    }

    // GET
    public function active()
    {
        jsonResponse(
            $this->session->active()
        );
    }
}