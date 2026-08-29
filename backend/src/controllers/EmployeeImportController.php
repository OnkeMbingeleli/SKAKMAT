<?php
namespace App\Controllers;

use App\Middleware\AuthMiddleware;

class EmployeeImportController
{
    public function __construct(private AuthMiddleware $auth = new AuthMiddleware()) {}

    public function store(array $input): void
    {
        $payload = $this->auth->requireAdmin();
        $rows = $input['rows'] ?? [];
        $source = trim((string)($input['source_name'] ?? 'legacy-import'));
        if (!is_array($rows) || !$rows) jsonResponse(['success' => false, 'error' => 'Add at least one employee row'], 400);

        $db = getDB();
        $db->beginTransaction();
        $imported = 0;
        $skipped = 0;
        try {
            $find = $db->prepare('SELECT id FROM users WHERE email = ?');
            $create = $db->prepare(
                "INSERT INTO users (first_name, last_name, email, role, department, position, password)
                 VALUES (?, ?, ?, 'staff', ?, ?, ?)"
            );
            $balance = $db->prepare(
                'INSERT INTO employee_leave_balances (user_id, leave_year, leave_type, allocated_days, used_days, imported_used_days)
                 VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE allocated_days = VALUES(allocated_days), used_days = VALUES(used_days), imported_used_days = VALUES(imported_used_days)'
            );

            foreach ($rows as $row) {
                $email = strtolower(trim((string)($row['email'] ?? '')));
                $firstName = trim((string)($row['first_name'] ?? ''));
                $lastName = trim((string)($row['last_name'] ?? ''));
                if (!$email || !$firstName || !$lastName || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $skipped++;
                    continue;
                }
                $find->execute([$email]);
                if ($find->fetchColumn()) {
                    $skipped++;
                    continue;
                }

                $temporaryPassword = bin2hex(random_bytes(6));
                $create->execute([
                    $firstName,
                    $lastName,
                    $email,
                    trim((string)($row['department'] ?? 'General')),
                    trim((string)($row['position'] ?? 'Staff member')),
                    password_hash($temporaryPassword, PASSWORD_BCRYPT),
                ]);
                $userId = (int)$db->lastInsertId();
                $year = (int)($row['leave_year'] ?? date('Y'));
                foreach (['annual', 'sick', 'other'] as $type) {
                    $balance->execute([
                        $userId,
                        $year,
                        $type,
                        max(0, (float)($row[$type . '_allocated'] ?? 0)),
                        max(0, (float)($row[$type . '_used'] ?? 0)),
                    ]);
                }
                $imported++;
            }

            $audit = $db->prepare('INSERT INTO employee_imports (imported_by, source_name, rows_imported, rows_skipped) VALUES (?, ?, ?, ?)');
            $audit->execute([(int)$payload['user_id'], $source ?: 'legacy-import', $imported, $skipped]);
            $db->commit();
            jsonResponse(['success' => true, 'data' => ['rows_imported' => $imported, 'rows_skipped' => $skipped]]);
        } catch (\Throwable $error) {
            $db->rollBack();
            jsonResponse(['success' => false, 'error' => 'Import failed: ' . $error->getMessage()], 500);
        }
    }
}
