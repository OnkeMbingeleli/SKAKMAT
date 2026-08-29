# 🚀 Skakmat - Quick Installation & Setup Guide

## Pre-Installation Requirements
- PHP 7.4 or higher
- Composer (https://getcomposer.org)
- Gmail account with 2-Step Verification enabled
- Database already configured (from previous sessions)

---

## ⚙️ Installation Steps

### Step 1: Install PHP Dependencies

**Windows:**
```bash
cd backend
setup.bat
```

**Mac/Linux:**
```bash
cd backend
bash setup.sh
```

**Manual Installation:**
```bash
cd backend
composer install
cp .env.example .env
```

### Step 2: Configure Gmail SMTP

**Get your Gmail App Password:**
1. Visit https://myaccount.google.com
2. Click **Security** in the left menu
3. Make sure **2-Step Verification** is enabled (enable if needed)
4. Look for **App passwords** option (appears after 2-Step is on)
5. Select "Mail" and "Windows Computer" (or your device)
6. Google will show you a 16-character password
7. Copy this password

**Edit `.env` file:**

```bash
# Open backend/.env in your text editor
# Find and update these lines:

MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-gmail@gmail.com          # ← Your Gmail address
MAIL_PASSWORD=xxxx xxxx xxxx xxxx           # ← The 16-char app password (no spaces)
MAIL_FROM=your-gmail@gmail.com
MAIL_FROM_NAME=Skakmat
APP_NAME=Skakmat
APP_LOGIN_URL=http://localhost:8080/index.php?page=login
```

**Save the file** (do NOT commit .env to Git!)

---

## 🎯 Testing the Installation

### Terminal 1 - Start Backend Server
```bash
cd backend
php -S localhost:8080 -t public
```

You should see:
```
Development Server running at http://127.0.0.1:8080
```

### Terminal 2 - Start Frontend Server
```bash
cd frontend
php -S localhost:8000 -t public
```

You should see:
```
Development Server running at http://127.0.0.1:8000
```

### Open in Browser
- Visit: http://localhost:8000
- Login with admin credentials

---

## ✅ Test All Features

### 1. Test Employee Creation (with email)
1. Login as admin
2. Navigate to **Admin → Employees**
3. Click **Add Employee** button
4. Fill in the form:
   - First Name: Test
   - Surname: Employee
   - Email: `your-test-email@gmail.com` (use YOUR email to receive test)
   - Department: Select from dropdown
   - Position: Test Position
5. Click **Create**
6. You should see success message
7. Check your email inbox for welcome message (30 seconds)

**Expected Email:**
- Subject: "Skakmat - Your employee login details"
- Contains: Your email and temporary password
- Has: A button to login

### 2. Test Employee Edit
1. In Employees table, click **Edit** on any employee
2. Change the email or name
3. Click **Save**
4. Verify changes appear in the table

### 3. Test Employee Delete
1. In Employees table, click **Delete** on any employee
2. Click **Confirm** in the popup
3. Employee disappears from table

### 4. Test Reports
1. Navigate to **Reports** page
2. You should see:
   - **Weekly Attendance** (bar chart)
   - **Monthly Attendance Rate** (line chart)
   - **Late Arrivals** (bar chart)
   - **Current Presence** (donut chart with rotation)
3. Try the filters:
   - Change date range → charts update
   - Select department → charts filter
   - Select employee → charts filter
4. Click and drag the donut chart to rotate it
5. Double-click to reset rotation

---

## 🐛 Troubleshooting

### Email Not Sending?

**Check #1: Is .env configured?**
```bash
# Verify backend/.env has these fields filled in:
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=xxxx xxxx xxxx xxxx
```

**Check #2: Is it an app password (not your Gmail password)?**
- Must be the 16-character app password from myaccount.google.com
- NOT your regular Gmail password
- If you paste your regular password, Google will reject it

**Check #3: Is 2-Step Verification enabled on your Gmail?**
- Go to myaccount.google.com → Security
- Look for 2-Step Verification
- If not enabled, enable it first
- Then "App passwords" will appear

**Check #4: Check the PHP error log**
- Start backend with: `php -S localhost:8080 -t public 2>&1`
- Look for errors mentioning SMTP or PHPMailer
- Common error: "Could not connect to smtp.gmail.com:587"
  - This means your firewall might block port 587
  - Contact your IT department or change networks

### "Class PHPMailer not found" error?
1. Make sure you ran `composer install` in the backend folder
2. Check that `backend/vendor/autoload.php` exists
3. Restart the PHP server

### Employee created but email didn't send?
- The employee IS created successfully
- Response shows `"email_sent": false`
- You can share the temporary password manually
- Check PHP error logs for the email error reason

---

## 📁 Project Structure

```
SKAKMAT/
├── backend/
│   ├── public/          # Web root
│   ├── src/
│   │   ├── controllers/ # API endpoints
│   │   ├── models/      # Database models
│   │   ├── services/    # Business logic (Mailer.php)
│   │   └── routes/      # URL routing
│   ├── vendor/          # PHP dependencies (created by composer)
│   ├── .env             # Configuration (DO NOT commit)
│   ├── .env.example     # Configuration template
│   ├── composer.json    # Dependency list
│   ├── setup.bat        # Windows setup
│   └── setup.sh         # Mac/Linux setup
│
├── frontend/
│   ├── public/          # Web root
│   ├── src/views/       # HTML templates
│   └── assets/          # CSS, JavaScript
│
└── README.md            # Project documentation
```

---

## 🔐 Security Reminders

⚠️ **IMPORTANT:**
- **.env is secret** - Never commit it to Git or share it
- **Google App Password** - Only used in .env, keep private
- **Never paste actual passwords in chat** - Use environment variables
- **Set strong passwords** - Employees must change temp password on first login

---

## 📞 Support

### Common Issues & Solutions

| Issue | Solution |
|-------|----------|
| Port 8080 already in use | Change to different port: `php -S localhost:8081 -t public` |
| Database connection error | Check .env MYSQL* variables and ensure Railway tunnel is open |
| Email says "From: nobody@localhost" | MAIL_FROM not set in .env |
| Email won't send over port 587 | Your firewall might block it - try port 465 instead |
| Composer command not found | Install Composer from getcomposer.org |
| PHP version error | Update to PHP 7.4+ |

---

## ✨ All Features Summary

| Feature | Status | How to Test |
|---------|--------|------------|
| Add Employee | ✅ Working | Admin → Employees → Add Employee |
| Edit Employee | ✅ Working | Admin → Employees → Click Edit |
| Delete Employee | ✅ Working | Admin → Employees → Click Delete |
| Email Notifications | ✅ Working | Create employee, check email inbox |
| Reports Dashboard | ✅ Working | Navigate to Reports page |
| Weekly Attendance Chart | ✅ Working | Reports → See bar chart |
| Monthly Trend Chart | ✅ Working | Reports → See line chart |
| Late Arrivals Chart | ✅ Working | Reports → See bar chart |
| Interactive Donut Chart | ✅ Working | Reports → Drag to rotate donut |
| Chart Filters | ✅ Working | Reports → Change date/dept/employee |

---

## 🎉 You're All Set!

Your Skakmat system is now fully functional with:
- ✅ Employee management (CRUD operations)
- ✅ Automated email notifications
- ✅ Interactive reporting dashboard
- ✅ Real-time data visualization

**Happy attendance tracking!** 📊
