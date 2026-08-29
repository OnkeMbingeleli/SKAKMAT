<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_COOKIE['checkmate_token'])) {
    header('Location: /public/index.php?page=login');
    exit;
}

$user = !empty($_COOKIE['checkmate_user']) ? json_decode($_COOKIE['checkmate_user'], true) : [];
$role = ($user['role'] ?? $_SESSION['user_role'] ?? 'staff') === 'admin' ? 'admin' : 'staff';
$name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: ($_SESSION['user_name'] ?? 'there');
$_SESSION['user_role'] = $role;
$_SESSION['user_name'] = $name;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Attendance history | CheckMate</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/app.css">
    <style>
        :root { --navy:#152238; --ink:#172033; --muted:#778196; --line:#e8ebf1; --surface:#fff; --canvas:#f6f7fb; --primary:#6b7f32; --primary-dark:#526324; --success:#16836a; --warn:#a55c10; --danger:#c4455a; }
        * { box-sizing:border-box; }
        body { margin:0; background:var(--canvas); color:var(--ink); font-family:"DM Sans",sans-serif; }
        .attendance-page { min-width: 0; flex: 1; min-height:100vh; padding:42px 48px; }
        .page-heading { display:flex; align-items:flex-start; justify-content:space-between; gap:24px; margin:0 auto 30px; max-width:1400px; }
        .eyebrow { color:var(--primary); font-size:.75rem; font-weight:700; letter-spacing:.1em; margin:0 0 7px; text-transform:uppercase; }
        h1 { font:800 clamp(1.7rem,3vw,2.35rem)/1.15 "Plus Jakarta Sans",sans-serif; margin:0; letter-spacing:-.045em; }
        .subtitle { margin:10px 0 0; color:var(--muted); font-size:.98rem; }
        .date-pill { align-items:center; background:#fff; border:1px solid var(--line); border-radius:11px; color:#586276; display:flex; font-size:.82rem; font-weight:600; gap:8px; padding:11px 14px; white-space:nowrap; }
        .date-pill svg { color:var(--primary); }
        .summary-grid { display:grid; gap:16px; grid-template-columns:repeat(3,minmax(0,1fr)); margin:0 auto 25px; max-width:1400px; }
        .summary-card { background:var(--surface); border:1px solid var(--line); border-radius:15px; display:flex; gap:14px; min-width:0; padding:18px 20px; }
        .summary-icon { align-items:center; background:#e8eed2; border-radius:11px; color:var(--primary); display:flex; flex:0 0 42px; height:42px; justify-content:center; }
        .summary-label { color:var(--muted); font-size:.79rem; font-weight:600; margin:0 0 4px; }
        .summary-value { font:700 1.28rem/1.1 "Plus Jakarta Sans",sans-serif; margin:0; }
        .attendance-panel { background:var(--surface); border:1px solid var(--line); border-radius:17px; margin:0 auto; max-width:1400px; overflow:hidden; }
        .panel-top { align-items:center; border-bottom:1px solid var(--line); display:flex; gap:18px; justify-content:space-between; padding:24px 25px 20px; }
        .panel-title { font:700 1.05rem "Plus Jakarta Sans",sans-serif; margin:0; }
        .panel-note { color:var(--muted); font-size:.84rem; margin:5px 0 0; }
        .filter-form { align-items:end; background:#fbfcfe; border-bottom:1px solid var(--line); display:grid; gap:13px; grid-template-columns:150px 150px minmax(190px,1fr) auto; padding:17px 25px; }
        .field label { color:#657087; display:block; font-size:.72rem; font-weight:700; margin:0 0 6px; text-transform:uppercase; letter-spacing:.045em; }
        .field input { background:#fff; border:1px solid #dce1ea; border-radius:8px; color:var(--ink); font:500 .85rem "DM Sans",sans-serif; height:39px; outline:0; padding:0 10px; width:100%; }
        .field input:focus { border-color:var(--primary); box-shadow:0 0 0 3px #6b7f321a; }
        .filter-actions { display:flex; gap:8px; }
        button { border:0; border-radius:8px; cursor:pointer; font:700 .83rem "DM Sans",sans-serif; height:39px; padding:0 14px; }
        .filter-btn { background:var(--primary); color:#fff; } .filter-btn:hover { background:var(--primary-dark); }
        .reset-btn { background:#fff; border:1px solid #dce1ea; color:#06543a; }
        .table-wrap { overflow-x:auto; }
        table { border-collapse:collapse; min-width:760px; width:100%; }
        th { background:#fff; color:#7a8496; font-size:.71rem; font-weight:700; letter-spacing:.055em; padding:16px 25px 12px; text-align:left; text-transform:uppercase; white-space:nowrap; }
        td { border-top:1px solid #eef0f4; color:#465166; font-size:.88rem; padding:17px 25px; vertical-align:middle; white-space:nowrap; }
        tbody tr:hover { background:#fafbff; }
        .employee { align-items:center; color:#253149; display:flex; font-weight:700; gap:10px; }
        .avatar { align-items:center; background:#e8eed2; border-radius:50%; color:var(--primary); display:inline-flex; font-size:.72rem; height:31px; justify-content:center; width:31px; }
        .time { color:#303b50; font-weight:600; } .time.muted { color:#99a2b2; font-weight:500; }
        .hours { color:#303b50; font-weight:700; }
        .status { border-radius:99px; display:inline-block; font-size:.73rem; font-weight:700; line-height:1; padding:7px 10px; }
        .status-present { background:#e8f7f1; color:var(--success); } .status-late { background:#fff3df; color:var(--warn); } .status-absent { background:#ffeaed; color:var(--danger); } .status-active { background:#e8f1ff; color:#336cbd; }
        .empty-row td { color:var(--muted); padding:54px 25px; text-align:center; }
        .empty-icon { align-items:center; background:#e8eed2; border-radius:50%; color:var(--primary); display:flex; height:42px; justify-content:center; margin:0 auto 10px; width:42px; }
        .loading-row td { color:#99a2b2; padding:45px; text-align:center; }
        .loading-dot { animation:pulse 1.1s ease-in-out infinite; background:var(--primary); border-radius:100%; display:inline-block; height:7px; margin-right:7px; width:7px; } @keyframes pulse { 50% { opacity:.25; transform:scale(.65); } }
        .error-box { background:#fff5f6; border-bottom:1px solid #ffd9df; color:#ab3347; display:none; font-size:.84rem; margin:0; padding:12px 25px; }
        .error-box.visible { display:block; }
        /* Admin dashboard treatment (staff keeps the existing personal view). */
        .is-admin { background:#f8fafc; }
        .is-admin .attendance-page { max-width:none; padding:0 40px 42px; }
        .admin-topbar { align-items:center; background:#fff; border-bottom:1px solid #e1e7f0; display:flex; gap:22px; justify-content:space-between; margin:0 -40px 0; min-height:98px; padding:0 40px; }
        .admin-global-search { align-items:center; background:#f8fafc; border:1px solid #dce5f1; border-radius:14px; color:#8290a6; display:flex; gap:11px; max-width:400px; padding:0 17px; width:100%; }
        .admin-global-search input { background:transparent; border:0; color:#14223b; font:500 .95rem "DM Sans",sans-serif; height:48px; outline:0; width:100%; }
        .admin-global-search input::placeholder { color:#93a0b5; }
        .admin-top-actions { align-items:center; display:flex; gap:16px; }
        .admin-live-time { color:#10203b; font:800 1.16rem/1 "Plus Jakarta Sans",sans-serif; letter-spacing:.04em; text-align:right; }
        .admin-live-time small { color:#607494; display:block; font:500 .75rem "DM Sans",sans-serif; letter-spacing:0; margin-top:5px; }
        .admin-avatar { align-items:center; background:#6b7f32; border-radius:50%; color:#fff; display:flex; font-weight:700; height:42px; justify-content:center; width:42px; }
        .admin-profile { align-items:center; background:#fff; border:1px solid #dce5f1; border-radius:14px; display:flex; gap:10px; padding:7px 15px 7px 8px; }
        .admin-profile strong { display:block; font-size:.88rem; } .admin-profile span { color:#66758e; display:block; font-size:.75rem; }
        .admin-hero { align-items:flex-end; display:flex; justify-content:space-between; padding:41px 0 30px; }
        .admin-hero h1 { font-size:2rem; } .admin-hero .subtitle { color:#59708f; }
        .admin-clock { color:#071d40; font:800 2.55rem/1 "Plus Jakarta Sans",sans-serif; letter-spacing:.06em; text-align:right; }
        .admin-clock span { color:#5f7698; display:block; font:500 .88rem "DM Sans",sans-serif; letter-spacing:0; margin-top:10px; }
        .is-admin .summary-grid { gap:20px; grid-template-columns:repeat(4,minmax(0,1fr)); margin-bottom:28px; max-width:none; }
        .is-admin .summary-card { border-color:#dfe7f1; border-radius:19px; display:block; min-height:164px; padding:25px 23px 20px; }
        .admin-card-label { align-items:center; color:#657791; display:flex; font-size:.89rem; font-weight:700; gap:9px; margin:0 0 23px; }
        .admin-card-icon { font-size:1.1rem; line-height:1; } .admin-card-icon.teal { color:#068a89; } .admin-card-icon.green { color:#14b86b; } .admin-card-icon.red { color:#ff4b5b; } .admin-card-icon.orange { color:#ff9d18; }
        .is-admin .summary-value { color:#111a31; font-size:2.28rem; letter-spacing:-.07em; }
        .admin-card-foot { font-size:.79rem; font-weight:700; margin:17px 0 0; } .admin-card-foot.teal { color:#07817f; } .admin-card-foot.green { color:#12ad64; } .admin-card-foot.red { color:#fb4153; } .admin-card-foot.orange { color:#f59712; }
        .is-admin .attendance-panel { border-color:#dfe7f1; border-radius:19px; max-width:none; }
        .is-admin .panel-top { padding:24px 25px; } .is-admin .panel-title { font-size:1.08rem; }
        .is-admin .filter-form { grid-template-columns:150px 150px minmax(200px,1fr) auto; }
        @media(max-width:1000px) { .attendance-page{padding:32px 28px;} .filter-form{grid-template-columns:1fr 1fr;} }
        @media(max-width:900px) { .is-admin .summary-grid{grid-template-columns:1fr 1fr;} .admin-topbar{padding:0 26px;margin:0 -28px;} .is-admin .attendance-page{padding:0 28px 30px;} .admin-profile>div{display:none;} }
        @media(max-width:768px) { .attendance-page{padding:76px 16px 24px;} .page-heading{align-items:flex-start;flex-direction:column;margin-bottom:22px;gap:14px;} .summary-grid{grid-template-columns:1fr;gap:10px;margin-bottom:16px;} .summary-card{padding:14px 16px;} .panel-top{padding:19px 17px;} .filter-form{grid-template-columns:1fr;padding:15px 17px;} .filter-actions button{flex:1;} th,td{padding-left:17px;padding-right:17px;} .is-admin .attendance-page{padding:0 16px 24px;} .admin-topbar{margin:0 -16px;padding:12px 16px;min-height:auto;} .admin-global-search{max-width:none;} .admin-live-time,.admin-profile{display:none;} .admin-hero{align-items:flex-start;flex-direction:column;gap:20px;padding:30px 0 24px;} .admin-clock{text-align:left;font-size:2rem;} .is-admin .summary-grid{grid-template-columns:1fr;gap:10px;} .is-admin .summary-card{min-height:0;padding:18px;} }
        /* The shared header partial sits inside .attendance-page's own padding
           on the staff view — pull it flush to the edges like the admin topbar. */
        .attendance-page > .topbar { margin: -42px -48px 30px; }
        @media(max-width:1000px) { .attendance-page > .topbar { margin: -32px -28px 26px; } }
        @media(max-width:768px) { .attendance-page > .topbar { margin: -76px -16px 20px; } }
    </style>
</head>
<body class="<?= $role === 'admin' ? 'is-admin' : '' ?>" data-role="<?= htmlspecialchars($role) ?>" data-user-name="<?= htmlspecialchars($name) ?>">
<div class="app-shell">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>
    <main class="attendance-page">
        <?php if ($role === 'admin'): ?>
        <header class="admin-topbar">
            <label class="admin-global-search"><span aria-hidden="true">⌕</span><input id="adminQuickSearch" type="search" placeholder="Search employees, records..."></label>
            <div class="admin-top-actions"><div class="admin-live-time" id="adminHeaderTime">--:--:--<small id="adminHeaderDate"></small></div><div class="admin-profile"><span class="admin-avatar"><?= htmlspecialchars(strtoupper(substr($name, 0, 1))) ?></span><div><strong><?= htmlspecialchars($name) ?></strong><span>Administrator</span></div></div></div>
        </header>
        <header class="admin-hero">
            <div><h1>Attendance History</h1><p class="subtitle">Review and manage your team's attendance records.</p></div>
            <div class="admin-clock" id="adminHeroTime">--:--:--<span id="adminHeroDate"></span></div>
        </header>
        <?php else: ?>
        <?php include __DIR__ . '/partials/header.php'; ?>
        <header class="page-heading">
            <div>
                <p class="eyebrow" id="roleLabel"><?= $role === 'admin' ? 'Team overview' : 'Personal overview' ?></p>
                <h1 id="pageTitle"><?= $role === 'admin' ? 'Attendance history' : 'My attendance' ?></h1>
                <p class="subtitle" id="pageSubtitle"><?= $role === 'admin' ? 'Review your team’s clock-in and clock-out activity.' : 'A record of your clock-in and clock-out activity.' ?></p>
            </div>
            <div class="date-pill"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M8 2v4M16 2v4M3 10h18"/></svg><span id="todayLabel"></span></div>
        </header>
        <?php endif; ?>

        <section class="summary-grid" aria-label="Attendance summary">
            <?php if ($role === 'admin'): ?>
            <article class="summary-card"><p class="admin-card-label"><span class="admin-card-icon teal">♙</span>Employees Onsite</p><p class="summary-value" id="adminOnsite">—</p><p class="admin-card-foot teal">Currently clocked in</p></article>
            <article class="summary-card"><p class="admin-card-label"><span class="admin-card-icon green">⊙</span>Checked In Today</p><p class="summary-value" id="adminCheckedIn">—</p><p class="admin-card-foot green">Attendance recorded</p></article>
            <article class="summary-card"><p class="admin-card-label"><span class="admin-card-icon red">⊗</span>Checked Out</p><p class="summary-value" id="adminCheckedOut">—</p><p class="admin-card-foot red">Completed shifts</p></article>
            <article class="summary-card"><p class="admin-card-label"><span class="admin-card-icon orange">△</span>Late Employees</p><p class="summary-value" id="adminLate">—</p><p class="admin-card-foot orange">After scheduled start</p></article>
            <?php else: ?>
            <article class="summary-card"><div class="summary-icon">✓</div><div><p class="summary-label" id="recordsLabel">Total records</p><p class="summary-value" id="totalRecords">—</p></div></article>
            <article class="summary-card"><div class="summary-icon">◷</div><div><p class="summary-label">Hours recorded</p><p class="summary-value" id="totalHours">—</p></div></article>
            <article class="summary-card"><div class="summary-icon">↗</div><div><p class="summary-label">On-time rate</p><p class="summary-value" id="onTimeRate">—</p></div></article>
            <?php endif; ?>
        </section>

        <section class="attendance-panel" aria-labelledby="historyHeading">
            <div class="panel-top"><div><h2 class="panel-title" id="historyHeading">Attendance records</h2><p class="panel-note" id="recordNote">Loading attendance history…</p></div></div>
            <p class="error-box" id="attendanceError" role="alert"></p>
            <form class="filter-form" id="filterForm" <?= $role === 'admin' ? '' : 'hidden' ?>>
                <div class="field"><label for="startDate">From</label><input type="date" id="startDate" name="start_date"></div>
                <div class="field"><label for="endDate">To</label><input type="date" id="endDate" name="end_date"></div>
                <div class="field"><label for="searchEmployee">Employee</label><input type="search" id="searchEmployee" name="search" placeholder="Search by employee name"></div>
                <div class="filter-actions"><button class="filter-btn" type="submit">Apply filters</button><button class="reset-btn" id="resetBtn" type="button">Reset</button></div>
            </form>
            <div class="table-wrap"><table><thead><tr>
                <?php if ($role === 'admin'): ?><th class="employee-column">Employee</th><?php endif; ?>
                <th>Date</th><th>Clock in</th><th>Clock out</th><th>Total hours</th><th>Status</th>
            </tr></thead><tbody id="attendanceTableBody"><tr class="loading-row"><td colspan="<?= $role === 'admin' ? 6 : 5 ?>"><span class="loading-dot"></span>Loading records</td></tr></tbody></table></div>
        </section>
    </main>
</div>
    <script src="/assets/js/config.js"></script>
    <script src="/assets/js/app-preferences.js"></script>
    <script src="/assets/js/attendanceLog.js"></script>
</body>
</html>
