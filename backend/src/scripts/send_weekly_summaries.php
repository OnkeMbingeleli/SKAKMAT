<?php
require_once __DIR__ . '/../bootstrap.php';

use App\Models\UserModel;
use App\Services\EmailService;
use App\Services\SummaryBuilder;

$userModel = new UserModel();
$emailService = new EmailService();
$summaryBuilder = new SummaryBuilder(getDB());

$to = date('Y-m-d');
$from = date('Y-m-d', strtotime('-7 days'));
$users = $userModel->getUsersWithPreference('weekly_report_email');

foreach ($users as $user) {
    $html = $summaryBuilder->build($user, $from, $to, 'weekly');
    $ok = $emailService->send(
        $user['email'],
        $user['first_name'] . ' ' . $user['last_name'],
        'Your CheckMate weekly report',
        $html
    );
    echo ($ok ? "Sent to " : "FAILED for ") . $user['email'] . PHP_EOL;
}