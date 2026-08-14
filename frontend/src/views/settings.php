<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| LOAD USER FROM COOKIE
|--------------------------------------------------------------------------
*/

if (!empty($_COOKIE['checkmate_user'])) {

    $user = json_decode($_COOKIE['checkmate_user'], true);

    if (is_array($user)) {

        $_SESSION['user_role'] =
            $user['role'] ?? ($_SESSION['user_role'] ?? 'staff');

        $_SESSION['user_first_name'] =
            $user['first_name'] ?? ($_SESSION['user_first_name'] ?? 'User');

        $_SESSION['user_last_name'] =
            $user['last_name'] ?? ($_SESSION['user_last_name'] ?? '');

        $_SESSION['user_email'] =
            $user['email'] ?? ($_SESSION['user_email'] ?? '');

        $_SESSION['user_department'] =
            $user['department'] ?? ($_SESSION['user_department'] ?? '');

        $_SESSION['user_position'] =
            $user['position'] ?? ($_SESSION['user_position'] ?? '');

        $_SESSION['user_dark_mode'] =
            !empty($user['dark_mode']);
    }
}


/*
|--------------------------------------------------------------------------
| DEFAULT SESSION VALUES
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['user_role'])) {
    $_SESSION['user_role'] = 'staff';
}

if (!isset($_SESSION['user_first_name'])) {
    $_SESSION['user_first_name'] =
        $_SESSION['user_role'] === 'admin'
            ? 'Admin'
            : 'Staff';
}

if (!isset($_SESSION['user_last_name'])) {
    $_SESSION['user_last_name'] = 'User';
}

if (!isset($_SESSION['user_email'])) {
    $_SESSION['user_email'] = '';
}

if (!isset($_SESSION['user_department'])) {
    $_SESSION['user_department'] = '';
}

if (!isset($_SESSION['user_position'])) {
    $_SESSION['user_position'] = '';
}

if (!isset($_SESSION['user_dark_mode'])) {
    $_SESSION['user_dark_mode'] = false;
}


/*
|--------------------------------------------------------------------------
| USER INFORMATION
|--------------------------------------------------------------------------
*/

$isAdmin =
    $_SESSION['user_role'] === 'admin';

$firstName =
    trim($_SESSION['user_first_name']);

$lastName =
    trim($_SESSION['user_last_name']);

$fullName =
    trim($firstName . ' ' . $lastName);

if ($fullName === '') {
    $fullName = $isAdmin
        ? 'Admin User'
        : 'Staff User';
}


/*
|--------------------------------------------------------------------------
| INITIALS
|--------------------------------------------------------------------------
*/

$initials = '';

$nameParts =
    preg_split('/\s+/', $fullName);

foreach ($nameParts as $part) {

    if ($part !== '') {
        $initials .= strtoupper(
            substr($part, 0, 1)
        );
    }
}

$initials =
    substr($initials, 0, 2);

if ($initials === '') {
    $initials = 'U';
}


/*
|--------------------------------------------------------------------------
| PROFILE SUBTITLE
|--------------------------------------------------------------------------
*/

if ($isAdmin) {

    $displaySubtitle =
        'Administrator';

} else {

    $department =
        trim($_SESSION['user_department']);

    $position =
        trim($_SESSION['user_position']);

    $parts = [];

    if ($department !== '') {
        $parts[] = $department;
    }

    if ($position !== '') {
        $parts[] = $position;
    }

    $displaySubtitle =
        !empty($parts)
            ? implode(' · ', $parts)
            : 'Staff';
}


/*
|--------------------------------------------------------------------------
| DARK MODE
|--------------------------------------------------------------------------
*/

