// Frontend config: API base URL + localStorage key prefix.

// process.env doesn't exist in a plain browser script — detect prod by hostname instead.
const IS_PRODUCTION = !['localhost', '127.0.0.1'].includes(window.location.hostname);

const CONFIG = {
    API_URL: IS_PRODUCTION
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
