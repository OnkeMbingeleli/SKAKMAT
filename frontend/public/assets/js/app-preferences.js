/**
 * Shared header behaviour used by every page: restoring + toggling dark
 * mode, and a live clock. Previously only settings.php wired up the dark
 * mode button's click handler, so clicking the moon icon anywhere else in
 * the app did nothing, and the clock never ticked because nothing ever
 * called setInterval on it.
 */
(function () {
    'use strict';

    function isDarkSaved() {
        try {
            return localStorage.getItem('checkmate_dark_mode') === 'true';
        } catch (e) {
            return false;
        }
    }

    // Apply saved preference immediately (before DOMContentLoaded) to avoid
    // a flash of the light theme.
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

        window.dispatchEvent(new CustomEvent('checkmateDarkModeChanged', { detail: { enabled: enabled } }));
    }

    function tickClock() {
        var timeEl = document.getElementById('currentTime');
        var dateEl = document.getElementById('currentDate');
        if (!timeEl && !dateEl) return;

        var now = new Date();
        if (timeEl) {
            timeEl.textContent = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        }
        if (dateEl) {
            dateEl.textContent = now.toLocaleDateString([], { day: '2-digit', month: 'short', year: 'numeric' });
        }
    }

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

        tickClock();
        setInterval(tickClock, 1000);
    });
})();

/**
 * Minimal, best-effort preference saving used by settings.php.
 * There's no dedicated self-service "update my profile" endpoint on the
 * backend yet, so this stores locally and no-ops the network call rather
 * than throwing — keeps the Settings page usable without a 404 loop.
 */
window.updatePreferences = window.updatePreferences || async function updatePreferences(prefs) {
    try {
        var user = JSON.parse(localStorage.getItem('user') || '{}');
        Object.assign(user, prefs);
        localStorage.setItem('user', JSON.stringify(user));
        document.cookie = 'checkmate_user=' + encodeURIComponent(JSON.stringify(user)) + '; path=/; max-age=3600';
    } catch (e) { /* ignore */ }
    return { success: true };
};
