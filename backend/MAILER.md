# Bundled email sender

This backend includes PHPMailer 6.9.3 under `vendor/phpmailer/phpmailer`.
It is loaded directly by `src/bootstrap.php`, so neither Composer nor a
separate PHPMailer installation is required on a new server.

Configure these values in `backend/.env`:

```dotenv
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@example.com
MAIL_PASSWORD=your-smtp-or-app-password
MAIL_FROM=your-email@example.com
MAIL_FROM_NAME=CheckMate
```

For Gmail, create and use a Google App Password. The `vendor` directory is
part of the application and must be deployed along with `backend`.
