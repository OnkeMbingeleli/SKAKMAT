function getAppRoute(page) {
    const entryPoint = window.location.pathname.includes('/public/')
        ? '/public/index.php'
        : '/index.php';
    return `${entryPoint}?page=${encodeURIComponent(page)}`;
}

function saveAuth(token, user) {
    localStorage.setItem('skakmat_token', token);
    localStorage.setItem('skakmat_user', JSON.stringify(user));
    localStorage.setItem('token', token);
    localStorage.setItem('user', JSON.stringify(user));
    document.cookie = `skakmat_token=${encodeURIComponent(token)}; path=/; max-age=3600`;
    document.cookie = `skakmat_user=${encodeURIComponent(JSON.stringify(user))}; path=/; max-age=3600`;
}

function clearAuth() {
    localStorage.removeItem('skakmat_token');
    localStorage.removeItem('skakmat_user');
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    document.cookie = 'skakmat_token=; path=/; max-age=0';
    document.cookie = 'skakmat_user=; path=/; max-age=0';
}

// Tab switching
function setupTabSwitching() {
    const tabs = document.querySelectorAll('#tabGroup .tab-btn');
    const staffPanel = document.getElementById('panelStaff');
    const adminPanel = document.getElementById('panelAdmin');

    if (!tabs.length || !staffPanel || !adminPanel) return;

    tabs.forEach(tab => {
        tab.addEventListener('click', function (e) {
            e.preventDefault();
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            const tab = this.getAttribute('data-tab');
            staffPanel.classList.toggle('active', tab === 'staff');
            adminPanel.classList.toggle('active', tab === 'admin');
        });
    });
}

// Manual form submission
function handleFormSubmit(event) {
    event.preventDefault();
    const form = event.target;
    const emailField = form.querySelector('input[type="email"]');
    const passwordField = form.querySelector('input[type="password"]');
    const submitBtn = form.querySelector('button[type="submit"]');

    if (!emailField || !passwordField) return;

    const staffForm = form.closest('#panelStaff') !== null;
    const expectedRole = staffForm ? 'staff' : 'admin';
    const email = emailField.value.trim();
    const password = passwordField.value;

    if (!email || !password) {
        alert('Please fill in all fields');
        return;
    }

    submitBtn.disabled = true;
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Signing in...';

    login(email, password)
        .then(result => {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;

            if (result.success) {
                const userRole = result.data.user.role;
                if (userRole !== expectedRole) {
                    alert(`This account is for ${userRole} users. Use the ${userRole.toUpperCase()} Login tab.`);
                    return;
                }
                saveAuth(result.data.token, result.data.user);
                window.location.replace(getAppRoute('dashboard'));
            } else {
                alert('Login failed: ' + (result.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            alert('Connection error: ' + error.message);
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
}

// Google Sign-In helpers
function loadGoogleIdentity(callback) {
    if (window.google && window.google.accounts) {
        callback();
        return;
    }
    // If not loaded, poll until it is
    const interval = setInterval(() => {
        if (window.google && window.google.accounts) {
            clearInterval(interval);
            callback();
        }
    }, 100);
}

function setupGoogleButtons() {
    if (!window.CONFIG?.GOOGLE_CLIENT_ID) {
        console.warn('Missing GOOGLE_CLIENT_ID in config.js');
        document.querySelectorAll('.btn-google').forEach(btn => {
            btn.addEventListener('click', () => alert('Google login is not configured.'));
        });
        return;
    }

    const clientId = window.CONFIG.GOOGLE_CLIENT_ID;
    let googleInitialized = false;

    function initGoogleSignIn() {
        if (googleInitialized) return;
        googleInitialized = true;

        google.accounts.id.initialize({
            client_id: clientId,
            callback: async (response) => {
                if (response.credential) {
                    // The active tab determines expected role
                    const activeTab = document.querySelector('#tabGroup .tab-btn.active')?.dataset.tab;
                    const expectedRole = activeTab || 'staff';

                    try {
                        const result = await googleLogin(response.credential);
                        if (result.success) {
                            const userRole = result.data.user.role;
                            if (userRole !== expectedRole) {
                                alert(`This Google account is for ${userRole} users. Use the ${userRole.toUpperCase()} Login tab.`);
                                return;
                            }
                            saveAuth(result.data.token, result.data.user);
                            window.location.replace(getAppRoute('dashboard'));
                        } else {
                            alert('Google login failed: ' + (result.error || 'Unknown error'));
                        }
                    } catch (error) {
                        console.error('Google login error:', error);
                        alert('Connection error during Google login.');
                    }
                }
            },
            auto_select: false,
        });
    }

    loadGoogleIdentity(() => {
        initGoogleSignIn();
        document.querySelectorAll('.btn-google').forEach(btn => {
            btn.addEventListener('click', () => {
                // Ensure the correct tab is active when button clicked
                const tab = btn.dataset.tab;
                if (tab) {
                    document.querySelectorAll('#tabGroup .tab-btn').forEach(t => t.classList.remove('active'));
                    document.querySelector(`#tabGroup .tab-btn[data-tab="${tab}"]`)?.classList.add('active');
                    document.getElementById('panelStaff').classList.toggle('active', tab === 'staff');
                    document.getElementById('panelAdmin').classList.toggle('active', tab === 'admin');
                }
                google.accounts.id.prompt();
            });
        });
    });
}

function init() {
    setupTabSwitching();
    setupGoogleButtons();

    const staffForm = document.querySelector('#panelStaff form');
    const adminForm = document.querySelector('#panelAdmin form');
    if (staffForm) staffForm.addEventListener('submit', handleFormSubmit);
    if (adminForm) adminForm.addEventListener('submit', handleFormSubmit);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
