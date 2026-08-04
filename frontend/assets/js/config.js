/**
 * Frontend Configuration
 */

// Change this based on environment (development vs production)
const CONFIG = {
    API_URL: process.env.NODE_ENV === 'production' 
        ? 'https://api.checkmate.app'  // Update with your production URL
        : 'http://localhost:8080',
    
    STORAGE_PREFIX: 'checkmate_',
    TOKEN_KEY: 'token',
    USER_KEY: 'user'
};

// Helper to get full storage key
function getStorageKey(key) {
    return CONFIG.STORAGE_PREFIX + key;
}
