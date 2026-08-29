@echo off
REM Skakmat Backend Setup Script for Windows
REM Configures the bundled PHPMailer email sender

echo.
echo ================================
echo Skakmat Backend Setup
echo ================================
echo.

echo PHPMailer is bundled with this application.
echo No Composer or separate mailer installation is required.
echo.

REM Check if .env exists
if not exist .env (
    echo Creating .env file from .env.example...
    copy .env.example .env
    echo.
    echo Please edit backend\.env with your Gmail credentials:
    echo   - MAIL_USERNAME: your Gmail address
    echo   - MAIL_PASSWORD: your 16-character Google App Password
    echo   - MAIL_FROM: your Gmail address
    echo.
)

echo.
echo ================================
echo Setup Complete!
echo ================================
echo.
echo Next steps:
echo 1. Edit backend\.env with your Gmail SMTP credentials
echo 2. Start the backend server:
echo    php -S localhost:8080 -t public
echo 3. In another terminal, start the frontend:
echo    cd ..\frontend
echo    php -S localhost:8000 -t public
echo.
echo For more details, see PHPMAILER_SETUP.md
echo.
pause
