(function() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const toggles = [document.getElementById('sidebarToggle'), document.getElementById('headerToggle')];

    if (!sidebar || !overlay) return;

    function openSidebar() {
        sidebar.classList.add('open');
        overlay.classList.add('show');
    }
    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
    }

    toggles.forEach(btn => {
        if (btn) btn.addEventListener('click', e => {
            e.stopPropagation();
            sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
        });
    });

    overlay.addEventListener('click', closeSidebar);
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeSidebar(); });

    sidebar.querySelectorAll('.nav-item').forEach(link => {
        link.addEventListener('click', () => { if (window.innerWidth <= 768) closeSidebar(); });
    });

    window.addEventListener('resize', () => { if (window.innerWidth > 768) closeSidebar(); });

    // Dark mode toggle
    const darkToggle = document.getElementById('darkModeToggle');
    if (darkToggle) {
        darkToggle.addEventListener('click', async () => {
            const isDark = !document.body.classList.contains('dark');
            document.body.classList.toggle('dark', isDark);
            document.documentElement.classList.toggle('cm-dark', isDark);
            darkToggle.classList.toggle('active', isDark);
            localStorage.setItem('checkmate_dark_mode', String(isDark));
            if (typeof updatePreferences === 'function') {
                await updatePreferences({ dark_mode: isDark });
            }
        });
    }

    // Language dropdown
    const langToggle = document.getElementById('languageToggle');
    const langDropdown = document.getElementById('languageDropdown');
    if (langToggle && langDropdown) {
        langToggle.addEventListener('click', e => {
            e.stopPropagation();
            langDropdown.classList.toggle('show');
        });
        langDropdown.querySelectorAll('.language-option').forEach(opt => {
            opt.addEventListener('click', e => {
                e.stopPropagation();
                const lang = opt.dataset.language;
                localStorage.setItem('checkmate_language', lang);
                // reload or apply translations
                window.location.reload();
            });
        });
    }

    // Profile dropdown
    const profileToggle = document.getElementById('profileToggle');
    const profileDropdown = document.getElementById('profileDropdown');
    if (profileToggle && profileDropdown) {
        profileToggle.addEventListener('click', e => {
            e.stopPropagation();
            profileDropdown.classList.toggle('show');
        });
    }

    // Close dropdowns on outside click
    document.addEventListener('click', () => {
        langDropdown?.classList.remove('show');
        profileDropdown?.classList.remove('show');
    });

    // Logout
    const logoutBtn = document.getElementById('headerLogoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', () => {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            document.cookie = 'checkmate_token=; path=/; max-age=0';
            document.cookie = 'checkmate_user=; path=/; max-age=0';
            window.location.replace('index.php?page=login');
        });
    }
})();