$darkModeOn =
    !empty($_SESSION['user_dark_mode']);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Settings · CheckMate</title>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"
    >

    <style>

        /* =====================================================
           RESET
        ===================================================== */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        /* =====================================================
           BODY
        ===================================================== */

        body {

            font-family:
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                Roboto,
                "Helvetica Neue",
                Arial,
                sans-serif;

            background: #f5f7fa;

            color: #1d2a3a;

            transition:
                background .2s ease,
                color .2s ease;
        }


        /* =====================================================
           PAGE
        ===================================================== */

        .page-shell {

            margin-left: 280px;

            padding:
                30px
                32px
                60px;
        }


        /* =====================================================
           PAGE HEADER
        ===================================================== */

        .settings-header {

            margin-bottom: 24px;
        }

        .settings-header h1 {

            font-size: 26px;

            font-weight: 700;

            letter-spacing: -.3px;
        }

        .settings-header p {

            margin-top: 5px;

            color: #6b7a8d;

            font-size: 14.5px;
        }


        /* =====================================================
           GRID
        ===================================================== */

        .settings-grid {

            display: grid;

            grid-template-columns:
                minmax(0, 1fr)
                minmax(0, 1fr);

            gap: 20px;

            width: 100%;

            max-width: 1100px;
        }


        /* =====================================================
           CARD
        ===================================================== */

        .card {

            background: #ffffff;

            border:
                1px solid
                #e4e7ed;

            border-radius: 10px;

            box-shadow:
                0 1px 3px
                rgba(0, 0, 0, .05);

            padding:
                24px 26px;

            transition:
                background .2s ease,
                border-color .2s ease;
        }

        .card h2 {

            margin-bottom: 18px;

            font-size: 16px;

            font-weight: 600;
        }


        /* =====================================================
           PROFILE
        ===================================================== */

        .profile-identity {

            display: flex;

            align-items: center;

            gap: 12px;

            margin-bottom: 22px;
        }


        .avatar {

            width: 44px;

            height: 44px;

            display: flex;

            align-items: center;

            justify-content: center;

            flex-shrink: 0;

            border-radius: 50%;

            background:
                <?= $isAdmin
                    ? '#7c3aed'
                    : '#0d9488'
                ?>;

            color: #ffffff;

            font-size: 15px;

            font-weight: 700;
        }


        .profile-identity h3 {

            font-size: 16px;

            font-weight: 600;
        }


        .profile-identity span {

            display: block;

            margin-top: 2px;

            color: #6b7a8d;

            font-size: 13px;
        }


        /* =====================================================
           FORM
        ===================================================== */

        .name-row {

            display: grid;

            grid-template-columns:
                1fr
                1fr;

            gap: 12px;
        }


        .form-group {

            margin-bottom: 16px;
        }


        .form-group label {

            display: block;

            margin-bottom: 6px;

            color: #3a4657;

            font-size: 13.5px;

            font-weight: 600;
        }


        .form-group input {

            width: 100%;

            height: 42px;

            padding:
                10px
                14px;

            border:
                1px solid
                #dde1e8;

            border-radius: 8px;

            outline: none;

            background: #f8f9fb;

            color: #1d2a3a;

            font-size: 14.5px;

            transition:
                border-color .15s ease,
                background .15s ease,
                box-shadow .15s ease;
        }


        .form-group input:focus {

            border-color: #0d9488;

            background: #ffffff;

            box-shadow:
                0 0 0 3px
                rgba(13, 148, 136, .08);
        }


        .form-group input::placeholder {

            color: #9aa5b1;
        }


        /* =====================================================
           BUTTON
        ===================================================== */

        .btn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            min-height: 40px;

            padding:
                9px
                20px;

            border: none;

            border-radius: 8px;

            font-size: 14px;

            font-weight: 600;

            cursor: pointer;

            transition:
                background .15s ease,
                transform .05s ease,
                opacity .15s ease;
        }


        .btn:active {

            transform:
                translateY(1px);
        }


        .btn-primary {

            background: #0f766e;

            color: #ffffff;
        }


        .btn-primary:hover {

            background: #0d5f58;
        }


        .btn:disabled {

            opacity: .65;

            cursor: not-allowed;
        }


        /* =====================================================
           PREFERENCES
        ===================================================== */

        .pref-row {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            padding:
                14px 0;

            border-bottom:
                1px solid
                #f0f1f5;
        }


        .pref-row:last-child {

            border-bottom: none;
        }


        .pref-row h4 {

            font-size: 14.5px;

            font-weight: 600;
        }


        .pref-row p {

            margin-top: 3px;

            color: #6b7a8d;

            font-size: 13px;

            line-height: 1.4;
        }


        /* =====================================================
           SWITCH
        ===================================================== */

        .switch {

            position: relative;

            width: 42px;

            height: 24px;

            flex-shrink: 0;
        }


        .switch input {

            width: 0;

            height: 0;

            opacity: 0;
        }


        .slider {

            position: absolute;

            inset: 0;

            border-radius: 24px;

            background: #cfd4dc;

            cursor: pointer;

            transition:
                background .2s ease;
        }


        .slider::before {

            content: "";

            position: absolute;

            width: 18px;

            height: 18px;

            top: 3px;

            left: 3px;

            border-radius: 50%;

            background: #ffffff;

            box-shadow:
                0 1px 2px
                rgba(0, 0, 0, .2);

            transition:
                transform .2s ease;
        }


        .switch input:checked
        + .slider {

            background: #0f766e;
        }


        .switch input:checked
        + .slider::before {

            transform:
                translateX(18px);
        }


        .switch input:disabled
        + .slider {

            opacity: .55;

            cursor: not-allowed;
        }


        /* =====================================================
           PASSWORD
        ===================================================== */

        .card-password {

            grid-column: 1 / -1;

            width: 100%;

            max-width: 540px;
        }


        /* =====================================================
           ERRORS
        ===================================================== */

        .field-error {

            display: none;

            margin-top: 6px;

            color: #c0392b;

            font-size: 12.5px;
        }


        .field-error.show {

            display: block;
        }


        /* =====================================================
           TOAST
        ===================================================== */

        .toast {

            position: fixed;

            right: 28px;

            bottom: 28px;

            z-index: 5000;

            max-width: 360px;

            padding:
                12px
                20px;

            border-radius: 8px;

            background: #1d2a3a;

            color: #ffffff;

            font-size: 14px;

            box-shadow:
                0 8px 24px
                rgba(0, 0, 0, .25);

            opacity: 0;

            transform:
                translateY(10px);

            pointer-events: none;

            transition:
                opacity .25s ease,
                transform .25s ease;
        }


        .toast.show {

            opacity: 1;

            transform:
                translateY(0);
        }


        .toast.error {

            background: #c0392b;
        }


        /* =====================================================
           DARK MODE
        ===================================================== */

        body.dark {

            background: #12181f;

            color: #e6e9ee;
        }


        body.dark .settings-header p {

            color: #9aa5b1;
        }


        body.dark .card {

            background: #1a212b;

            border-color: #2a323d;

            box-shadow:
                0 1px 3px
                rgba(0, 0, 0, .2);
        }


        body.dark .profile-identity h3,
        body.dark .card h2,
        body.dark .pref-row h4 {

            color: #f1f3f6;
        }


        body.dark .profile-identity span,
        body.dark .pref-row p,
        body.dark .form-group label {

            color: #9aa5b1;
        }


        body.dark .form-group input {

            background: #12181f;

            border-color: #2a323d;

            color: #e6e9ee;
        }


        body.dark .form-group input:focus {

            background: #1a212b;

            border-color: #0d9488;
        }


        body.dark .pref-row {

            border-bottom-color:
                #2a323d;
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 1100px) {

            .page-shell {

                margin-left: 240px;

                padding:
                    28px 24px 50px;
            }
        }


        @media (max-width: 900px) {

            .page-shell {

                margin-left: 0;

                padding:
                    90px 20px 40px;
            }


            .settings-grid {

                grid-template-columns: 1fr;
            }


            .card-password {

                grid-column: auto;

                max-width: none;
            }
        }


        @media (max-width: 600px) {

            .page-shell {

                padding:
                    85px 15px 35px;
            }


            .settings-header h1 {

                font-size: 23px;
            }


            .settings-header p {

                font-size: 13.5px;
            }


            .card {

                padding:
                    20px;
            }


            .name-row {

                grid-template-columns: 1fr;

                gap: 0;
            }


            .pref-row {

                align-items: flex-start;
            }


            .pref-row p {

                max-width: 220px;
            }


            .toast {

                right: 15px;

                bottom: 15px;

                left: 15px;

                max-width: none;
            }
        }

    </style>

