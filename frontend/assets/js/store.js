window.CheckMateStore = {
    getToken() {
        return localStorage.getItem('checkmate_token')
            || localStorage.getItem('token')
            || localStorage.getItem('authToken')
            || sessionStorage.getItem('checkmate_token')
            || sessionStorage.getItem('token')
            || '';
    },

    getUser() {
        const keys = ['checkmate_user', 'user', 'authUser'];
        for (const key of keys) {
            const raw = localStorage.getItem(key) || sessionStorage.getItem(key);
            if (!raw) continue;

            try {
                return JSON.parse(raw);
            } catch (_) {
                return null;
            }
        }
        return null;
    },

    setSession(token, user) {
        localStorage.setItem('checkmate_token', token);
        localStorage.setItem('checkmate_user', JSON.stringify(user));
    }
};
