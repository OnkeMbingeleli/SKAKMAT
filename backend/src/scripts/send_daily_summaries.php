<?php
require_once __DIR__ . '/../bootstrap.php';

use App\Models\UserModel;
use App\Services\EmailService;
use App\Services\SummaryBuilder;

$userModel = new UserModel();
$emailService = new EmailService();
$summaryBuilder = new SummaryBuilder(getDB());

$today = date('Y-m-d');
$users = $userModel->getUsersWithPreference('email_notifications');

foreach ($users as $user) {
    $html = $summaryBuilder->build($user, $today, $today, 'daily');
    $ok = $emailService->send(
        $user['email'],
        $user['first_name'] . ' ' . $user['last_name'],
        'Your CheckMate daily summary',
        $html
    );
    echo ($ok ? "Sent to " : "FAILED for ") . $user['email'] . PHP_EOL;
}