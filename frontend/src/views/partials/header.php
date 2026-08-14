<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| USER INFORMATION
|--------------------------------------------------------------------------
*/

if (!empty($_COOKIE['checkmate_user'])) {

    $user = json_decode($_COOKIE['checkmate_user'], true);

    if (is_array($user)) {

        $_SESSION['user_role'] =
            $user['role'] ?? 'staff';

        $_SESSION['user_name'] = trim(
            ($user['first_name'] ?? 'User') . ' ' .
            ($user['last_name'] ?? '')
        );

        $_SESSION['user_department'] =
            $user['department'] ?? '';

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

if (empty($_SESSION['user_name'])) {

    $_SESSION['user_name'] =
        $_SESSION['user_role'] === 'admin'
            ? 'Admin User'
            : 'Staff User';
}

if (!isset($_SESSION['user_department'])) {
    $_SESSION['user_department'] = '';
}

if (!isset($_SESSION['user_dark_mode'])) {
    $_SESSION['user_dark_mode'] = false;
}


/*
|--------------------------------------------------------------------------
| USER ROLE
|--------------------------------------------------------------------------
*/

$isAdmin =
    $_SESSION['user_role'] === 'admin';


/*
|--------------------------------------------------------------------------
| USER INITIALS
|--------------------------------------------------------------------------
*/

$initials = '';

$nameParts = preg_split(
    '/\s+/',
    trim($_SESSION['user_name'])
);

foreach ($nameParts as $part) {

    if ($part !== '') {
        $initials .= strtoupper(
            substr($part, 0, 1)
        );
    }
}

$initials =
    substr($initials, 0, 2) ?: 'U';


/*
|--------------------------------------------------------------------------
| HEADER SUBTITLE
|--------------------------------------------------------------------------
*/

$department =
    trim($_SESSION['user_department'] ?? '');

$headerSubtitle = $isAdmin
    ? 'Administrator'
    : (
        $department !== ''
            ? ucfirst($department)
            : 'Staff'
    );


/*
|--------------------------------------------------------------------------
| DARK MODE
|--------------------------------------------------------------------------
*/

$darkModeOn =
    !empty($_SESSION['user_dark_mode']);

?>

<style>

/* =========================================================
   CHECKMATE HEADER
   ========================================================= */

.app-header {

    position: relative;

    margin-left: 280px;

    min-height: 68px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;

    padding: 14px 28px;

    background: #ffffff;

    border-bottom: 1px solid #e4e7ed;

    font-family:
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        Roboto,
        "Helvetica Neue",
        Arial,
        sans-serif;

    z-index: 900;

    box-sizing: border-box;
}


/* =========================================================
   SEARCH
   ========================================================= */

.app-header-search {

    position: relative;

    flex: 1;

    width: 100%;

    max-width: 380px;

    min-width: 0;
}


.app-header-search i {

    position: absolute;

    left: 14px;

    top: 50%;

    transform: translateY(-50%);

    color: #9aa5b1;

    font-size: 14px;

    pointer-events: none;

    z-index: 2;
}


.app-header-search input {

    width: 100%;

    height: 40px;

    padding:
        9px
        14px
        9px
        38px;

    border: 1px solid #e1e5eb;

    border-radius: 8px;

    background: #f8f9fb;

    color: #1d2a3a;

    font-size: 14px;

    outline: none;

    box-sizing: border-box;

    transition:
        border-color .15s ease,
        background .15s ease,
        box-shadow .15s ease;
}


.app-header-search input::placeholder {

    color: #9aa5b1;
}


.app-header-search input:hover {

    border-color: #d5dae2;
}


.app-header-search input:focus {

    border-color: #0d9488;

    background: #ffffff;

    box-shadow:
        0 0 0 3px
        rgba(13, 148, 136, .08);
}


/* =========================================================
   RIGHT SIDE
   ========================================================= */

.app-header-right {

    display: flex;

    align-items: center;

    gap: 16px;

    flex-shrink: 0;
}


/* =========================================================
   CLOCK
   ========================================================= */

.app-header-clock {

    text-align: right;

    line-height: 1.25;

    min-width: 82px;
}


.app-header-clock .time {

    font-size: 14px;

    font-weight: 700;

    color: #1d2a3a;

    font-variant-numeric:
        tabular-nums;
}


.app-header-clock .date {

    margin-top: 2px;

    font-size: 11.5px;

    color: #8a94a3;
}


/* =========================================================
   DARK MODE BUTTON
   ========================================================= */

.icon-btn {

    position: relative;

    width: 38px;

    height: 38px;

    display: flex;

    align-items: center;

    justify-content: center;

    flex-shrink: 0;

    border: 1px solid #e1e5eb;

    border-radius: 50%;

    background: #ffffff;

    color: #4b5768;

    font-size: 14px;

    cursor: pointer;

    outline: none;

    transition:
        background .15s ease,
        border-color .15s ease,
        color .15s ease;
}


.icon-btn:hover {

    background: #f5f7fa;

    border-color: #d5dae2;
}


.icon-btn.active {

    background: #0d9488;

    border-color: #0d9488;

    color: #ffffff;
}


/* =========================================================
   LANGUAGE SELECTOR
   ========================================================= */

.app-header-language-wrap {

    position: relative;

    flex-shrink: 0;
}


.language-btn {

    display: flex;

    align-items: center;

    justify-content: center;

    gap: 7px;

    height: 38px;

    min-width: 38px;

    padding: 0 10px;

    border: 1px solid #e1e5eb;

    border-radius: 20px;

    background: #ffffff;

    color: #4b5768;

    font-size: 13px;

    font-weight: 600;

    cursor: pointer;

    outline: none;

    transition:
        background .15s ease,
        border-color .15s ease,
        color .15s ease;
}


.language-btn:hover {

    background: #f5f7fa;

    border-color: #d5dae2;
}


.language-btn i {

    font-size: 14px;
}


.language-btn .fa-chevron-down {

    font-size: 9px;

    transition:
        transform .15s ease;
}


.language-btn.open
.fa-chevron-down {

    transform: rotate(180deg);
}


/* =========================================================
   LANGUAGE DROPDOWN
   ========================================================= */

.language-dropdown {

    position: absolute;

    top: calc(100% + 8px);

    right: 0;

    width: 220px;

    max-height: 420px;

    padding: 6px;

    overflow-y: auto;

    display: none;

    background: #ffffff;

    border: 1px solid #e4e7ed;

    border-radius: 9px;

    box-shadow:
        0 10px 25px
        rgba(20, 25, 35, .12);

    z-index: 1200;

    box-sizing: border-box;
}


.language-dropdown.show {

    display: block;
}


/* =========================================================
   LANGUAGE DROPDOWN HEADER
   ========================================================= */

.language-dropdown-header {

    padding:
        9px
        12px
        7px;

    color: #8a94a3;

    font-size: 11px;

    font-weight: 700;

    text-transform: uppercase;

    letter-spacing: .4px;
}


/* =========================================================
   LANGUAGE OPTIONS
   ========================================================= */

.language-option {

    width: 100%;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 10px;

    padding:
        9px
        12px;

    border: none;

    border-radius: 7px;

    background: transparent;

    color: #4b5768;

    font-family: inherit;

    font-size: 13px;

    font-weight: 500;

    text-align: left;

    cursor: pointer;

    box-sizing: border-box;

    transition:
        background .15s ease,
        color .15s ease;
}


.language-option:hover {

    background: #f5f7fa;
}


.language-option.active {

    background: #f0fdfa;

    color: #0d9488;

    font-weight: 700;
}


.language-check {

    display: none;
}


.language-option.active
.language-check {

    display: inline-block;
}


/* =========================================================
   PROFILE
   ========================================================= */

.app-header-profile-wrap {

    position: relative;

    flex-shrink: 0;
}


.app-header-profile {

    display: flex;

    align-items: center;

    gap: 10px;

    padding:
        4px
        8px
        4px
        4px;

    border-radius: 9px;

    cursor: pointer;

    transition:
        background .15s ease;
}


.app-header-profile:hover,
.app-header-profile.open {

    background: #f5f7fa;
}


/* =========================================================
   PROFILE AVATAR
   ========================================================= */

.app-header-avatar {

    width: 38px;

    height: 38px;

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

    font-size: 13px;

    font-weight: 700;
}


/* =========================================================
   PROFILE INFORMATION
   ========================================================= */

.app-header-identity {

    min-width: 90px;

    line-height: 1.25;

    white-space: nowrap;
}


.app-header-identity .name {

    color: #1d2a3a;

    font-size: 13.5px;

    font-weight: 700;
}


.app-header-identity .role {

    margin-top: 2px;

    color: #8a94a3;

    font-size: 11.5px;
}


/* =========================================================
   PROFILE CHEVRON
   ========================================================= */

.app-header-chevron {

    margin-left: 2px;

    color: #9aa5b1;

    font-size: 10px;

    transition:
        transform .15s ease;
}


.app-header-profile.open
.app-header-chevron {

    transform: rotate(180deg);
}


/* =========================================================
   PROFILE DROPDOWN
   ========================================================= */

.app-header-dropdown {

    position: absolute;

    top: calc(100% + 8px);

    right: 0;

    min-width: 190px;

    padding: 6px;

    display: none;

    background: #ffffff;

    border: 1px solid #e4e7ed;

    border-radius: 9px;

    box-shadow:
        0 10px 25px
        rgba(20, 25, 35, .12);

    z-index: 1100;

    box-sizing: border-box;
}


.app-header-dropdown.show {

    display: block;
}


.app-header-dropdown-item {

    width: 100%;

    display: flex;

    align-items: center;

    gap: 10px;

    padding:
        10px
        12px;

    border: none;

    border-radius: 7px;

    background: transparent;

    color: #4b5768;

    font-family: inherit;

    font-size: 13.5px;

    font-weight: 600;

    text-align: left;

    text-decoration: none;

    cursor: pointer;

    box-sizing: border-box;

    transition:
        background .15s ease;
}


.app-header-dropdown-item:hover {

    background: #f5f7fa;
}


.app-header-dropdown-item i {

    width: 16px;

    font-size: 13px;

    text-align: center;
}


.app-header-dropdown-item.danger {

    color: #dc2626;
}


/* =========================================================
   DARK MODE
   ========================================================= */

body.dark .app-header {

    background: #1a212b;

    border-bottom-color: #2a323d;
}


body.dark
.app-header-search input {

    background: #12181f;

    border-color: #2a323d;

    color: #e6e9ee;
}


body.dark
.app-header-search input::placeholder {

    color: #7f8b99;
}


body.dark
.app-header-search input:focus {

    background: #1a212b;

    border-color: #0d9488;
}


body.dark
.app-header-clock .time {

    color: #f1f3f6;
}


body.dark
.app-header-clock .date {

    color: #9aa5b1;
}


body.dark .icon-btn {

    background: #1a212b;

    border-color: #2a323d;

    color: #c5ccd5;
}


body.dark .icon-btn:hover {

    background: #242d38;
}


/* =========================================================
   DARK MODE - LANGUAGE
   ========================================================= */

body.dark .language-btn {

    background: #1a212b;

    border-color: #2a323d;

    color: #c5ccd5;
}


body.dark .language-btn:hover {

    background: #242d38;
}


body.dark .language-dropdown {

    background: #1a212b;

    border-color: #2a323d;
}


body.dark
.language-dropdown-header {

    color: #8d98a5;
}


body.dark .language-option {

    color: #d1d7df;
}


body.dark .language-option:hover {

    background: #242d38;
}


body.dark
.language-option.active {

    background: #163b38;

    color: #4fd1c5;
}


/* =========================================================
   DARK MODE - PROFILE
   ========================================================= */

body.dark
.app-header-profile:hover,

body.dark
.app-header-profile.open {

    background: #242d38;
}


body.dark
.app-header-identity .name {

    color: #f1f3f6;
}


body.dark
.app-header-identity .role {

    color: #9aa5b1;
}


body.dark
.app-header-dropdown {

    background: #1a212b;

    border-color: #2a323d;
}


body.dark
.app-header-dropdown-item {

    color: #d1d7df;
}


body.dark
.app-header-dropdown-item:hover {

    background: #242d38;
}


/* =========================================================
   TABLET
   ========================================================= */

@media (max-width: 1100px) {

    .app-header {

        padding:
            14px
            22px;
    }


    .app-header-search {

        max-width: 300px;
    }


    .app-header-clock {

        display: none;
    }
}


/* =========================================================
   MOBILE
   ========================================================= */

@media (max-width: 900px) {

    .app-header {

        margin-left: 0;

        min-height: 64px;

        padding:
            12px
            20px
            12px
            60px;
    }


    .app-header-search {

        max-width: 280px;
    }
}


/* =========================================================
   SMALL TABLETS / LARGE PHONES
   ========================================================= */

@media (max-width: 720px) {

    .app-header {

        flex-wrap: wrap;

        gap: 10px;

        padding:
            12px
            16px
            12px
            60px;
    }


    .app-header-search {

        order: 3;

        flex-basis: 100%;

        max-width: none;

        margin-top: 2px;
    }


    .app-header-search input {

        height: 38px;
    }


    .app-header-right {

        gap: 8px;
    }


    .app-header-identity {

        display: none;
    }


    .app-header-chevron {

        display: none;
    }


    .language-dropdown {

        right: -40px;
    }
}


/* =========================================================
   SMALL PHONES
   ========================================================= */

@media (max-width: 480px) {

    .app-header {

        padding-left: 55px;

        padding-right: 12px;
    }


    .app-header-right {

        gap: 6px;
    }


    .icon-btn {

        width: 36px;

        height: 36px;
    }


    .language-btn {

        width: 36px;

        min-width: 36px;

        padding: 0;
    }


    .language-btn .fa-chevron-down {

        display: none;
    }


    .app-header-avatar {

        width: 36px;

        height: 36px;
    }


    .language-dropdown {

        right: -20px;

        width: 210px;
    }
}


/* =========================================================
   VERY SMALL PHONES
   ========================================================= */

@media (max-width: 360px) {

    .app-header {

        padding-left: 50px;

        padding-right: 8px;
    }


    .app-header-right {

        gap: 4px;
    }


    .icon-btn {

        width: 34px;

        height: 34px;
    }


    .language-btn {

        width: 34px;

        min-width: 34px;
    }


    .app-header-avatar {

        width: 34px;

        height: 34px;
    }
}

</style>


<!-- =========================================================
     CHECKMATE HEADER
     ========================================================= -->

<div class="app-header">


    <!-- =====================================================
         SEARCH
         ===================================================== -->

    <div class="app-header-search">

        <i class="fas fa-search"></i>

        <input
            type="text"
            id="headerSearch"
            placeholder="Search employees, records..."
            autocomplete="off"
            aria-label="Search"
        >

    </div>


    <!-- =====================================================
         RIGHT SIDE
         ===================================================== -->

    <div class="app-header-right">


        <!-- =================================================
             CLOCK
             ================================================= -->

        <div class="app-header-clock">

            <div
                class="time"
                id="headerClockTime"
            >
                --:--:--
            </div>

            <div
                class="date"
                id="headerClockDate"
            >
                <?= htmlspecialchars(
                    date('d M Y')
                ) ?>
            </div>

        </div>


        <!-- =================================================
             DARK MODE
             ================================================= -->

        <button
            type="button"
            class="icon-btn<?= $darkModeOn ? ' active' : '' ?>"
            id="headerDarkModeBtn"
            title="Toggle dark mode"
            aria-label="Toggle dark mode"
            aria-pressed="<?= $darkModeOn ? 'true' : 'false' ?>"
        >

            <i class="fas fa-moon"></i>

        </button>


        <!-- =================================================
             LANGUAGE SELECTOR
             ================================================= -->

        <div class="app-header-language-wrap">

            <button
                type="button"
                class="language-btn"
                id="headerLanguageBtn"
                title="Select language"
                aria-label="Select language"
                aria-expanded="false"
            >

                <i class="fas fa-globe"></i>

                <i class="fas fa-chevron-down"></i>

            </button>


            <!-- LANGUAGE DROPDOWN -->

            <div
                class="language-dropdown"
                id="headerLanguageDropdown"
                role="menu"
            >

                <div class="language-dropdown-header">

                    South African languages

                </div>


                <!-- English -->

                <button
                    type="button"
                    class="language-option active"
                    data-language="English"
                    role="menuitem"
                >

                    <span>English</span>

                    <i
                        class="fas fa-check language-check"
                    ></i>

                </button>


                <!-- Afrikaans -->

                <button
                    type="button"
                    class="language-option"
                    data-language="Afrikaans"
                    role="menuitem"
                >

                    <span>Afrikaans</span>

                    <i
                        class="fas fa-check language-check"
                    ></i>

                </button>


                <!-- isiNdebele -->

                <button
                    type="button"
                    class="language-option"
                    data-language="isiNdebele"
                    role="menuitem"
                >

                    <span>isiNdebele</span>

                    <i
                        class="fas fa-check language-check"
                    ></i>

                </button>


                <!-- isiXhosa -->

                <button
                    type="button"
                    class="language-option"
                    data-language="isiXhosa"
                    role="menuitem"
                >

                    <span>isiXhosa</span>

                    <i
                        class="fas fa-check language-check"
                    ></i>

                </button>


                <!-- isiZulu -->

                <button
                    type="button"
                    class="language-option"
                    data-language="isiZulu"
                    role="menuitem"
                >

                    <span>isiZulu</span>

                    <i
                        class="fas fa-check language-check"
                    ></i>

                </button>


                <!-- Sepedi -->

                <button
                    type="button"
                    class="language-option"
                    data-language="Sepedi"
                    role="menuitem"
                >

                    <span>Sepedi</span>

                    <i
                        class="fas fa-check language-check"
                    ></i>

                </button>


                <!-- Sesotho -->

                <button
                    type="button"
                    class="language-option"
                    data-language="Sesotho"
                    role="menuitem"
                >

                    <span>Sesotho</span>

                    <i
                        class="fas fa-check language-check"
                    ></i>

                </button>


                <!-- Setswana -->

                <button
                    type="button"
                    class="language-option"
                    data-language="Setswana"
                    role="menuitem"
                >

                    <span>Setswana</span>

                    <i
                        class="fas fa-check language-check"
                    ></i>

                </button>


                <!-- siSwati -->

                <button
                    type="button"
                    class="language-option"
                    data-language="siSwati"
                    role="menuitem"
                >

                    <span>siSwati</span>

                    <i
                        class="fas fa-check language-check"
                    ></i>

                </button>


                <!-- Tshivenda -->

                <button
                    type="button"
                    class="language-option"
                    data-language="Tshivenda"
                    role="menuitem"
                >

                    <span>Tshivenda</span>

                    <i
                        class="fas fa-check language-check"
                    ></i>

                </button>


                <!-- Xitsonga -->

                <button
                    type="button"
                    class="language-option"
                    data-language="XiTsonga"
                    role="menuitem"
                >

                    <span>XiTsonga</span>

                    <i
                        class="fas fa-check language-check"
                    ></i>

                </button>

            </div>

        </div>


        <!-- =================================================
             PROFILE
             ================================================= -->

        <div class="app-header-profile-wrap">

            <div
                class="app-header-profile"
                id="headerProfile"
                role="button"
                tabindex="0"
                aria-expanded="false"
                aria-haspopup="true"
            >

                <div class="app-header-avatar">

                    <?= htmlspecialchars(
                        $initials
                    ) ?>

                </div>


                <div class="app-header-identity">

                    <div class="name">

                        <?= htmlspecialchars(
                            $_SESSION['user_name']
                        ) ?>

                    </div>


                    <div class="role">

                        <?= htmlspecialchars(
                            $headerSubtitle
                        ) ?>

                    </div>

                </div>


                <i
                    class="fas fa-chevron-down app-header-chevron"
                ></i>

            </div>


            <!-- PROFILE DROPDOWN -->

            <div
                class="app-header-dropdown"
                id="headerProfileDropdown"
            >

                <a
                    href="javascript:void(0)"
                    class="app-header-dropdown-item danger"
                    id="headerLogoutBtn"
                >

                    <i
                        class="fas fa-right-from-bracket"
                    ></i>

                    <span>Log out</span>

                </a>

            </div>

        </div>

    </div>

</div>


<script>

(function () {

    'use strict';


    /* =====================================================
       LIVE CLOCK
       ===================================================== */

    function pad(number) {

        return String(number).padStart(2, '0');

    }


    function tickClock() {

        const now = new Date();


        const time =
            `${pad(now.getHours())}:` +
            `${pad(now.getMinutes())}:` +
            `${pad(now.getSeconds())}`;


        const timeElement =
            document.getElementById(
                'headerClockTime'
            );


        const dateElement =
            document.getElementById(
                'headerClockDate'
            );


        if (timeElement) {

            timeElement.textContent = time;

        }


        if (dateElement) {

            dateElement.textContent =
                now.toLocaleDateString(
                    'en-GB',
                    {
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric'
                    }
                );

        }

    }


    tickClock();

    setInterval(
        tickClock,
        1000
    );


    /* =====================================================
       ELEMENTS
       ===================================================== */

    const darkBtn =
        document.getElementById(
            'headerDarkModeBtn'
        );


    const languageBtn =
        document.getElementById(
            'headerLanguageBtn'
        );


    const languageDropdown =
        document.getElementById(
            'headerLanguageDropdown'
        );


    const languageOptions =
        document.querySelectorAll(
            '.language-option'
        );


    const profile =
        document.getElementById(
            'headerProfile'
        );


    const profileDropdown =
        document.getElementById(
            'headerProfileDropdown'
        );


    const logoutBtn =
        document.getElementById(
            'headerLogoutBtn'
        );


    const searchInput =
        document.getElementById(
            'headerSearch'
        );


    /* =====================================================
       CLOSE LANGUAGE DROPDOWN
       ===================================================== */

    function closeLanguageDropdown() {

        if (!languageDropdown) {
            return;
        }


        languageDropdown.classList.remove(
            'show'
        );


        if (languageBtn) {

            languageBtn.classList.remove(
                'open'
            );


            languageBtn.setAttribute(
                'aria-expanded',
                'false'
            );

        }

    }


    /* =====================================================
       CLOSE PROFILE DROPDOWN
       ===================================================== */

    function closeProfileDropdown() {

        if (!profileDropdown) {
            return;
        }


        profileDropdown.classList.remove(
            'show'
        );


        if (profile) {

            profile.classList.remove(
                'open'
            );


            profile.setAttribute(
                'aria-expanded',
                'false'
            );

        }

    }


    /* =====================================================
       CLOSE ALL DROPDOWNS
       ===================================================== */

    function closeAllDropdowns() {

        closeLanguageDropdown();

        closeProfileDropdown();

    }


    /* =====================================================
       DARK MODE
       ===================================================== */

    if (darkBtn) {

        darkBtn.addEventListener(
            'click',
            async function () {

                const isOn =
                    !darkBtn.classList.contains(
                        'active'
                    );


                darkBtn.classList.toggle(
                    'active',
                    isOn
                );


                darkBtn.setAttribute(
                    'aria-pressed',
                    isOn
                        ? 'true'
                        : 'false'
                );


                document.body.classList.toggle(
                    'dark',
                    isOn
                );


                if (
                    typeof updatePreferences ===
                    'function'
                ) {

                    try {

                        const result =
                            await updatePreferences({
                                dark_mode: isOn
                            });


                        if (
                            !result ||
                            !result.success
                        ) {

                            darkBtn.classList.toggle(
                                'active',
                                !isOn
                            );


                            darkBtn.setAttribute(
                                'aria-pressed',
                                !isOn
                                    ? 'true'
                                    : 'false'
                            );


                            document.body.classList.toggle(
                                'dark',
                                !isOn
                            );

                        }

                    }
                    catch (error) {

                        console.error(
                            'Dark mode update failed:',
                            error
                        );


                        darkBtn.classList.toggle(
                            'active',
                            !isOn
                        );


                        darkBtn.setAttribute(
                            'aria-pressed',
                            !isOn
                                ? 'true'
                                : 'false'
                        );


                        document.body.classList.toggle(
                            'dark',
                            !isOn
                        );

                    }

                }

            }
        );

    }


    /* =====================================================
       APPLY SAVED DARK MODE
       ===================================================== */

    <?php if ($darkModeOn): ?>

    document.body.classList.add('dark');

    <?php endif; ?>


    /* =====================================================
       LANGUAGE DROPDOWN
       ===================================================== */

    if (
        languageBtn &&
        languageDropdown
    ) {

        languageBtn.addEventListener(
            'click',
            function (event) {

                event.stopPropagation();


                closeProfileDropdown();


                const isOpen =
                    languageDropdown.classList.toggle(
                        'show'
                    );


                languageBtn.classList.toggle(
                    'open',
                    isOpen
                );


                languageBtn.setAttribute(
                    'aria-expanded',
                    isOpen
                        ? 'true'
                        : 'false'
                );

            }
        );

    }


    /* =====================================================
       LANGUAGE TRANSLATION PLACEHOLDER
       ===================================================== */

    const languagePlaceholders = {

        'English':
            'Search employees, records...',

        'Afrikaans':
            'Soek werknemers, rekords...',

        'isiNdebele':
            'Funa abasebenzi, amarekhodi...',

        'isiXhosa':
            'Khangela abasebenzi, iirekhodi...',

        'isiZulu':
            'Sesha abasebenzi, amarekhodi...',

        'Sepedi':
            'Nyaka bašomi, direkoto...',

        'Sesotho':
            'Batla basebetsi, direkoto...',

        'Setswana':
            'Batla badiri, direkoto...',

        'siSwati':
            'Sesha tisebenti, emarekhodi...',

        'Tshivenda':
            'Ṱola vhashumi, rekhodo...',

        'XiTsonga':
            'Lava vatirhi, tirhekodo...'

    };


    /* =====================================================
       APPLY LANGUAGE
       ===================================================== */

    function applyLanguage(language) {

        if (!language) {
            return;
        }


        languageOptions.forEach(
            function (option) {

                option.classList.toggle(
                    'active',
                    option.dataset.language ===
                    language
                );

            }
        );


        if (searchInput) {

            searchInput.placeholder =
                languagePlaceholders[language]
                ||
                languagePlaceholders['English'];

        }


        localStorage.setItem(
            'checkmate_language',
            language
        );


        window.dispatchEvent(
            new CustomEvent(
                'checkmateLanguageChanged',
                {
                    detail: {
                        language: language
                    }
                }
            )
        );

    }


    /* =====================================================
       LANGUAGE SELECTION
       ===================================================== */

    languageOptions.forEach(
        function (option) {

            option.addEventListener(
                'click',
                function (event) {

                    event.stopPropagation();


                    const selectedLanguage =
                        option.dataset.language;


                    if (!selectedLanguage) {
                        return;
                    }


                    applyLanguage(
                        selectedLanguage
                    );


                    closeLanguageDropdown();


                    console.log(
                        'Selected language:',
                        selectedLanguage
                    );

                }
            );

        }
    );


    /* =====================================================
       RESTORE SAVED LANGUAGE
       ===================================================== */

    const savedLanguage =
        localStorage.getItem(
            'checkmate_language'
        );


    applyLanguage(
        savedLanguage || 'English'
    );


    /* =====================================================
       PROFILE DROPDOWN
       ===================================================== */

    if (
        profile &&
        profileDropdown
    ) {

        profile.addEventListener(
            'click',
            function (event) {

                event.stopPropagation();


                closeLanguageDropdown();


                const isOpen =
                    profileDropdown.classList.toggle(
                        'show'
                    );


                profile.classList.toggle(
                    'open',
                    isOpen
                );


                profile.setAttribute(
                    'aria-expanded',
                    isOpen
                        ? 'true'
                        : 'false'
                );

            }
        );


        /* Keyboard accessibility */

        profile.addEventListener(
            'keydown',
            function (event) {

                if (
                    event.key === 'Enter' ||
                    event.key === ' '
                ) {

                    event.preventDefault();

                    profile.click();

                }


                if (
                    event.key === 'Escape'
                ) {

                    closeProfileDropdown();

                }

            }
        );

    }


    /* =====================================================
       CLOSE DROPDOWNS WHEN CLICKING OUTSIDE
       ===================================================== */

    document.addEventListener(
        'click',
        function (event) {

            const target =
                event.target;


            if (
                languageDropdown &&
                languageBtn &&
                !languageDropdown.contains(target) &&
                !languageBtn.contains(target)
            ) {

                closeLanguageDropdown();

            }


            if (
                profileDropdown &&
                profile &&
                !profileDropdown.contains(target) &&
                !profile.contains(target)
            ) {

                closeProfileDropdown();

            }

        }
    );


    /* =====================================================
       ESCAPE KEY
       ===================================================== */

    document.addEventListener(
        'keydown',
        function (event) {

            if (
                event.key === 'Escape'
            ) {

                closeAllDropdowns();

            }

        }
    );


    /* =====================================================
       LOGOUT
       ===================================================== */

    if (logoutBtn) {

        logoutBtn.addEventListener(
            'click',
            function (event) {

                event.preventDefault();

                event.stopPropagation();


                if (
                    typeof logoutUser ===
                    'function'
                ) {

                    logoutUser();

                }
                else {

                    console.error(
                        'logoutUser() is not defined. ' +
                        'Make sure login.js is loaded.'
                    );

                }

            }
        );

    }


    /* =====================================================
       SEARCH
       ===================================================== */

    if (searchInput) {

        searchInput.addEventListener(
            'keydown',
            function (event) {

                if (
                    event.key === 'Enter'
                ) {

                    const searchTerm =
                        searchInput.value.trim();


                    if (
                        searchTerm !== ''
                    ) {

                        console.log(
                            'Header search:',
                            searchTerm
                        );


                        /*
                         * Connect your CheckMate
                         * backend search here.
                         */

                    }

                }

            }
        );

    }


})();

</script>