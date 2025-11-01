/**
 * Login Page JavaScript
 * Handles login form validation and lockout timer
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize form validation
    initializeLoginForm();
});

// Initialize login form with enhanced validation
function initializeLoginForm() {
    const usernameField = document.getElementById('username');
    const passwordField = document.getElementById('password');
    const loginForm = document.getElementById('loginForm');

    if (usernameField && passwordField && loginForm) {
        // Real-time validation
        usernameField.addEventListener('input', function() {
            validateField(this, 'username');
        });

        usernameField.addEventListener('blur', function() {
            validateField(this, 'username');
        });

        passwordField.addEventListener('input', function() {
            validateField(this, 'password');
        });

        // Form submission validation
        loginForm.addEventListener('submit', function(e) {
            if (!validateLoginForm()) {
                e.preventDefault();
                return false;
            }
        });
    }

    // Handle lockout timer
    const timerElement = document.getElementById('lockoutTimer');
    if (timerElement) {
        startLockoutTimer(timerElement);
    }
}

// Enhanced lockout timer with better state management
function startLockoutTimer(timerElement) {
    let timeRemaining = parseInt(timerElement.dataset.time);

    // ✅ Enforce maximum of 60 seconds
    if (isNaN(timeRemaining) || timeRemaining > 60) {
        timeRemaining = 60;
    } else if (timeRemaining < 0) {
        timeRemaining = 0;
    }

    const timeDisplay = document.getElementById('timeRemaining');
    const loginBtn = document.getElementById('loginBtn');
    const usernameField = document.getElementById('username');
    const passwordField = document.getElementById('password');
    const registerLink = document.getElementById('registerLink');
    const forgotPasswordContainer = document.querySelector('.forgot-password');

    // Disable all interactive elements during lockout
    if (loginBtn) loginBtn.disabled = true;
    if (usernameField) usernameField.disabled = true;
    if (passwordField) passwordField.disabled = true;
    if (registerLink) {
        registerLink.style.pointerEvents = 'none';
        registerLink.style.color = '#ccc';
    }
    if (forgotPasswordContainer) {
        forgotPasswordContainer.style.opacity = '0.5';
        forgotPasswordContainer.style.pointerEvents = 'none';
    }

    const countdown = setInterval(function() {
        timeRemaining--;
        if (timeDisplay) timeDisplay.textContent = timeRemaining;

        if (timeRemaining <= 0) {
            clearInterval(countdown);
            if (timerElement) timerElement.style.display = 'none';
            if (loginBtn) loginBtn.disabled = false;
            if (usernameField) usernameField.disabled = false;
            if (passwordField) passwordField.disabled = false;
            if (registerLink) {
                registerLink.style.pointerEvents = 'auto';
                registerLink.style.color = '';
            }
            if (forgotPasswordContainer) {
                forgotPasswordContainer.style.opacity = '1';
                forgotPasswordContainer.style.pointerEvents = 'auto';
            }
            location.reload(); // Refresh to reset the form
        }
    }, 1000);
}

// Enhanced form validation
function validateLoginForm() {
    const username = document.getElementById('username');
    const password = document.getElementById('password');
    let isValid = true;

    // Clear previous errors
    ErrorHandler.clearAllErrors();

    // Validate username
    if (username && !validateField(username, 'username')) {
        isValid = false;
    }

    // Validate password
    if (password && !validateField(password, 'password')) {
        isValid = false;
    }

    return isValid;
}

function validateField(field, fieldType) {
    const value = field.value.trim();
    let isValid = true;
    let errorMessage = '';

    switch (fieldType) {
        case 'username':
            if (!value) {
                errorMessage = 'Username or email is required';
                isValid = false;
            } else if (value.length < 3) {
                errorMessage = 'Username must be at least 3 characters';
                isValid = false;
            } else if (!/^[a-zA-Z0-9@._-]+$/.test(value)) {
                errorMessage = 'Username contains invalid characters';
                isValid = false;
            }
            break;

        case 'password':
            if (!value) {
                errorMessage = 'Password is required';
                isValid = false;
            } else if (value.length < 6) {
                errorMessage = 'Password must be at least 6 characters';
                isValid = false;
            }
            break;
    }

    if (!isValid) {
        ErrorHandler.showFieldError(field, errorMessage);
        field.classList.add('invalid');
    } else {
        field.classList.remove('invalid');
        field.classList.add('valid');
    }

    return isValid;
}
