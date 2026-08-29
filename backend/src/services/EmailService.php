<?php
namespace App\Services;

class EmailService
{
    /**
     * Send an HTML email using PHP's built-in mail().
     * Works on Xneelo/cPanel hosting without any external library —
     * the host's mail server handles delivery.
     */
    public function send(string $toEmail, string $toName, string $subject, string $htmlBody): bool
    {
        $fromEmail = getenv('MAIL_FROM') ?: 'no-reply@' . ($_SERVER['SERVER_NAME'] ?? 'localhost');
        $fromName  = getenv('MAIL_FROM_NAME') ?: 'Skakmat';

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$fromName} <{$fromEmail}>\r\n";
        $headers .= "Reply-To: {$fromEmail}\r\n";

        $toHeader = $toName ? "{$toName} <{$toEmail}>" : $toEmail;

        $sent = mail($toHeader, $subject, $htmlBody, $headers);

        if (!$sent) {
            error_log("EmailService: mail() failed for {$toEmail}");
        }

        return $sent;
    }
}