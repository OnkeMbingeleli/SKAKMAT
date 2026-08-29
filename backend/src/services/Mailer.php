<?php

namespace App\Services;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class Mailer
{
    public function sendWelcomeCredentials(
        array $user,
        string $temporaryPassword
    ): bool {
        $to = trim((string) ($user['email'] ?? ''));

        if (
            $to === '' ||
            !filter_var($to, FILTER_VALIDATE_EMAIL)
        ) {
            error_log('CheckMate Mailer: invalid recipient email.');
            return false;
        }

        $appName = getenv('APP_NAME') ?: 'CheckMate';

        $loginUrl = getenv('APP_LOGIN_URL')
            ?: 'http://localhost:8000/index.php?page=login';

        $smtpHost = getenv('MAIL_HOST') ?: 'smtp.gmail.com';

        $smtpPort = (int) (
            getenv('MAIL_PORT') ?: 587
        );

        $smtpUsername = trim(
            (string) getenv('MAIL_USERNAME')
        );

        $smtpPassword = (string) getenv(
            'MAIL_PASSWORD'
        );

        $fromAddress = trim(
            (string) (
                getenv('MAIL_FROM') ?: $smtpUsername
            )
        );

        $fromName = getenv('MAIL_FROM_NAME')
            ?: $appName;

        if (
            $smtpUsername === '' ||
            $smtpPassword === '' ||
            $fromAddress === ''
        ) {
            error_log(
                'CheckMate Mailer: SMTP credentials are missing.'
            );

            return false;
        }

        $firstName = trim(
            (string) (
                $user['first_name'] ?? 'Employee'
            )
        );

        $safeFirstName = htmlspecialchars(
            $firstName,
            ENT_QUOTES,
            'UTF-8'
        );

        $safeEmail = htmlspecialchars(
            $to,
            ENT_QUOTES,
            'UTF-8'
        );

        $safePassword = htmlspecialchars(
            $temporaryPassword,
            ENT_QUOTES,
            'UTF-8'
        );

        $safeLoginUrl = htmlspecialchars(
            $loginUrl,
            ENT_QUOTES,
            'UTF-8'
        );

        $subject =
            $appName .
            ' - Your employee login details';

        $html = <<<HTML
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{$appName} - Employee Account</title>
</head>

<body style="
    margin:0;
    padding:32px;
    background:#f8fafc;
    font-family:Arial,Helvetica,sans-serif;
    color:#0f172a;
">

<div style="
    max-width:560px;
    margin:0 auto;
    background:#ffffff;
    border:1px solid #e2e8f0;
    border-radius:18px;
    padding:32px;
">

    <div style="
        font-size:24px;
        font-weight:800;
        color:#0f766e;
    ">
        {$appName}
    </div>

    <p style="
        font-size:16px;
        margin-top:24px;
    ">
        Hi {$safeFirstName},
    </p>

    <p style="
        color:#64748b;
        line-height:1.6;
    ">
        Your {$appName} employee account has been created.
        Use the temporary credentials below to sign in.
    </p>

    <div style="
        margin:22px 0;
        padding:18px;
        border-radius:14px;
        background:#f0fdfa;
        border:1px solid #ccfbf1;
    ">

        <div>
            <strong>Email:</strong>
            {$safeEmail}
        </div>

        <div style="margin-top:10px;">
            <strong>Temporary password:</strong>
            {$safePassword}
        </div>

    </div>

    <a
        href="{$safeLoginUrl}"
        style="
            display:inline-block;
            padding:12px 18px;
            background:#0f766e;
            color:#ffffff;
            text-decoration:none;
            border-radius:10px;
            font-weight:700;
        "
    >
        Sign in to CheckMate
    </a>

    <p style="
        margin-top:24px;
        color:#64748b;
        font-size:13px;
        line-height:1.5;
    ">
        You can change your password anytime from
        Settings after signing in.
    </p>

</div>

</body>
</html>
HTML;

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();

            $mail->Host = $smtpHost;
            $mail->Port = $smtpPort;

            $mail->SMTPAuth = true;

            $mail->Username = $smtpUsername;
            $mail->Password = $smtpPassword;

            $mail->SMTPSecure =
                PHPMailer::ENCRYPTION_STARTTLS;

            $mail->SMTPDebug = 0;
            $mail->CharSet = 'UTF-8';

            $mail->setFrom(
                $fromAddress,
                $fromName
            );

            $mail->addAddress(
                $to,
                $firstName
            );

            $mail->addReplyTo(
                $fromAddress,
                $fromName
            );

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html;

            $mail->AltBody =
                "Hi {$firstName},\n\n" .
                "Your {$appName} employee account has been created.\n\n" .
                "Email: {$to}\n" .
                "Temporary password: {$temporaryPassword}\n\n" .
                "Sign in: {$loginUrl}\n\n" .
                "You can change your password from Settings after signing in.";

            return $mail->send();

        } catch (Exception $e) {
            error_log(
                'CheckMate Mailer error: ' .
                $mail->ErrorInfo
            );

            return false;
        }
    }
}