</head>


<body>

<?php
/*
|--------------------------------------------------------------------------
| SIDEBAR
|--------------------------------------------------------------------------
*/

include __DIR__ . '/partials/sidebar.php';


/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

include __DIR__ . '/partials/header.php';
?>


<!-- =========================================================
     SETTINGS PAGE
========================================================= -->

<main class="page-shell">

    <div class="settings-header">

        <h1>
            Settings
        </h1>

        <p>
            Manage your profile and preferences.
        </p>

    </div>


    <div
        class="settings-grid"
        id="settingsGrid"
    >


        <!-- =================================================
             PROFILE
        ================================================== -->

        <section class="card">

            <h2>
                Profile
            </h2>


            <div class="profile-identity">

                <div
                    class="avatar"
                    id="avatarInitials"
                >
                    <?= htmlspecialchars($initials) ?>
                </div>


                <div>

                    <h3 id="displayName">

                        <?= htmlspecialchars($fullName) ?>

                    </h3>


                    <span id="displaySubtitle">

                        <?= htmlspecialchars($displaySubtitle) ?>

                    </span>

                </div>

            </div>


            <form id="profileForm">

                <div class="name-row">


                    <div class="form-group">

                        <label for="firstName">
                            First name
                        </label>

                        <input
                            type="text"
                            id="firstName"
                            name="firstName"
                            autocomplete="given-name"
                            value="<?= htmlspecialchars($firstName) ?>"
                        >

                    </div>


                    <div class="form-group">

                        <label for="lastName">
                            Last name
                        </label>

                        <input
                            type="text"
                            id="lastName"
                            name="lastName"
                            autocomplete="family-name"
                            value="<?= htmlspecialchars($lastName) ?>"
                        >

                    </div>

                </div>


                <div class="form-group">

                    <label for="email">
                        Email
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        autocomplete="email"
                        value="<?= htmlspecialchars($_SESSION['user_email']) ?>"
                    >

                    <div
                        class="field-error"
                        id="profileError"
                    ></div>

                </div>


                <button
                    type="submit"
                    class="btn btn-primary"
                    id="saveProfileBtn"
                >

                    <span id="saveProfileText">
                        Save changes
                    </span>

                </button>

            </form>

        </section>


        <!-- =================================================
             PREFERENCES
        ================================================== -->

        <section class="card">

            <h2>
                Preferences
            </h2>


            <!-- DARK MODE -->

            <div class="pref-row">

                <div>

                    <h4>
                        Dark mode
                    </h4>

                    <p>
                        Switch to a darker interface
                    </p>

                </div>


                <label class="switch">

                    <input
                        type="checkbox"
                        id="prefDarkMode"
                        data-pref="dark_mode"
                        <?= $darkModeOn ? 'checked' : '' ?>
                    >

                    <span class="slider"></span>

                </label>

            </div>


            <!-- EMAIL NOTIFICATIONS -->

            <div class="pref-row">

                <div>

                    <h4>
                        Email notifications
                    </h4>

                    <p>
                        Daily summary to your inbox
                    </p>

                </div>


                <label class="switch">

                    <input
                        type="checkbox"
                        id="prefEmailNotifications"
                        data-pref="email_notifications"
                        checked
                    >

                    <span class="slider"></span>

                </label>

            </div>


            <!-- WEEKLY REPORT -->

            <div class="pref-row">

                <div>

                    <h4>
                        Weekly report email
                    </h4>

                    <p>
                        Sent every Monday morning
                    </p>

                </div>


                <label class="switch">

                    <input
                        type="checkbox"
                        id="prefWeeklyReport"
                        data-pref="weekly_report_email"
                        checked
                    >

                    <span class="slider"></span>

                </label>

            </div>

        </section>


        <!-- =================================================
             CHANGE PASSWORD
        ================================================== -->

        <section class="card card-password">

            <h2>
                Change Password
            </h2>


            <form id="passwordForm">


                <div class="form-group">

                    <label for="currentPassword">
                        Current password
                    </label>

                    <input
                        type="password"
                        id="currentPassword"
                        placeholder="Enter current password"
                        autocomplete="current-password"
                    >

                </div>


                <div class="form-group">

                    <label for="newPassword">
                        New password
                    </label>

                    <input
                        type="password"
                        id="newPassword"
                        placeholder="At least 8 characters"
                        autocomplete="new-password"
                    >

                </div>


                <div class="form-group">

                    <label for="confirmPassword">
                        Confirm new password
                    </label>

                    <input
                        type="password"
                        id="confirmPassword"
                        placeholder="Re-enter new password"
                        autocomplete="new-password"
                    >

                    <div
                        class="field-error"
                        id="passwordError"
                    ></div>

                </div>


                <button
                    type="submit"
                    class="btn btn-primary"
                    id="updatePasswordBtn"
                >

                    <i class="fas fa-key"></i>

                    <span id="updatePasswordText">
                        Update password
                    </span>

                </button>

            </form>

        </section>

    </div>

