// Centralized API helper (token storage, login/logout, generic apiCall) for the frontend.
const API_URL = window.CONFIG?.API_URL || 'http://127.0.0.1:8000';

/**
 * Get the stored JWT token from localStorage
 */
function getToken() {
    // Keep older sessions working while login migrates to the namespaced key.
    const storedToken = localStorage.getItem('token') || localStorage.getItem('checkmate_token');
    if (storedToken) return storedToken;
    const cookie = document.cookie.split('; ').find(item => item.startsWith('checkmate_token='));
    return cookie ? decodeURIComponent(cookie.slice('checkmate_token='.length)) : null;
}

/**
 * Get the stored user data from localStorage
 */
function getUser() {
    const userStr = localStorage.getItem('user');
    return userStr ? JSON.parse(userStr) : null;
}

/**
 * Check if user is authenticated
 */
function isAuthenticated() {
    return !!getToken();
}

/**
 * Login user with email and password
 */
async function login(email, password) {
    try {
        const response = await fetch(`${API_URL}/api/login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ email, password })
        });

        const data = await response.json();

        if (data.success) {
            localStorage.setItem('token', data.token);
            localStorage.setItem('user', JSON.stringify(data.user));
            return { success: true, data };
        } else {
            return { success: false, error: data.error };
        }
    } catch (error) {
        console.error('Login error:', error);
        return { success: false, error: 'Network error. Check if backend is running.' };
    }
}

/**
 * Logout user
 */
function logout() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    const entryPoint = window.location.pathname.includes('/public/')
        ? '/public/index.php'
        : '/index.php';
    window.location.href = `${entryPoint}?page=login`;
}

/**
 * Get user profile (requires authentication)
 */
async function getProfile() {
    try {
        const token = getToken();
        if (!token) {
            return { success: false, error: 'Not authenticated' };
        }

        const response = await fetch(`${API_URL}/api/profile`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json',
            }
        });

        const text = await response.text();
        let data = {};
        try {
            data = text ? JSON.parse(text) : {};
        } catch (parseError) {
            return {
                success: false,
                error: `Invalid server response (${response.status})`,
            };
        }
        if (!response.ok) {
            return {
                success: false,
                error: data.error || data.message || `Request failed (${response.status})`,
            };
        }
        return data;
    } catch (error) {
        console.error('Profile fetch error:', error);
        return { success: false, error: 'Network error' };
    }
}

/**
 * Update the currently signed-in user's password.
 */
async function changePassword(oldPassword, newPassword) {
    return apiCall('/api/profile/password', {
        method: 'PATCH',
        body: JSON.stringify({
            old_password: oldPassword,
            new_password: newPassword,
        }),
    });
}

/**
 * Generic API call with authorization header
 */
async function apiCall(endpoint, options = {}) {
    const token = getToken();
    
    const headers = {
        'Content-Type': 'application/json',
        ...options.headers
    };

    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }

    try {
        const response = await fetch(`${API_URL}${endpoint}`, {
            ...options,
            headers
        });

        const text = await response.text();
        let data = {};
        try {
            data = text ? JSON.parse(text) : {};
        } catch (parseError) {
            return { success: false, error: `Invalid server response (${response.status})`, status: response.status };
        }
        if (!response.ok) {
            return {
                ...data,
                success: false,
                error: data.error || data.message || `Request failed (${response.status})`,
                status: response.status,
            };
        }
        return data;
    } catch (error) {
        console.error('API call error:', error);
        return { success: false, error: 'Network error' };
    }
}

/**
 * Require authentication - redirect if not authenticated
 */
function requireAuth() {
    if (!isAuthenticated()) {
        window.location.href = '/login.php';
        return false;
    }
    return true;
}
