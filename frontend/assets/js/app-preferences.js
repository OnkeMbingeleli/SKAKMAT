/**
 * Shared header/sidebar behaviour used by every page: dark mode, live
 * clock, notification bell (real counts, not a hardcoded badge), and the
 * profile dropdown with a working logout. Loaded on every page either via
 * layouts/app.php or directly (settings.php, attendance-history.php).
 */
(function () {
    'use strict';

    // =====================================================================
    // Dark mode + clock
    // =====================================================================

    function isDarkSaved() {
        try {
            return localStorage.getItem('checkmate_dark_mode') === 'true' || localStorage.getItem('checkmate_dark_mode') === '1';
        } catch (e) {
            return false;
        }
    }

    if (isDarkSaved()) {
        document.documentElement.classList.add('dark');
    }

    function setDark(enabled) {
        document.body.classList.toggle('dark', enabled);
        document.body.classList.toggle('dark-mode', enabled);
        try {
            localStorage.setItem('checkmate_dark_mode', enabled ? 'true' : 'false');
        } catch (e) { /* ignore */ }

        var btn = document.getElementById('headerDarkModeBtn');
        if (btn) {
            btn.classList.toggle('active', enabled);
            btn.setAttribute('aria-pressed', enabled ? 'true' : 'false');
        }

        var switchInput = document.getElementById('prefDarkMode');
        if (switchInput) switchInput.checked = enabled;

        window.dispatchEvent(new CustomEvent('checkmateDarkModeChanged', { detail: { enabled: enabled } }));
    }

    function tickClock() {
        var timeEl = document.getElementById('currentTime');
        var dateEl = document.getElementById('currentDate');
        if (!timeEl && !dateEl) return;

        var now = new Date();
        if (timeEl) timeEl.textContent = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        if (dateEl) dateEl.textContent = now.toLocaleDateString([], { day: '2-digit', month: 'short', year: 'numeric' });
    }

    // =====================================================================
    // Notifications — a real count, not a hardcoded number.
    //   Admin:  number of leave requests still pending a decision.
    //   Staff:  number of their own leave requests that were approved or
    //           rejected since they last opened the bell (tracked in
    //           localStorage so it doesn't just stay stuck forever).
    // =====================================================================

    function getStoredToken() {
        try { return localStorage.getItem('token'); } catch (e) { return null; }
    }

    async function fetchJson(path) {
        var base = (window.CONFIG && window.CONFIG.API_URL) || 'http://127.0.0.1:8000';
        var token = getStoredToken();
        var response = await fetch(base + path, {
            headers: Object.assign(
                { 'Accept': 'application/json' },
                token ? { Authorization: 'Bearer ' + token } : {}
            )
        });
        var text = await response.text();
        return text ? JSON.parse(text) : {};
    }

    function setNotificationCount(count) {
        var badge = document.getElementById('headerNotifBadge');
        if (!badge) return;
        if (count > 0) {
            badge.textContent = count > 9 ? '9+' : String(count);
            badge.hidden = false;
        } else {
            badge.hidden = true;
        }
    }

    var lastNotificationIds = [];

    async function loadNotifications() {
        var role = document.body.getAttribute('data-role') ||
            document.body.getAttribute('data-user-role') || 'staff';

        try {
            var res = await fetchJson('/api/leave-requests');
            if (!res || res.success === false) { setNotificationCount(0); return; }
            var rows = res.data || [];

            if (role === 'admin') {
                var pending = rows.filter(function (r) { return r.status === 'pending'; });
                setNotificationCount(pending.length);
                lastNotificationIds = pending.map(function (r) { return r.id; });
            } else {
                var seen = [];
                try { seen = JSON.parse(localStorage.getItem('checkmate_seen_leave_decisions') || '[]'); } catch (e) { /* ignore */ }
                var decided = rows.filter(function (r) { return r.status !== 'pending' && seen.indexOf(r.id) === -1; });
                setNotificationCount(decided.length);
                lastNotificationIds = decided.map(function (r) { return r.id; });
            }
        } catch (e) {
            setNotificationCount(0);
        }
    }

    function wireNotificationBell() {
        var bellBtn = document.getElementById('headerNotifBtn');
        if (!bellBtn || bellBtn.dataset.boundBell) return;
        bellBtn.dataset.boundBell = '1';
        bellBtn.addEventListener('click', function () {
            var existing = document.getElementById('cmNotificationPop');
            if (existing) { existing.remove(); return; }

            var role = document.body.getAttribute('data-role') || 'staff';
            var message = lastNotificationIds.length
                ? (role === 'admin'
                    ? lastNotificationIds.length + ' leave request(s) waiting for your review.'
                    : lastNotificationIds.length + ' of your leave request(s) were just decided.')
                : 'No new notifications';

            var pop = document.createElement('div');
            pop.id = 'cmNotificationPop';
            pop.textContent = message;
            pop.style.cssText = 'position:absolute; top:64px; right:24px; max-width:260px; background:var(--panel); border:1px solid var(--line); border-radius:10px; padding:12px 16px; font-size:13px; color:var(--text); box-shadow:0 12px 28px rgba(15,23,42,.15); z-index:3000;';
            document.body.appendChild(pop);
            setTimeout(function () { pop.remove(); }, 3500);

            // Mark staff's currently-visible decisions as seen so the badge
            // doesn't show the same ones forever.
            if (role !== 'admin' && lastNotificationIds.length) {
                try {
                    var seen = JSON.parse(localStorage.getItem('checkmate_seen_leave_decisions') || '[]');
                    var merged = seen.concat(lastNotificationIds);
                    localStorage.setItem('checkmate_seen_leave_decisions', JSON.stringify(merged));
                } catch (e) { /* ignore */ }
                setNotificationCount(0);
            }
        });
    }

    // =====================================================================
    // Profile dropdown (logout)
    // =====================================================================

    function closeAllMenus() {
        var el = document.getElementById('headerProfileMenu');
        if (el) el.hidden = true;
    }

    function wireProfileMenu() {
        var btn = document.getElementById('headerProfileBtn');
        var menu = document.getElementById('headerProfileMenu');
        var logoutBtn = document.getElementById('headerLogoutBtn');
        if (!btn || !menu || btn.dataset.boundProfile) return;
        btn.dataset.boundProfile = '1';

        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var willOpen = menu.hidden;
            closeAllMenus();
            menu.hidden = !willOpen;
            btn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        });

        if (logoutBtn) {
            logoutBtn.addEventListener('click', doLogout);
        }
    }

    function doLogout() {
        if (typeof window.logout === 'function') {
            window.logout();
            return;
        }
        try {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
        } catch (e) { /* ignore */ }
        document.cookie = 'checkmate_token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
        document.cookie = 'checkmate_user=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
        var entryPoint = window.location.pathname.includes('/public/') ? '/public/index.php' : '/index.php';
        window.location.href = entryPoint + '?page=login';
    }
    window.checkmateLogout = doLogout;

    window.addEventListener('click', function () { closeAllMenus(); });

    // =====================================================================
    // Init
    // =====================================================================

    document.addEventListener('DOMContentLoaded', function () {
        setDark(isDarkSaved());

        var darkBtn = document.getElementById('headerDarkModeBtn');
        if (darkBtn && !darkBtn.dataset.boundDarkToggle) {
            darkBtn.dataset.boundDarkToggle = '1';
            darkBtn.addEventListener('click', async function () {
                var enabled = !document.body.classList.contains('dark');
                setDark(enabled);
                if (typeof window.updatePreferences === 'function') {
                    try { await window.updatePreferences({ dark_mode: enabled }); } catch (e) { /* non-fatal */ }
                }
            });
        }

        wireNotificationBell();
        wireProfileMenu();

        // Wire up the sidebar's logout link too, if present.
        var sidebarLogout = document.getElementById('sidebarLogoutBtn');
        if (sidebarLogout && !sidebarLogout.dataset.boundLogout) {
            sidebarLogout.dataset.boundLogout = '1';
            sidebarLogout.addEventListener('click', function (e) {
                e.preventDefault();
                doLogout();
            });
        }

        tickClock();
        setInterval(tickClock, 1000);

        loadNotifications();
        setInterval(loadNotifications, 60000);
    });
})();

window.updatePreferences = window.updatePreferences || async function updatePreferences(prefs) {
    try {
        var user = JSON.parse(localStorage.getItem('user') || '{}');
        Object.assign(user, prefs);
        localStorage.setItem('user', JSON.stringify(user));
        document.cookie = 'checkmate_user=' + encodeURIComponent(JSON.stringify(user)) + '; path=/; max-age=3600';
    } catch (e) { /* ignore */ }
    return { success: true };
};
