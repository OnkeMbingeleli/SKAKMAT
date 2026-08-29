<?php
// This file can be included or used as a standalone login page.
// No server-side logic yet – just the design.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CheckMate · Login</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet" />
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <style>
        /* ── Reset & base ── */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: linear-gradient(160deg, #062420 0%, #0A3D34 45%, #0B574C 100%);
            position: relative;
            overflow-x: hidden;
        }

        /* ── Background starfield sits behind the whole page ── */
        #starfield {
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            display: block;
            pointer-events: none;
            z-index: 0;
        }
        body::before,
        body::after {
            content: '';
            position: fixed;
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
        }
        body::after {
            top: -20%;
            right: -15%;
            width: 560px;
            height: 560px;
            background: rgba(47, 191, 154, 0.10);
        }
        body::before {
            bottom: -20%;
            left: -15%;
            width: 460px;
            height: 460px;
            background: rgba(47, 191, 154, 0.06);
        }

        /* ── One unified, centered card ── */
        .login-card {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 460px;
            background: #ffffff;
            border-radius: 28px;
            box-shadow: 0 30px 80px rgba(4, 20, 18, 0.35);
            padding: 44px 40px 36px;
            text-align: center;
        }

        .brand { margin-bottom: 22px; }
        .brand h1 {
            font-size: 34px;
            font-weight: 700;
            color: #0e0c2a;
            letter-spacing: -0.5px;
        }
        .brand h1 span { color: #0B574C; }
        .brand .sub {
            font-size: 12px;
            font-weight: 600;
            color: #8e8db0;
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-top: 4px;
        }

        .badge-row {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 16px 0 26px;
            flex-wrap: wrap;
        }
        .badge-row .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #E6F4F1;
            border: 1px solid #d6ece7;
            border-radius: 40px;
            padding: 6px 14px;
            font-size: 11.5px;
            font-weight: 600;
            color: #0B574C;
        }

        /* ── Tabs ── */
        .tab-group {
            display: flex;
            gap: 6px;
            background: #F1F5F3;
            border-radius: 60px;
            padding: 5px;
            margin: 0 auto 26px;
            width: fit-content;
        }
        .tab-btn {
            font-family: 'Inter', sans-serif;
            font-size: 14.5px;
            font-weight: 600;
            padding: 11px 30px;
            border: none;
            border-radius: 60px;
            cursor: pointer;
            transition: all 0.25s ease;
            background: transparent;
            color: #6b7a8d;
        }
        .tab-btn:hover { color: #0B574C; }
        .tab-btn.active {
            background: #0B574C;
            color: #ffffff;
            box-shadow: 0 6px 16px rgba(11,87,76,0.25);
        }

        .greeting { text-align: left; margin-bottom: 6px; }
        .greeting h2 {
            font-size: 22px;
            font-weight: 700;
            color: #0e0c2a;
        }
        .greeting p {
            font-size: 14px;
            color: #6b6a8a;
            margin-top: 4px;
        }
        .admin-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #E6F4F1;
            color: #0B574C;
            font-size: 12.5px;
            font-weight: 600;
            padding: 4px 14px 4px 10px;
            border-radius: 40px;
            margin-bottom: 10px;
        }

        /* ── Google button ── */
        .btn-google {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            width: 100%;
            padding: 13px 0;
            border: 1px solid #e3e4f0;
            border-radius: 60px;
            background: #fff;
            font-size: 14.5px;
            font-weight: 500;
            color: #1f1e3a;
            cursor: pointer;
            transition: all 0.25s ease;
            margin-top: 18px;
        }
        .btn-google:hover {
            border-color: #b0b5d6;
            background: #f8f9ff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
        }
        .btn-google i {
            color: #0B574C;
            font-size: 17px;
        }

        /* ── Divider ── */
        .divider {
            display: flex;
            align-items: center;
            gap: 14px;
            margin: 20px 0 18px 0;
        }
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e9eaf2;
        }
        .divider span {
            font-size: 12px;
            font-weight: 500;
            color: #8e8db0;
            text-transform: uppercase;
        }

        /* ── Form fields ── */
        .form-group { margin-bottom: 16px; text-align: left; }
        .form-group label {
            display: block;
            font-size: 13.5px;
            font-weight: 600;
            color: #1f1e3a;
            margin-bottom: 6px;
        }
        .input-wrap { position: relative; }
        .input-wrap i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #a8a9c7;
            font-size: 15px;
        }
        .input-wrap input {
            width: 100%;
            min-height: 50px;
            padding: 13px 18px 13px 46px;
            border: 1.5px solid #e3e4f0;
            border-radius: 60px;
            font-size: 14.5px;
            color: #1f1e3a;
            background: #fff;
            outline: none;
            transition: all 0.25s ease;
        }
        .input-wrap input::placeholder { color: #b0b1cc; }
        .input-wrap input:focus {
            border-color: #0B574C;
            box-shadow: 0 0 0 4px rgba(11,87,76,0.08);
        }

        .password-row {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-top: 4px;
        }
        .password-row a {
            font-size: 13.5px;
            font-weight: 500;
            color: #0B574C;
            text-decoration: none;
        }
        .password-row a:hover { text-decoration: underline; }

        .btn-signin {
            width: 100%;
            min-height: 50px;
            padding: 14px 0;
            border: none;
            border-radius: 60px;
            background: #0B574C;
            color: #fff;
            font-size: 15.5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 8px;
        }
        .btn-signin:hover {
            background: #0A5348;
            box-shadow: 0 8px 24px rgba(11,87,76,0.20);
            transform: translateY(-1px);
        }
        .btn-signin:disabled { opacity: 0.7; cursor: progress; transform: none; }

        .footnote {
            font-size: 13px;
            color: #6b6a8a;
            margin-top: 20px;
            line-height: 1.55;
            border-top: 1px solid #f0f1f7;
            padding-top: 18px;
            text-align: left;
        }
        .footnote .highlight { color: #0B574C; font-weight: 500; }

        /* ── Toggle panels ── */
        .form-panel { display: none; }
        .form-panel.active { display: block; }

        /* ── Responsive ── */
        @media (max-width: 560px) {
            body { padding: 16px; align-items: flex-start; padding-top: 36px; }
            .login-card { padding: 32px 22px 28px; border-radius: 22px; }
            .brand h1 { font-size: 28px; }
            .tab-btn { padding: 10px 22px; font-size: 13.5px; }
            .badge-row .badge { font-size: 11px; padding: 5px 11px; }
        }
        @media (max-width: 360px) {
            .tab-group { width: 100%; }
            .tab-btn { flex: 1; padding: 10px 8px; }
        }
    </style>
</head>
<body>
<canvas id="starfield"></canvas>

<div class="login-card">
    <div class="brand">
        <h1>Check<span>Mate</span></h1>
        <div class="sub">by AttendanceHub</div>
    </div>

    <div class="badge-row">
        <span class="badge"><i class="fas fa-shield-alt"></i> Secure</span>
        <span class="badge"><i class="fas fa-bolt"></i> Real-time</span>
        <span class="badge"><i class="fas fa-qrcode"></i> QR Check-in</span>
    </div>

    <div class="tab-group" id="tabGroup">
        <button class="tab-btn active" data-tab="staff">Staff Login</button>
        <button class="tab-btn" data-tab="admin">Admin Login</button>
    </div>

    <!-- ====== STAFF PANEL ====== -->
    <div id="panelStaff" class="form-panel active">
        <div class="greeting">
            <h2>Welcome back</h2>
            <p>Sign in with the credentials your admin emailed you.</p>
        </div>

        <form action="#" method="POST">
            <div class="form-group">
                <label for="staffEmail">Email address</label>
                <div class="input-wrap">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="staffEmail" placeholder="you@company.com" required />
                </div>
            </div>
            <div class="form-group">
                <label for="staffPassword">Password</label>
                <div class="input-wrap">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="staffPassword" placeholder="••••••••" required />
                </div>
            </div>
            <div class="password-row">
                <a href="#">Forgot password?</a>
            </div>
            <button type="submit" class="btn-signin">Sign in</button>
        </form>

        <div class="footnote">
            First time signing in? Use the temporary password from your welcome email — you'll be asked to keep it or change it.
        </div>
    </div>

    <!-- ====== ADMIN PANEL ====== -->
    <div id="panelAdmin" class="form-panel">
        <div class="greeting">
            <div class="admin-badge"><i class="fas fa-user-shield"></i> Admin</div>
            <h2>Welcome back, Admin</h2>
            <p class="admin-sub">Manage employees, oversee attendance and approve leave.</p>
        </div>

        <form action="#" method="POST">
            <div class="form-group">
                <label for="adminEmail">Email address</label>
                <div class="input-wrap">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="adminEmail" placeholder="admin@company.com" required />
                </div>
            </div>
            <div class="form-group">
                <label for="adminPassword">Password</label>
                <div class="input-wrap">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="adminPassword" placeholder="••••••••" required />
                </div>
            </div>
            <div class="password-row">
                <a href="#">Forgot password?</a>
            </div>
            <button type="submit" class="btn-signin">Sign in</button>
        </form>

        <div class="footnote">
            Admin accounts are provisioned by another administrator. <a class="highlight" href="mailto:onkembingeleli22@gmail.com?subject=CheckMate%20admin%20access%20request">Contact IT</a> if you need access.
        </div>
    </div>
</div>

<!-- JavaScript for Starfield Animation -->
<script>
    // ─── MOVING STARFIELD ANIMATION (now runs on the full page background) ───
    const canvas = document.getElementById('starfield');
    const ctx = canvas.getContext('2d');

    let stars = [];
    let width, height;

    function initStars() {
        width = window.innerWidth;
        height = window.innerHeight;

        const dpr = window.devicePixelRatio || 1;
        canvas.width = width * dpr;
        canvas.height = height * dpr;
        canvas.style.width = width + 'px';
        canvas.style.height = height + 'px';
        ctx.setTransform(1, 0, 0, 1, 0, 0);
        ctx.scale(dpr, dpr);

        stars = [];
        const numStars = 160;

        for (let i = 0; i < numStars; i++) {
            stars.push({
                x: Math.random() * width,
                y: Math.random() * height,
                radius: Math.random() * 2.0 + 0.5,
                baseOpacity: Math.random() * 0.7 + 0.2,
                speed: 0.002 + Math.random() * 0.012,
                phase: Math.random() * Math.PI * 2,
                dx: (Math.random() - 0.5) * 0.15,
                dy: (Math.random() - 0.5) * 0.10
            });
        }
    }

    let time = 0;
    function animateStars() {
        time += 0.01;
        ctx.clearRect(0, 0, width, height);

        stars.forEach(star => {
            star.x += star.dx;
            star.y += star.dy;

            if (star.x < -10) star.x = width + 10;
            if (star.x > width + 10) star.x = -10;
            if (star.y < -10) star.y = height + 10;
            if (star.y > height + 10) star.y = -10;

            const opacity = star.baseOpacity * (0.5 + 0.5 * Math.sin(time * star.speed + star.phase));

            ctx.beginPath();
            ctx.arc(star.x, star.y, star.radius, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(255, 255, 255, ${opacity})`;
            ctx.fill();

            if (star.radius > 1.5) {
                ctx.shadowColor = `rgba(47, 191, 154, ${opacity * 0.15})`;
                ctx.shadowBlur = 8;
                ctx.fill();
                ctx.shadowBlur = 0;
            }
        });

        requestAnimationFrame(animateStars);
    }

    let resizeTimeout;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            initStars();
        }, 200);
    });

    initStars();
    animateStars();
</script>

<!-- Shared API must load before the form handler calls login(). -->
<script src="/assets/js/config.js"></script>
<script src="/assets/js/api.js"></script>
<!-- Login Form Handler + Tab Switching -->
<script src="/assets/js/login.js"></script>

</body>
</html>
