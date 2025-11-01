<?php
session_start();
include("../includes/db.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate input
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "All fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "New passwords do not match.";
    } elseif (strlen($new_password) < 8 || !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $new_password)) {
        $error_message = "Password must be at least 8 characters with uppercase, lowercase, and number.";
    } else {
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if (!password_verify($current_password, $user['password'])) {
            $error_message = "Current password is incorrect.";
        } else {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $success_message = "Password successfully changed!";
                
                // Log out all other sessions for security
                $current_session = session_id();
                $stmt = $conn->prepare("UPDATE user_sessions SET is_active = 0 WHERE user_id = ? AND session_id != ?");
                $stmt->bind_param("is", $_SESSION['user_id'], $current_session);
                $stmt->execute();
            } else {
                $error_message = "An error occurred while updating your password. Please try again.";
            }
        }
    }
}

include("../includes/header.php");
?>

<link rel="stylesheet" href="../CSS/change_password.css">

<div class="main-content">
    <div class="change-password-container">
        <div class="change-password-header">
            <h2>Change Password</h2>
            <p>Update your account password</p>
        </div>
        
        <?php if ($error_message): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="change_password.php" id="changePasswordForm">
            <div class="form-group">
                <label for="current_password">Current Password <span class="required">*</span></label>
                <div class="password-container">
                    <input type="password" name="current_password" id="current_password" required>
                    <button type="button" class="show-password" onclick="togglePassword('current_password')">👁️</button>
                </div>
            </div>
            
            <div class="form-group">
                <label for="new_password">New Password <span class="required">*</span></label>
                <div class="password-container">
                    <input type="password" name="new_password" id="new_password" required>
                    <button type="button" class="show-password" onclick="togglePassword('new_password')">👁️</button>
                </div>
                <div class="password-strength" id="password_strength"></div>
                <small>Password must be at least 8 characters with uppercase, lowercase, and number</small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password <span class="required">*</span></label>
                <div class="password-container">
                    <input type="password" name="confirm_password" id="confirm_password" required>
                    <button type="button" class="show-password" onclick="togglePassword('confirm_password')">👁️</button>
                </div>
                <div class="password-match" id="password_match"></div>
            </div>
            
            <div class="button-row">
                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Change Password</button>
            </div>
        </form>
        
        <div class="security-note">
            <h4>Security Note:</h4>
            <ul>
                <li>Your password should be unique and not used elsewhere</li>
                <li>Changing your password will log out all other devices</li>
                <li>Use a combination of letters, numbers, and symbols</li>
                <li>Avoid using personal information in your password</li>
            </ul>
        </div>
    </div>
</div>

<script>
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

// Password strength checker and match validator
document.addEventListener('DOMContentLoaded', function() {
    const newPasswordField = document.getElementById('new_password');
    const confirmPasswordField = document.getElementById('confirm_password');
    const strengthIndicator = document.getElementById('password_strength');
    const matchIndicator = document.getElementById('password_match');
    
    // Password strength checker
    if (newPasswordField && strengthIndicator) {
        newPasswordField.addEventListener('input', function() {
            const strength = getPasswordStrength(this.value);
            strengthIndicator.textContent = strength.text;
            strengthIndicator.className = `password-strength ${strength.class}`;
            
            // Also check match when new password changes
            checkPasswordMatch();
        });
    }
    
    // Password match checker
    if (confirmPasswordField && matchIndicator) {
        confirmPasswordField.addEventListener('input', checkPasswordMatch);
    }
    
    function checkPasswordMatch() {
        const newPassword = newPasswordField.value;
        const confirmPassword = confirmPasswordField.value;
        
        if (confirmPassword === '') {
            matchIndicator.textContent = '';
            matchIndicator.className = 'password-match';
        } else if (newPassword === confirmPassword) {
            matchIndicator.textContent = 'Passwords match';
            matchIndicator.className = 'password-match match';
        } else {
            matchIndicator.textContent = 'Passwords do not match';
            matchIndicator.className = 'password-match no-match';
        }
    }
    
    // Form validation
    const form = document.getElementById('changePasswordForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const newPassword = newPasswordField.value;
            const confirmPassword = confirmPasswordField.value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (newPassword.length < 8 || !/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(newPassword)) {
                e.preventDefault();
                alert('Password must be at least 8 characters with uppercase, lowercase, and number!');
                return false;
            }
        });
    }
});

function getPasswordStrength(password) {
    let score = 0;
    
    if (password.length >= 8) score++;
    if (/[a-z]/.test(password)) score++;
    if (/[A-Z]/.test(password)) score++;
    if (/\d/.test(password)) score++;
    if (/[^a-zA-Z\d]/.test(password)) score++;
    
    if (score < 3) return { text: 'Weak', class: 'weak' };
    if (score < 5) return { text: 'Medium', class: 'medium' };
    return { text: 'Strong', class: 'strong' };
}

// Disable back button
history.pushState(null, null, location.href);
window.onpopstate = function () {
    history.go(1);
};

// Prevent viewing source code
document.addEventListener('keydown', function(e) {
    if (e.keyCode === 123 || 
        (e.ctrlKey && e.shiftKey && (e.keyCode === 73 || e.keyCode === 74)) ||
        (e.ctrlKey && e.keyCode === 85)) {
        e.preventDefault();
        return false;
    }
});

document.addEventListener('contextmenu', function(e) {
    e.preventDefault();
});
</script>

<?php include "../includes/footer.php"; ?>
