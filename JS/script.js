
 

// Password toggle function
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const button = field.nextElementSibling;
    
    if (field.type === 'password') {
        field.type = 'text';
        button.textContent = '🙈';
    } else {
        field.type = 'password';
        button.textContent = '👁️';
    }
}

// Enhanced lockout timer with better state management
document.addEventListener('DOMContentLoaded', function() {
    const timerElement = document.getElementById('lockoutTimer');
    if (timerElement) {
        let timeRemaining = parseInt(timerElement.dataset.time);
        const timeDisplay = document.getElementById('timeRemaining');
        const loginBtn = document.getElementById('loginBtn');
        const usernameField = document.getElementById('username');
        const passwordField = document.getElementById('password');
        const registerLink = document.getElementById('registerLink');

        // Disable elements during lockout
        loginBtn.disabled = true;
        usernameField.disabled = true;
        passwordField.disabled = true;
        if (registerLink) {
            registerLink.style.pointerEvents = 'none';
            registerLink.style.color = '#ccc';
        }

        const countdown = setInterval(function() {
            timeRemaining--;
            timeDisplay.textContent = timeRemaining;

            if (timeRemaining <= 0) {
                clearInterval(countdown);
                timerElement.style.display = 'none';
                loginBtn.disabled = false;
                usernameField.disabled = false;
                passwordField.disabled = false;
                if (registerLink) {
                    registerLink.style.pointerEvents = 'auto';
                    registerLink.style.color = '';
                }
                location.reload(); // Refresh to reset the form
            }
        }, 1000);
    }
});

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
