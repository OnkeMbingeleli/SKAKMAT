// API Configuration
const API_CONFIG = {
    baseURL: '/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
};

// Attendance Configuration
const ATTENDANCE_CONFIG = {
    checkInStart: '07:00',
    checkInEnd: '10:00',
    lateThreshold: '09:00'
};

// QR Code Configuration
const QR_CONFIG = {
    expiryMinutes: 15,
    refreshInterval: 60000 // 1 minute
};

export { API_CONFIG, ATTENDANCE_CONFIG, QR_CONFIG };