</main>


<!-- =========================================================
     TOAST
========================================================= -->

<div
    class="toast"
    id="toast"
></div>


<!-- =========================================================
     JAVASCRIPT FILES
========================================================= -->

<script src="/assets/js/api.js"></script>

<script src="/assets/js/login.js"></script>


<script>

/*
|--------------------------------------------------------------------------
| SETTINGS JAVASCRIPT
|--------------------------------------------------------------------------
*/

(function () {

    'use strict';


    /*
    |--------------------------------------------------------------------------
    | ELEMENTS
    |--------------------------------------------------------------------------
    */

    const profileForm =
        document.getElementById('profileForm');

    const passwordForm =
        document.getElementById('passwordForm');

    const darkModeSwitch =
        document.getElementById('prefDarkMode');

    const headerDarkModeBtn =
        document.getElementById('headerDarkModeBtn');


    /*
    |--------------------------------------------------------------------------
    | TOAST
    |--------------------------------------------------------------------------
    */

    let toastTimer = null;


    function showToast(
        message,
        isError = false
    ) {

        const toast =
            document.getElementById('toast');

        if (!toast) {
            return;
        }

        toast.textContent =
            message;

        toast.classList.toggle(
            'error',
            isError
        );

        toast.classList.add(
            'show'
        );

        clearTimeout(
            toastTimer
        );

        toastTimer =
            setTimeout(
                function () {

                    toast.classList.remove(
                        'show'
                    );

                },
                3000
            );
    }


    /*
    |--------------------------------------------------------------------------
    | FIELD ERROR
    |--------------------------------------------------------------------------
    */

    function setFieldError(
        id,
        message
    ) {

        const element =
            document.getElementById(id);

        if (!element) {
            return;
        }

        if (message) {

            element.textContent =
                message;

            element.classList.add(
                'show'
            );

        } else {

            element.textContent =
                '';

            element.classList.remove(
                'show'
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | APPLY DARK MODE
    |--------------------------------------------------------------------------
    */

    function applyDarkMode(
        enabled,
        saveLocal = true
    ) {

        const isEnabled =
            Boolean(enabled);


        document.body.classList.toggle(
            'dark',
            isEnabled
        );


        if (darkModeSwitch) {

            darkModeSwitch.checked =
                isEnabled;
        }


        if (headerDarkModeBtn) {

            headerDarkModeBtn.classList.toggle(
                'active',
                isEnabled
            );
        }


        if (saveLocal) {

            localStorage.setItem(
                'checkmate_dark_mode',
                isEnabled
                    ? 'true'
                    : 'false'
            );
        }

    }


    /*
    |--------------------------------------------------------------------------
    | INITIAL DARK MODE
    |--------------------------------------------------------------------------
    */

    const savedDarkMode =
        localStorage.getItem(
            'checkmate_dark_mode'
        );


    if (savedDarkMode !== null) {

        applyDarkMode(
            savedDarkMode === 'true',
            false
        );

    } else {

        applyDarkMode(
            <?= $darkModeOn ? 'true' : 'false' ?>,
            false
        );

    }


    /*
    |--------------------------------------------------------------------------
    | PROFILE UI
    |--------------------------------------------------------------------------
    */

    function applyProfileToPage(
        user
    ) {

        if (!user) {
            return;
        }


        const firstName =
            user.first_name || '';


        const lastName =
            user.last_name || '';


        const email =
            user.email || '';


        const fullName =
            `${firstName} ${lastName}`
                .trim();


        /*
        |------------------------------------------------------
        | Inputs
        |------------------------------------------------------
        */

        const firstNameInput =
            document.getElementById(
                'firstName'
            );

        const lastNameInput =
            document.getElementById(
                'lastName'
            );

        const emailInput =
            document.getElementById(
                'email'
            );


        if (firstNameInput) {

            firstNameInput.value =
                firstName;
        }


        if (lastNameInput) {

            lastNameInput.value =
                lastName;
        }


        if (emailInput) {

            emailInput.value =
                email;
        }


        /*
        |------------------------------------------------------
        | Display name
        |------------------------------------------------------
        */

        if (fullName) {

            const displayName =
                document.getElementById(
                    'displayName'
                );

            if (displayName) {

                displayName.textContent =
                    fullName;
            }


            /*
            |--------------------------------------------------
            | Initials
            |--------------------------------------------------
            */

            const initials =
                (
                    (firstName
                        ? firstName.charAt(0)
                        : '') +

                    (lastName
                        ? lastName.charAt(0)
                        : '')
                ).toUpperCase();


            const avatar =
                document.getElementById(
                    'avatarInitials'
                );


            if (
                avatar &&
                initials
            ) {

                avatar.textContent =
                    initials;
            }

        }


        /*
        |------------------------------------------------------
        | Subtitle
        |------------------------------------------------------
        */

        if (user.role) {

            const subtitle =
                user.role === 'admin'
                    ? 'Administrator'
                    : [
                        user.department,
                        user.position
                    ]
                        .filter(Boolean)
                        .join(' · ')
                        || 'Staff';


            const displaySubtitle =
                document.getElementById(
                    'displaySubtitle'
                );


            if (displaySubtitle) {

                displaySubtitle.textContent =
                    subtitle;
            }
        }


        /*
        |------------------------------------------------------
        | Preferences
        |------------------------------------------------------
        */

        if (
            Object.prototype.hasOwnProperty.call(
                user,
                'dark_mode'
            )
        ) {

            applyDarkMode(
                Boolean(user.dark_mode)
            );
        }


        const emailNotifications =
            document.getElementById(
                'prefEmailNotifications'
            );


        if (
            emailNotifications &&
            Object.prototype.hasOwnProperty.call(
                user,
                'email_notifications'
            )
        ) {

            emailNotifications.checked =
                Boolean(
                    user.email_notifications
                );
        }


        const weeklyReport =
            document.getElementById(
                'prefWeeklyReport'
            );


        if (
            weeklyReport &&
            Object.prototype.hasOwnProperty.call(
                user,
                'weekly_report_email'
            )
        ) {

            weeklyReport.checked =
                Boolean(
                    user.weekly_report_email
                );
        }


        /*
        |------------------------------------------------------
        | Keep local storage + cookie updated
        |------------------------------------------------------
        */

        try {

            const existingUser =
                typeof getUser === 'function'
                    ? (getUser() || {})
                    : {};


            const mergedUser = {

                ...existingUser,

                ...user
            };


            localStorage.setItem(
                'user',
                JSON.stringify(
                    mergedUser
                )
            );


            document.cookie =
                'checkmate_user=' +
                encodeURIComponent(
                    JSON.stringify(
                        mergedUser
                    )
                ) +
                '; path=/; max-age=3600';

        }
        catch (error) {

            console.warn(
                'Could not update local user data:',
                error
            );

        }

    }


    /*
    |--------------------------------------------------------------------------
    | LOAD PROFILE
    |--------------------------------------------------------------------------
    */

    async function loadProfile() {

        if (
            typeof getProfile !==
            'function'
        ) {

            return;
        }


        try {

            const result =
                await getProfile();


            if (
                result &&
                result.success &&
                result.data
            ) {

                applyProfileToPage(
                    result.data
                );
            }

        }
        catch (error) {

            console.warn(
                'Profile could not be loaded:',
                error
            );

        }

    }


    /*
    |--------------------------------------------------------------------------
    | PROFILE FORM
    |--------------------------------------------------------------------------
    */

    if (profileForm) {

        profileForm.addEventListener(
            'submit',
            async function (event) {

                event.preventDefault();


                setFieldError(
                    'profileError',
                    ''
                );


                const firstName =
                    document
                        .getElementById(
                            'firstName'
                        )
                        .value
                        .trim();


                const lastName =
                    document
                        .getElementById(
                            'lastName'
                        )
                        .value
                        .trim();


                const email =
                    document
                        .getElementById(
                            'email'
                        )
                        .value
                        .trim();


                /*
                |------------------------------------------------
                | Validation
                |------------------------------------------------
                */

                if (!firstName) {

                    setFieldError(
                        'profileError',
                        'First name is required.'
                    );

                    return;
                }


                if (!lastName) {

                    setFieldError(
                        'profileError',
                        'Last name is required.'
                    );

                    return;
                }


                if (
                    !email ||
                    !/^[^\s@]+@[^\s@]+\.[^\s@]+$/
                        .test(email)
                ) {

                    setFieldError(
                        'profileError',
                        'Enter a valid email address.'
                    );

                    return;
                }


                const button =
                    document.getElementById(
                        'saveProfileBtn'
                    );


                const buttonText =
                    document.getElementById(
                        'saveProfileText'
                    );


                if (button) {
                    button.disabled = true;
                }


                if (buttonText) {

                    buttonText.textContent =
                        'Saving...';
                }


                try {

                    if (
                        typeof updateProfile !==
                        'function'
                    ) {

                        throw new Error(
                            'Profile update is not connected.'
                        );
                    }


                    const result =
                        await updateProfile({

                            first_name:
                                firstName,

                            last_name:
                                lastName,

                            email:
                                email

                        });


                    if (
                        result &&
                        result.success
                    ) {

                        applyProfileToPage(
                            result.user ||
                            result.data ||
                            {
                                first_name:
                                    firstName,

                                last_name:
                                    lastName,

                                email:
                                    email
                            }
                        );


                        showToast(
                            'Profile updated successfully.'
                        );

                    } else {

                        throw new Error(
                            result?.error ||
                            'Could not update profile.'
                        );
                    }

                }
                catch (error) {

                    const message =
                        error.message ||
                        'Could not update profile.';


                    setFieldError(
                        'profileError',
                        message
                    );


                    showToast(
                        message,
                        true
                    );

                }
                finally {

                    if (button) {
                        button.disabled = false;
                    }


                    if (buttonText) {

                        buttonText.textContent =
                            'Save changes';
                    }

                }

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | PREFERENCE TOGGLES
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll(
            '[data-pref]'
        )
        .forEach(
            function (input) {

                input.addEventListener(
                    'change',
                    async function () {

                        const key =
                            input.dataset.pref;


                        const value =
                            input.checked;


                        /*
                        |------------------------------------------------
                        | Apply dark mode immediately
                        |------------------------------------------------
                        */

                        if (
                            key ===
                            'dark_mode'
                        ) {

                            applyDarkMode(
                                value
                            );
                        }


                        input.disabled =
                            true;


                        try {

                            if (
                                typeof updatePreferences !==
                                'function'
                            ) {

                                throw new Error(
                                    'Preference saving is not connected.'
                                );
                            }


                            const result =
                                await updatePreferences({

                                    [key]:
                                        value

                                });


                            if (
                                !result ||
                                !result.success
                            ) {

                                throw new Error(
                                    result?.error ||
                                    'Could not update preference.'
                                );
                            }


                            showToast(
                                'Preferences updated successfully.'
                            );

                        }
                        catch (error) {

                            /*
                            |--------------------------------------------
                            | Revert the switch
                            |--------------------------------------------
                            */

                            input.checked =
                                !value;


                            if (
                                key ===
                                'dark_mode'
                            ) {

                                applyDarkMode(
                                    !value
                                );
                            }


                            showToast(
                                error.message ||
                                'Could not update preference.',
                                true
                            );

                        }
                        finally {

                            input.disabled =
                                false;

                        }

                    }
                );

            }
        );


    /*
    |--------------------------------------------------------------------------
    | PASSWORD FORM
    |--------------------------------------------------------------------------
    */

    if (passwordForm) {

        passwordForm.addEventListener(
            'submit',
            async function (event) {

                event.preventDefault();


                setFieldError(
                    'passwordError',
                    ''
                );


                const currentPassword =
                    document
                        .getElementById(
                            'currentPassword'
                        )
                        .value;


                const newPassword =
                    document
                        .getElementById(
                            'newPassword'
                        )
                        .value;


                const confirmPassword =
                    document
                        .getElementById(
                            'confirmPassword'
                        )
                        .value;


                /*
                |------------------------------------------------
                | Validation
                |------------------------------------------------
                */

                if (
                    !currentPassword ||
                    !newPassword ||
                    !confirmPassword
                ) {

                    setFieldError(
                        'passwordError',
                        'All password fields are required.'
                    );

                    return;
                }


                if (
                    newPassword.length <
                    8
                ) {

                    setFieldError(
                        'passwordError',
                        'New password must be at least 8 characters.'
                    );

                    return;
                }


                if (
                    newPassword !==
                    confirmPassword
                ) {

                    setFieldError(
                        'passwordError',
                        'New password and confirmation do not match.'
                    );

                    return;
                }


                const button =
                    document.getElementById(
                        'updatePasswordBtn'
                    );


                const buttonText =
                    document.getElementById(
                        'updatePasswordText'
                    );


                if (button) {

                    button.disabled =
                        true;
                }


                if (buttonText) {

                    buttonText.textContent =
                        'Updating...';
                }


                try {

                    if (
                        typeof changePassword !==
                        'function'
                    ) {

                        throw new Error(
                            'Password update is not connected.'
                        );
                    }


                    const result =
                        await changePassword(
                            currentPassword,
                            newPassword
                        );


                    if (
                        result &&
                        result.success
                    ) {

                        passwordForm.reset();


                        setFieldError(
                            'passwordError',
                            ''
                        );


                        showToast(
                            'Password updated successfully.'
                        );

                    } else {

                        throw new Error(
                            result?.error ||
                            'Could not update password.'
                        );
                    }

                }
                catch (error) {

                    const message =
                        error.message ||
                        'Could not update password.';


                    setFieldError(
                        'passwordError',
                        message
                    );


                    showToast(
                        message,
                        true
                    );

                }
                finally {

                    if (button) {

                        button.disabled =
                            false;
                    }


                    if (buttonText) {

                        buttonText.textContent =
                            'Update password';
                    }

                }

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | HEADER DARK MODE SYNC
    |--------------------------------------------------------------------------
    */

    if (headerDarkModeBtn) {

        /*
        |--------------------------------------------------------------
        | Listen for clicks on the header button.
        |
        | IMPORTANT:
        | The header itself already sends the API request.
        | We only synchronize the settings switch here.
        |--------------------------------------------------------------
        */

        headerDarkModeBtn.addEventListener(
            'click',
            function () {

                const enabled =
                    headerDarkModeBtn.classList.contains(
                        'active'
                    );


                if (darkModeSwitch) {

                    darkModeSwitch.checked =
                        enabled;
                }


                document.body.classList.toggle(
                    'dark',
                    enabled
                );


                localStorage.setItem(
                    'checkmate_dark_mode',
                    enabled
                        ? 'true'
                        : 'false'
                );

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | LISTEN FOR HEADER DARK MODE CHANGES
    |--------------------------------------------------------------------------
    |
    | This allows the header to tell the settings page that
    | dark mode changed.
    |
    */

    window.addEventListener(
        'checkmateDarkModeChanged',
        function (event) {

            const enabled =
                Boolean(
                    event.detail?.enabled
                );


            applyDarkMode(
                enabled
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | LOAD PROFILE
    |--------------------------------------------------------------------------
    */

    loadProfile();


})();

</script>

</body>

</html>