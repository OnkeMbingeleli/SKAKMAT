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
            background: #f4f6fb;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* ── Main card ── */
        .login-container {
            display: flex;
            max-width: 1200px;
            width: 100%;
            min-height: 680px;
            background: #ffffff;
            border-radius: 36px;
            box-shadow: 0 30px 80px rgba(10, 10, 40, 0.20);
            overflow: hidden;
        }

        /* ── Left panel (dark) with canvas container ── */
        .panel-left {
            flex: 0 0 48%;
            background: linear-gradient(145deg, #0e0c2a 0%, #1a1642 50%, #221e5a 100%);
            padding: 56px 48px 48px 48px;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }
        .panel-left::before,
        .panel-left::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
        }
        .panel-left::after {
            top: -40%;
            right: -30%;
            width: 500px;
            height: 500px;
            background: rgba(74, 108, 247, 0.08);
        }
        .panel-left::before {
            bottom: -20%;
            left: -20%;
            width: 400px;
            height: 400px;
            background: rgba(74, 108, 247, 0.05);
        }

        /* ── Starfield Canvas ── */
        #starfield {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: block;
            pointer-events: none;
            z-index: 1;
        }

        .brand, .tab-group, .illustration {
            position: relative;
            z-index: 2;
        }

        .brand {
            margin-bottom: 48px;
        }
        .brand h1 {
            font-size: 42px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -0.5px;
        }
        .brand h1 span {
            color: #6d8aff;
        }
        .brand .sub {
            font-size: 14px;
            font-weight: 500;
            color: rgba(255,255,255,0.5);
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-top: 4px;
        }

        /* ── Tabs ── */
        .tab-group {
            display: flex;
            gap: 6px;
            background: rgba(255,255,255,0.06);
            border-radius: 60px;
            padding: 5px;
            width: fit-content;
            margin-bottom: 40px;
            border: 1px solid rgba(255,255,255,0.06);
            backdrop-filter: blur(2px);
        }
        .tab-btn {
            font-family: 'Inter', sans-serif;
            font-size: 15px;
            font-weight: 600;
            padding: 12px 32px;
            border: none;
            border-radius: 60px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: transparent;
            color: rgba(255,255,255,0.5);
        }
        .tab-btn:hover {
            color: rgba(255,255,255,0.8);
        }
        .tab-btn.active {
            background: #ffffff;
            color: #0e0c2a;
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }

        /* ── Left illustration ── */
        .illustration {
            margin-top: auto;
        }
        .illustration .icon-row {
            display: flex;
            gap: 16px;
            align-items: center;
        }
        .illustration .icon-row i {
            font-size: 28px;
            color: rgba(255,255,255,0.15);
        }
        .illustration p {
            font-size: 14px;
            color: rgba(255,255,255,0.3);
            line-height: 1.6;
            max-width: 280px;
            margin-top: 12px;
        }
        .illustration .badge {
            display: inline-block;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 40px;
            padding: 6px 18px;
            font-size: 12px;
            font-weight: 500;
            color: rgba(255,255,255,0.35);
        }

        /* ── Right panel (white) ── */
        .panel-right {
            flex: 1;
            padding: 56px 56px 48px 48px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: #ffffff;
        }

        .form-wrapper {
            max-width: 400px;
            width: 100%;
            margin: 0 auto;
        }

        .greeting h2 {
            font-size: 28px;
            font-weight: 700;
            color: #0e0c2a;
        }
        .greeting p {
            font-size: 15px;
            color: #6b6a8a;
            margin-top: 4px;
        }
        .admin-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #eef1ff;
            color: #4a6cf7;
            font-size: 13px;
            font-weight: 600;
            padding: 4px 16px 4px 12px;
            border-radius: 40px;
            margin-bottom: 12px;
        }

        /* ── Google button ── */
        .btn-google {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            width: 100%;
            padding: 14px 0;
            border: 1px solid #e3e4f0;
            border-radius: 60px;
            background: #fff;
            font-size: 15px;
            font-weight: 500;
            color: #1f1e3a;
            cursor: pointer;
            transition: all 0.25s ease;
            margin-top: 20px;
        }
        .btn-google:hover {
            border-color: #b0b5d6;
            background: #f8f9ff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
        }
        .btn-google i {
            color: #4a6cf7;
            font-size: 18px;
        }

        /* ── Divider ── */
        .divider {
            display: flex;
            align-items: center;
            gap: 16px;
            margin: 24px 0 20px 0;
        }
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e9eaf2;
        }
        .divider span {
            font-size: 13px;
            font-weight: 500;
            color: #8e8db0;
            text-transform: uppercase;
        }

        /* ── Form fields ── */
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #1f1e3a;
            margin-bottom: 6px;
        }
        .input-wrap {
            position: relative;
        }
        .input-wrap i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #a8a9c7;
            font-size: 16px;
        }
        .input-wrap input {
            width: 100%;
            padding: 14px 20px 14px 48px;
            border: 1.5px solid #e3e4f0;
            border-radius: 60px;
            font-size: 15px;
            color: #1f1e3a;
            background: #fff;
            outline: none;
            transition: all 0.25s ease;
        }
        .input-wrap input::placeholder {
            color: #b0b1cc;
        }
        .input-wrap input:focus {
            border-color: #4a6cf7;
            box-shadow: 0 0 0 4px rgba(74,108,247,0.08);
        }

        .password-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 4px;
        }
        .password-row a {
            font-size: 14px;
            font-weight: 500;
            color: #4a6cf7;
            text-decoration: none;
        }
        .password-row a:hover {
            text-decoration: underline;
        }

        .btn-signin {
            width: 100%;
            padding: 15px 0;
            border: none;
            border-radius: 60px;
            background: #0e0c2a;
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 6px;
        }
        .btn-signin:hover {
            background: #1a1642;
            box-shadow: 0 8px 24px rgba(14,12,42,0.20);
            transform: translateY(-1px);
        }

        .footnote {
            font-size: 13.5px;
            color: #6b6a8a;
            margin-top: 22px;
            line-height: 1.6;
            border-top: 1px solid #f0f1f7;
            padding-top: 20px;
        }
        .footnote .highlight {
            color: #4a6cf7;
            font-weight: 500;
        }

        /* ── Toggle panels ── */
        .form-panel {
            display: none;
        }
        .form-panel.active {
            display: block;
        }

        /* ── Responsive ── */
        @media (max-width: 1024px) {
            .panel-left { flex: 0 0 44%; padding: 40px 32px; }
            .panel-right { padding: 40px 36px; }
            .brand h1 { font-size: 34px; }
            .tab-btn { padding: 10px 24px; font-size: 14px; }
        }
        @media (max-width: 820px) {
            .login-container { flex-direction: column; min-height: auto; border-radius: 28px; }
            .panel-left { flex: none; padding: 40px 32px 32px; min-height: 260px; }
            .panel-left .illustration { display: none; }
            .panel-right { padding: 40px 32px; }
            .form-wrapper { max-width: 100%; }
            .brand h1 { font-size: 32px; }
            .tab-group { margin-bottom: 0; }
        }
        @media (max-width: 480px) {
            .panel-left { padding: 28px 20px 20px; min-height: 200px; }
            .panel-right { padding: 28px 20px; }
            .brand h1 { font-size: 26px; }
            .tab-btn { padding: 8px 18px; font-size: 13px; }
        }
    </style>
