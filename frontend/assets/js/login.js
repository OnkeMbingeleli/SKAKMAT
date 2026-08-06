function getAppRoute(page, authData = null) {
    const baseUrl = `/public/index.php?page=${page}`;
    if (!authData) {
        return baseUrl;
    }

    const params = new URLSearchParams({
        page,
        token: authData.token,
        user: JSON.stringify(authData.user)
    });

    return `/public/index.php?${params.toString()}`;
}

function saveAuth(token, user) {
    localStorage.setItem('token', token);
    localStorage.setItem('user', JSON.stringify(user));
    document.cookie = `checkmate_token=${encodeURIComponent(token)}; path=/; max-age=3600`;
    document.cookie = `checkmate_user=${encodeURIComponent(JSON.stringify(user))}; path=/; max-age=3600`;
}

function clearAuth() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    document.cookie = 'checkmate_token=; path=/; max-age=0';
    document.cookie = 'checkmate_user=; path=/; max-age=0';
}

window.logoutUser = function logoutUser() {
    clearAuth();
    window.location.href = '/public/index.php?page=login';
};

// Setup tab switching
function setupTabSwitching() {
    const tabs = document.querySelectorAll('#tabGroup .tab-btn');
    const staffPanel = document.getElementById('panelStaff');
    const adminPanel = document.getElementById('panelAdmin');

    if (!tabs.length || !staffPanel || !adminPanel) {
        return;
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', function (e) {
            e.preventDefault();
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            if (this.getAttribute('data-tab') === 'staff') {
                staffPanel.classList.add('active');
                adminPanel.classList.remove('active');
            } else {
                adminPanel.classList.add('active');
                staffPanel.classList.remove('active');
            }
        });
    });
}

// Form submission handler
function handleFormSubmit(event) {
    event.preventDefault();

    const form = event.target;
    const emailField = form.querySelector('input[type="email"]');
    const passwordField = form.querySelector('input[type="password"]');
    const submitBtn = form.querySelector('button[type="submit"]');

    if (!emailField || !passwordField) {
        console.error('Email or password field not found');
        return;
    }

    const staffFormSubmitted = form.closest('#panelStaff') !== null;
    const expectedRole = staffFormSubmitted ? 'staff' : 'admin';
    const email = emailField.value.trim();
    const password = passwordField.value;

    if (!email || !password) {
        alert('Please fill in all fields');
        return;
    }

    submitBtn.disabled = true;
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Signing in...';

    fetch(`${API_URL}/api/login`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ email, password })
    })
        .then(response => response.json())
        .then(data => {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;

            if (data.success) {
                const userRole = data.user.role;
                if (userRole !== expectedRole) {
                    alert(`Login failed: This account is for ${userRole} users. Please use the ${userRole.toUpperCase()} Login tab.`);
                    return;
                }

                saveAuth(data.token, data.user);
                window.location.replace(getAppRoute('dashboard', { token: data.token, user: data.user }));
            } else {
                alert('Login failed: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            alert('Connection error: ' + error.message + '\n\nMake sure the backend server is running at ' + API_URL);
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
}

function init() {
    setupTabSwitching();

    const staffForm = document.querySelector('#panelStaff form');
    const adminForm = document.querySelector('#panelAdmin form');

    if (staffForm) {
        staffForm.addEventListener('submit', handleFormSubmit);
    }
    if (adminForm) {
        adminForm.addEventListener('submit', handleFormSubmit);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}


