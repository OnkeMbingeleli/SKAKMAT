# PHPMailer Setup Guide for CheckMate

## Overview
PHPMailer 6.9.3 is bundled in `backend/vendor/phpmailer/phpmailer` and sends employee welcome emails with temporary credentials via Gmail SMTP (port 587 with STARTTLS). No Composer or separate PHPMailer installation is required.

## Prerequisites
1. A Gmail account with 2-Step Verification enabled
2. A Google App Password (16-character password for third-party apps)

## Step 1: Generate Gmail App Password

1. Go to https://myaccount.google.com
2. Click **Security** in the left sidebar
3. Under "How you sign in to Google", enable **2-Step Verification** (if not already enabled)
4. After 2-Step is enabled, go back to Security
5. Find **App passwords** (will only appear after 2-Step is enabled)
6. Select "Mail" and "Windows Computer"
7. Google will display a 16-character password
8. Copy this password (you'll need it in the next step)

## Step 2: Configure Backend Environment

1. Open `backend/.env` (create if it doesn't exist):

```bash
cd backend
nano .env
```

2. Add these lines (replace with your actual values):

```
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-16-char-app-password
MAIL_FROM=your-email@gmail.com
MAIL_FROM_NAME=CheckMate
APP_NAME=CheckMate
APP_LOGIN_URL=http://localhost:8080/index.php?page=login
```

3. Save the file (Ctrl+X, then Y, then Enter in nano)

## Step 3: How It Works

When you create a new employee in the admin panel:

1. A temporary password is generated
2. The employee record is created in the database
3. PHPMailer sends a welcome email to the employee's email address
4. The email contains:
   - Employee's email address
   - Temporary password
   - Link to login page
   - Instructions to change password in Settings

## Step 4: Testing

1. Start the backend server:
```bash
cd backend
php -S localhost:8080 -t public
```

2. Start the frontend in another terminal:
```bash
cd frontend
php -S localhost:8000 -t public
```

3. Log in as admin and create a new employee with an email you control
4. Check the employee's inbox for the welcome email
5. The email should NOT show any warning about localhost:25 (incorrect SMTP)

## Security Notes

⚠️ **IMPORTANT:** 
- **Never** commit your `.env` file to Git
- Keep your Gmail App Password only in `.env`, never share it
- The `.env.example` file shows the configuration format but uses placeholder values
- Add `.env` to your `.gitignore` file

## Troubleshooting

### Email Not Sending
1. Check error logs: `tail -f backend/logs/*` (if logging is configured)
2. Verify `.env` file has correct SMTP credentials
3. Ensure Gmail account has 2-Step Verification enabled
4. Confirm you're using the 16-character App Password, not your normal Gmail password
5. Check that MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD are all set

### SMTP Connection Failed
- Verify port 587 is not blocked by your firewall
- Use Gmail's official SMTP settings: `smtp.gmail.com` on port 587 with STARTTLS
- Check that SMTPSecure is set to `ENCRYPTION_STARTTLS` in Mailer.php

### PHP Error: "Class PHPMailer not found"
- Verify that `backend/vendor/phpmailer/phpmailer/src/PHPMailer.php` was deployed with the app.
- Verify `backend/src/bootstrap.php` is loaded before the mailer is used.

## Email Template

The welcome email template is defined in `backend/src/services/Mailer.php`:
- Responsive HTML design
- Employee's first name is personalized
- Login credentials clearly displayed
- Instructions to change password

You can customize the email HTML by editing the template in `Mailer.php`.

## API Response

When creating an employee, the API returns:
```json
{
  "success": true,
  "message": "Staff member created",
  "user": { ... },
  "password": "temporary_password_here",
  "email_sent": true
}
```

- If `email_sent` is `false`, the employee was created but the email failed to send
- The password is still returned so you can manually share it if needed