</head>
<body>

<div class="login-container">
    <!-- LEFT PANEL with Starfield -->
    <div class="panel-left">
        <canvas id="starfield"></canvas>

        <div class="brand">
            <h1>Check<span>Mate</span></h1>
            <div class="sub">by AttendanceHub</div>
        </div>

        <div class="tab-group" id="tabGroup">
            <button class="tab-btn active" data-tab="staff">Staff Login</button>
            <button class="tab-btn" data-tab="admin">Admin Login</button>
        </div>

        <div class="illustration">
            <div class="badge"><i class="fas fa-shield-alt"></i> Secure · Real‑time</div>
            <div class="icon-row">
                <i class="fas fa-clock"></i>
                <i class="fas fa-users"></i>
                <i class="fas fa-qrcode"></i>
            </div>
            <p>Live attendance tracking • Check in/out • Google Sheets integration</p>
        </div>
    </div>

    <!-- RIGHT PANEL -->
    <div class="panel-right">
        <div class="form-wrapper">
            <!-- ====== STAFF PANEL ====== -->
            <div id="panelStaff" class="form-panel active">
                <div class="greeting">
                    <h2>Welcome back</h2>
                    <p>Sign in with the credentials your admin emailed you.</p>
                </div>

                <button class="btn-google">
                    <i class="fab fa-google"></i> Continue with Google
                </button>

                <div class="divider"><span>or sign in manually</span></div>

                <form action="#" method="POST">
                    <div class="form-group">
                        <label for="staffEmail">Email address</label>
                        <div class="input-wrap">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="staffEmail" placeholder="qaasimdavids1@gmail.com" value="qaasimdavids1@gmail.com" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="staffPassword">Password</label>
                        <div class="input-wrap">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="staffPassword" placeholder="••••••••" value="password123" />
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

                <button class="btn-google">
                    <i class="fab fa-google"></i> Continue with Google
                </button>

                <div class="divider"><span>or sign in manually</span></div>

                <form action="#" method="POST">
                    <div class="form-group">
                        <label for="adminEmail">Email address</label>
                        <div class="input-wrap">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="adminEmail" placeholder="qaasimdavids1@gmail.com" value="qaasimdavids1@gmail.com" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="adminPassword">Password</label>
                        <div class="input-wrap">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="adminPassword" placeholder="••••••••" value="admin123" />
                        </div>
                    </div>
                    <div class="password-row">
                        <a href="#">Forgot password?</a>
                    </div>
                    <button type="submit" class="btn-signin">Sign in</button>
                </form>

                <div class="footnote">
                    Admin accounts are provisioned by another administrator. <span class="highlight">Contact your IT</span> if you need access.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Starfield Animation -->
<script>
    // ─── MOVING STARFIELD ANIMATION ───
    const canvas = document.getElementById('starfield');
    const ctx = canvas.getContext('2d');

    let stars = [];
    let width, height;

    function initStars() {
        const rect = canvas.parentElement.getBoundingClientRect();
        width = rect.width;
        height = rect.height;

        const dpr = window.devicePixelRatio || 1;
        canvas.width = width * dpr;
        canvas.height = height * dpr;
        canvas.style.width = width + 'px';
        canvas.style.height = height + 'px';
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
                ctx.shadowColor = `rgba(109, 138, 255, ${opacity * 0.12})`;
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

<!-- Login Form Handler + Tab Switching -->
<script src="/assets/js/login.js"></script>


</body>
<<<<<<< HEAD
</html>
=======
</html>
>>>>>>> origin/PortReferencingUpdate
