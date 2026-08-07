<?php
namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\ReportModel;
use App\Models\UserModel;

class ReportController
{
    private ReportModel $reportModel;
    private UserModel $userModel;
    private AuthMiddleware $auth;

    public function __construct()
    {
        $this->reportModel = new ReportModel();
        $this->userModel = new UserModel();
        $this->auth = new AuthMiddleware();
    }

    public function index(): void
    {
        $this->auth->requireAdmin();

        $type = $_GET['type'] ?? 'daily';
        $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        $department = $_GET['department'] ?? null;
        $employeeId = isset($_GET['employee_id']) && ctype_digit((string)$_GET['employee_id']) ? (int)$_GET['employee_id'] : null;
        $format = $_GET['format'] ?? 'json';

        if (!$this->isDate($startDate) || !$this->isDate($endDate)) {
            jsonResponse(['error' => 'Invalid date range'], 400);
        }

        if (strtotime($endDate) < strtotime($startDate)) {
            jsonResponse(['error' => 'End date cannot be before start date'], 400);
        }

        if (!in_array($type, ['daily', 'weekly', 'monthly'], true)) {
            $type = 'daily';
        }

        $employees = $this->userModel->getUsers(['role' => 'staff'], false, 200, 0);
        $rows = match ($type) {
            'weekly' => $this->reportModel->getWeekly($startDate, $endDate, $department, $employeeId),
            'monthly' => $this->reportModel->getMonthly($startDate, $endDate, $department, $employeeId),
            default => $this->reportModel->getDaily($startDate, $endDate, $department, $employeeId),
        };

        $summary = $this->reportModel->getSummary($startDate, $endDate, $department, $employeeId);

        if ($format === 'csv') {
            $this->exportCsv($type, $rows, $startDate, $endDate, $department, $employeeId);
        }

        jsonResponse([
            'success' => true,
            'data' => [
                'summary' => $summary,
                'rows' => $rows,
                'type' => $type,
                'filters' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'department' => $department,
                    'employee_id' => $employeeId,
                ],
                'meta' => [
                    'departments' => $this->userModel->getDepartments(),
                    'employees' => array_map(fn ($user) => [
                        'id' => $user['id'],
                        'name' => trim($user['first_name'] . ' ' . $user['last_name']),
                    ], $employees),
                ],
            ],
        ]);
    }

    private function exportCsv(string $type, array $rows, string $startDate, string $endDate, ?string $department, ?int $employeeId): void
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="attendance-report.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Period', 'Start Date', 'End Date', 'Present Count', 'Total Check-ins', 'Total Check-outs', 'Late Arrivals']);

        foreach ($rows as $row) {
            fputcsv($output, [
                $row['period'] ?? '',
                $row['start_date'] ?? '',
                $row['end_date'] ?? '',
                $row['present_count'] ?? 0,
                $row['total_checkins'] ?? 0,
                $row['total_checkouts'] ?? 0,
                $row['late_arrivals'] ?? 0,
            ]);
        }

        fclose($output);
        exit;
    }

    private function isDate(string $date): bool
    {
        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
        return $parsed && $parsed->format('Y-m-d') === $date;
    }
}
