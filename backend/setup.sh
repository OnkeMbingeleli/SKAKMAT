#!/bin/bash

# Skakmat Backend Setup Script
# Configures the bundled PHPMailer email sender.

echo "================================"
echo "Skakmat Backend Setup"
echo "================================"
echo ""

cd "$(dirname "$0")" || exit 1

echo "PHPMailer is bundled with this application."
echo "No Composer or separate mailer installation is required."
echo ""

if [ ! -f .env ]; then
    echo "Creating .env file from .env.example..."
    cp .env.example .env
    echo "Please edit backend/.env with your SMTP credentials."
    echo ""
fi

echo "================================"
echo "Setup Complete!"
echo "================================"
echo ""
echo "Next steps:"
echo "1. Edit backend/.env with your SMTP credentials"
echo "2. Start the backend server:"
echo "   php -S localhost:8080 -t public"
echo "3. In another terminal, start the frontend:"
echo "   cd ../frontend"
echo "   php -S localhost:8000 -t public"
echo ""
echo "For more details, see PHPMAILER_SETUP.md"
