<?php
namespace App\Services;

use App\Models\EmployeeInsightModel;

class EmployeeInsightService
{
    public function __construct(private EmployeeInsightModel $model = new EmployeeInsightModel()) {}

    /**
     * Allocated / used / remaining days per leave-type bucket
     * (annual, sick, other), used both by the admin analysis view and by
     * the employee's own "leave balance" self-service endpoint.
     */
    public function computeLeaveStatus(array $snapshot): array
    {
        $leaveStatus = [];
        foreach ($snapshot['leave_balances'] as $balance) {
            $type = $balance['leave_type'];
            $leaveStatus[$type] = [
                'allocated_days' => (float)$balance['allocated_days'],
                'used_days' => (float)$balance['used_days'],
                'remaining_days' => (float)$balance['remaining_days'],
                'exceeded' => (float)$balance['remaining_days'] < 0,
            ];
        }
        return $leaveStatus;
    }

    /**
     * Leave balance only — no attendance signals, no AI call. Safe for an
     * employee to fetch about themselves.
     */
    public function leaveBalance(int $userId): ?array
    {
        $snapshot = $this->model->getEmployeeSnapshot($userId);
        if (!$snapshot) return null;
        return $this->computeLeaveStatus($snapshot);
    }

    public function analyze(int $userId): array
    {
        $snapshot = $this->model->getEmployeeSnapshot($userId);
        if (!$snapshot) return [];

        $attendance = $snapshot['attendance'];
        $records = (int)($attendance['records'] ?? 0);
        $late = (int)($attendance['late_arrivals'] ?? 0);
        $approvedLeave = array_filter($snapshot['leave_requests'], fn($row) => $row['status'] === 'approved');
        $leaveDays = array_sum(array_map(fn($row) => (int)$row['days'], $approvedLeave));
        $leaveStatus = $this->computeLeaveStatus($snapshot);
        $signals = [];
        if ($records > 0 && $late / $records >= 0.2) $signals[] = 'Frequent late arrivals in the last 90 days.';
        if ($leaveDays >= 10) $signals[] = 'Approved leave usage is high in the last 12 months.';
        foreach ($leaveStatus as $type => $status) if ($status['exceeded']) $signals[] = ucfirst($type) . ' leave allocation has been exceeded.';
        if (!$signals) $signals[] = 'No significant attendance or leave risk signal detected.';

        $analysis = [
            'provider' => 'local-rules',
            'summary' => count($signals) === 1 ? $signals[0] : 'Multiple signals need a manager review.',
            'signals' => $signals,
            'metrics' => [
                'attendance_records_90d' => $records,
                'late_arrivals_90d' => $late,
                'approved_leave_days_12m' => $leaveDays,
            ],
            'leave_balances' => $snapshot['leave_balances'],
            'leave_status' => $leaveStatus,
            'contract' => $snapshot['contract'],
            'disclaimer' => 'This is a decision-support signal, not an automated employment decision. Review the source records before acting.',
        ];
        return $this->analyzeWithGemini($analysis, $snapshot['employee']);
    }

    private function analyzeWithGemini(array $analysis, array $employee): array
    {
        $apiKey = getenv('GEMINI_API_KEY');
        if (!$apiKey || !function_exists('curl_init')) return $analysis;
        $prompt = 'You are an HR analytics assistant. Summarize these attendance and leave signals factually. Do not infer protected characteristics, diagnose health, or recommend discipline. Return JSON with summary and signals only. Data: ' . json_encode(['employee' => $employee, 'analysis' => $analysis]);
        $ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . rawurlencode($apiKey));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode(['contents' => [['parts' => [['text' => $prompt]]]]]),
            CURLOPT_TIMEOUT => 8,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        $decoded = json_decode((string)$response, true);
        $text = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $text = preg_replace('/^```json|```$/m', '', trim($text));
        $ai = json_decode(trim($text), true);
        if (!is_array($ai)) return $analysis;
        $analysis['provider'] = 'gemini-1.5-flash';
        $analysis['summary'] = (string)($ai['summary'] ?? $analysis['summary']);
        $analysis['signals'] = array_values(array_filter($ai['signals'] ?? $analysis['signals'], 'is_string'));
        return $analysis;
    }
}
