/**
 * API Helper Functions
 * Centralized API calls for the CheckMate frontend
 */

const API_URL = 'http://127.0.0.1:8000';

/**
 * Get the stored JWT token from localStorage
 */
function getToken() {
    return localStorage.getItem('token');
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
    window.location.href = '/login.php';
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

        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Profile fetch error:', error);
        return { success: false, error: 'Network error' };
    }
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

        const data = await response.json();
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
