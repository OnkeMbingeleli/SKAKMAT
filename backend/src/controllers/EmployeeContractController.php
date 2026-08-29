<?php
namespace App\Controllers;

use App\Middleware\AuthMiddleware;

class EmployeeContractController
{
    private AuthMiddleware $auth;

    public function __construct()
    {
        $this->auth = new AuthMiddleware();
    }

    public function store(int $userId, array $input): void
    {
        $this->auth->requireAdmin();
        foreach (['start_date', 'annual_leave_days', 'sick_leave_days', 'other_leave_days'] as $field) {
            if (!array_key_exists($field, $input) || $input[$field] === '') jsonResponse(['success' => false, 'error' => "Field '$field' is required"], 400);
        }
        $stmt = getDB()->prepare(
            'INSERT INTO employee_contracts (user_id, start_date, end_date, contract_type, annual_leave_days, sick_leave_days, other_leave_days, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $userId,
            $input['start_date'],
            $input['end_date'] ?? null,
            $input['contract_type'] ?? 'permanent',
            max(0, (float)$input['annual_leave_days']),
            max(0, (float)$input['sick_leave_days']),
            max(0, (float)$input['other_leave_days']),
            $input['notes'] ?? null,
        ]);
        jsonResponse(['success' => true, 'data' => ['id' => (int)getDB()->lastInsertId()]], 201);
    }
}