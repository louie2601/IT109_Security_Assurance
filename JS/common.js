/**
 * Common JavaScript Utilities
 * Shared functions used across multiple pages
 */

 

// Prevent back button navigation
function disableBackButton() {
    history.pushState(null, null, location.href);
    window.onpopstate = function () {
        history.go(1);
    };
}

// Password toggle functionality
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    if (!field) return;
    
    const button = field.nextElementSibling;
    
    if (field.type === 'password') {
        field.type = 'text';
        if (button) button.textContent = '🙈';
    } else {
        field.type = 'password';
        if (button) button.textContent = '👁️';
    }
}

// Initialize security features
function initializeSecurityFeatures() {
    // Prevent viewing source code
    document.addEventListener('keydown', function(e) {
        // Disable F12, Ctrl+Shift+I, Ctrl+Shift+J, Ctrl+U
        if (e.keyCode === 123 ||
            (e.ctrlKey && e.shiftKey && (e.keyCode === 73 || e.keyCode === 74)) ||
            (e.ctrlKey && e.keyCode === 85)) {
            e.preventDefault();
            return false;
        }
    });

    // Disable right-click context menu
    document.addEventListener('contextmenu', function(e) {
        e.preventDefault();
    });

    // Prevent text selection on sensitive elements
    const sensitiveElements = document.querySelectorAll('input[type="password"], .login-btn, button[type="submit"]');
    sensitiveElements.forEach(element => {
        element.addEventListener('selectstart', function(e) {
            e.preventDefault();
        });
    });
}

// Common validation helpers
const ValidationHelpers = {
    // Email validation
    validateEmail: function(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    },

    // Username validation
    validateUsername: function(username) {
        return /^[a-zA-Z0-9]{3,20}$/.test(username);
    },

    // Password validation
    validatePassword: function(password) {
        return password.length >= 8 && 
               /[a-z]/.test(password) && 
               /[A-Z]/.test(password) && 
               /\d/.test(password);
    },

    // Name validation
    validateName: function(name) {
        if (/[^a-zA-Z\s]/.test(name)) return false;
        if (/\s{2,}/.test(name)) return false;
        if (name === name.toUpperCase() && name.length > 1) return false;
        if (/(.)\1{2,}/i.test(name)) return false;
        return true;
    },

    // Get name validation error message
    getNameErrorMessage: function(name) {
        if (/[^a-zA-Z\s]/.test(name)) return 'Only letters and spaces are allowed';
        if (/\s{2,}/.test(name)) return 'Double spaces are not allowed';
        if (name === name.toUpperCase() && name.length > 1) return 'All capital letters are not allowed';
        if (/(.)\1{2,}/i.test(name)) return 'Three or more consecutive identical letters are not allowed';
        return 'Invalid name format';
    },

    // Extension validation
    validateExtension: function(ext) {
        if (!ext || ext.trim() === '') return true;
        const validExtensions = ['Jr', 'Sr', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X'];
        return validExtensions.includes(ext.trim()) || /^[IVX]+$/.test(ext.trim());
    }
};

// Error handling utilities
const ErrorHandler = {
    showError: function(fieldName, message) {
        const errorElement = document.getElementById(`${fieldName}_error`);
        if (errorElement) errorElement.textContent = message;
    },

    clearError: function(fieldName) {
        const errorElement = document.getElementById(`${fieldName}_error`);
        if (errorElement) errorElement.textContent = '';
    },

    clearAllErrors: function() {
        const errorElements = document.querySelectorAll('.field-error, [id$="_error"]');
        errorElements.forEach(error => error.textContent = '');

        // Remove validation classes
        const fields = document.querySelectorAll('input, select');
        fields.forEach(field => {
            field.classList.remove('invalid', 'valid');
        });
    },

    showFieldError: function(field, message) {
        // Remove existing error
        const existingError = field.parentNode.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }

        // Create error element
        const errorElement = document.createElement('div');
        errorElement.className = 'field-error';
        errorElement.textContent = message;
        errorElement.style.color = 'red';
        errorElement.style.fontSize = '12px';
        errorElement.style.marginTop = '5px';

        field.parentNode.appendChild(errorElement);
    }
};

// Auto-capitalization utility
function autoCapitalizeInput(input) {
    const cursorPosition = input.selectionStart;
    const originalValue = input.value;
    const capitalizedValue = originalValue
        .toLowerCase()
        .split(' ')
        .map(word => word.length > 0 ? word.charAt(0).toUpperCase() + word.slice(1) : word)
        .join(' ');
    
    if (originalValue !== capitalizedValue) {
        input.value = capitalizedValue;
        const newCursorPosition = Math.min(cursorPosition, capitalizedValue.length);
        input.setSelectionRange(newCursorPosition, newCursorPosition);
    }
}

// Initialize common features on page load
document.addEventListener('DOMContentLoaded', function() {
    disableBackButton();
    initializeSecurityFeatures();
});

// Make functions globally accessible
window.togglePassword = togglePassword;
window.ValidationHelpers = ValidationHelpers;
window.ErrorHandler = ErrorHandler;
window.autoCapitalizeInput = autoCapitalizeInput;
