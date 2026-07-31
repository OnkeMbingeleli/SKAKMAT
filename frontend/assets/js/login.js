/**
 * Login Handler
 * Handles tab switching and form submission for login page
 */

const API_URL = 'http://127.0.0.1:8000';

// Setup tab switching
function setupTabSwitching() {
    const tabs = document.querySelectorAll('#tabGroup .tab-btn');
    const staffPanel = document.getElementById('panelStaff');
    const adminPanel = document.getElementById('panelAdmin');
    
    console.log('Setting up tab switching. Found', tabs.length, 'tabs');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
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
    console.log('Form submitted');
    
    const form = event.target;
    const emailField = form.querySelector('input[type="email"]');
    const passwordField = form.querySelector('input[type="password"]');
    const submitBtn = form.querySelector('button[type="submit"]');
    
    if (!emailField || !passwordField) {
        console.error('Email or password field not found');
        return;
    }
    
    // Determine which form was submitted
    const panelStaff = document.getElementById('panelStaff');
    const staffFormSubmitted = form.closest('#panelStaff') !== null;
    const expectedRole = staffFormSubmitted ? 'staff' : 'admin';
    
    console.log('Form type:', staffFormSubmitted ? 'Staff' : 'Admin');
    console.log('Expected role:', expectedRole);
    
    const email = emailField.value.trim();
    const password = passwordField.value;

    console.log('Submitting login with email:', email);

    if (!email || !password) {
        alert('Please fill in all fields');
        return;
    }

    // Disable button and show loading state
    submitBtn.disabled = true;
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Signing in...';

    // Send login request to backend
    fetch(`${API_URL}/api/login`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ email, password })
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Login response:', data);
        
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
        
        if (data.success) {
            // Check if the user's role matches the login form used
            const userRole = data.user.role;
            console.log('User role from backend:', userRole, 'Expected role:', expectedRole);
            
            if (userRole !== expectedRole) {
                alert(`Login failed: This account is for ${userRole} users. Please use the ${userRole.toUpperCase()} Login tab.`);
                return;
            }
            
            // Save token and user data to localStorage
            localStorage.setItem('token', data.token);
            localStorage.setItem('user', JSON.stringify(data.user));
            
            console.log('Login successful! Token saved. Role:', userRole);
            alert('Login successful!');
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

// Initialize everything when DOM is ready
function init() {
    console.log('Login handler initializing...');
    
    setupTabSwitching();
    
    // Attach form handlers
    const staffForm = document.querySelector('#panelStaff form');
    const adminForm = document.querySelector('#panelAdmin form');
    
    console.log('Staff form found:', !!staffForm);
    console.log('Admin form found:', !!adminForm);
    
    if (staffForm) {
        staffForm.addEventListener('submit', handleFormSubmit);
        console.log('Attached submit handler to staff form');
    }
    if (adminForm) {
        adminForm.addEventListener('submit', handleFormSubmit);
        console.log('Attached submit handler to admin form');
    }
    
    console.log('Login handler initialized');
}

// Run on DOM ready
if (document.readyState === 'loading') {
    console.log('DOM still loading, waiting for DOMContentLoaded');
    document.addEventListener('DOMContentLoaded', init);
} else {
    console.log('DOM already loaded, initializing immediately');
    init();
}


