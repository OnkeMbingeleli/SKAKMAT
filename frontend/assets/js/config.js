// Frontend config: API base URL + localStorage key prefix.

// process.env doesn't exist in a plain browser script — detect prod by hostname instead.
const IS_PRODUCTION = !['localhost', '127.0.0.1'].includes(window.location.hostname);

const CONFIG = {
    API_URL: IS_PRODUCTION
        ? 'https://api.skakmat.app'  // Update with your production URL
        : 'http://127.0.0.1:8000',

    STORAGE_PREFIX: 'skakmat_',
    TOKEN_KEY: 'token',
    USER_KEY: 'user'
};

// Helper to get full storage key
function getStorageKey(key) {
    return CONFIG.STORAGE_PREFIX + key;
}
