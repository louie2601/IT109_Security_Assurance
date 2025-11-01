<?php
session_start();
include("../includes/db.php");

$step = $_GET['step'] ?? 1;
$error_message = '';
$success_message = '';
$user_data = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step == 1) {
        // Step 1: Verify username/email
        $username = trim($_POST['username'] ?? '');
        
        if (empty($username)) {
            $error_message = "Please enter your username or email.";
        } else {
            $stmt = $conn->prepare("SELECT id, username, email, first_name, last_name FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user_data = $result->fetch_assoc();
                $_SESSION['reset_user_id'] = $user_data['id'];
                $step = 2;
            } else {
                $error_message = "Username or email not found.";
            }
        }
    } elseif ($step == 2) {
        // Step 2: Verify security questions
        if (!isset($_SESSION['reset_user_id'])) {
            header("Location: forgot_password.php");
            exit;
        }
        
        $user_id = $_SESSION['reset_user_id'];
        $answer1 = trim($_POST['answer1'] ?? '');
        $answer2 = trim($_POST['answer2'] ?? '');
        $answer3 = trim($_POST['answer3'] ?? '');
        
        if (empty($answer1) || empty($answer2) || empty($answer3)) {
            $error_message = "Please answer all security questions.";
        } else {
            $stmt = $conn->prepare("SELECT answer_1, answer_2, answer_3 FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user_answers = $result->fetch_assoc();
            
            if (password_verify(strtolower($answer1), $user_answers['answer_1']) &&
                password_verify(strtolower($answer2), $user_answers['answer_2']) &&
                password_verify(strtolower($answer3), $user_answers['answer_3'])) {
                $step = 3;
            } else {
                $error_message = "One or more security answers are incorrect.";
            }
        }
        
        // Get user data for display
        $stmt = $conn->prepare("SELECT username, email, first_name, last_name FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user_data = $stmt->get_result()->fetch_assoc();
    } elseif ($step == 3) {
        // Step 3: Reset password
        if (!isset($_SESSION['reset_user_id'])) {
            header("Location: forgot_password.php");
            exit;
        }
        
        $user_id = $_SESSION['reset_user_id'];
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($password) || empty($confirm_password)) {
            $error_message = "Please enter and confirm your new password.";
        } elseif ($password !== $confirm_password) {
            $error_message = "Passwords do not match.";
        } elseif (strlen($password) < 8 || !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
            $error_message = "Password must be at least 8 characters with uppercase, lowercase, and number.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($stmt->execute()) {
                $success_message = "Password successfully changed! You can now log in with your new password.";
                unset($_SESSION['reset_user_id']);
                $step = 4;
            } else {
                $error_message = "An error occurred while updating your password. Please try again.";
            }
        }
        
        // Get user data for display
        $stmt = $conn->prepare("SELECT username, email, first_name, last_name FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user_data = $stmt->get_result()->fetch_assoc();
    }
}

// Get user data if we have a session
if (isset($_SESSION['reset_user_id']) && !$user_data) {
    $stmt = $conn->prepare("SELECT username, email, first_name, last_name FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['reset_user_id']);
    $stmt->execute();
    $user_data = $stmt->get_result()->fetch_assoc();
}

include("../includes/header.php");
?>

<link rel="stylesheet" href="../CSS/forgot_password.css">

<div class="main-content">
    <div class="forgot-password-container">
        <div class="forgot-password-header">
            <h2>Password Recovery</h2>
            <p>Mindanao Institute Enrollment and Payment System</p>
        </div>
        
        <!-- Progress Indicator -->
        <div class="step-indicator">
            <div class="step <?php echo $step >= 1 ? 'active' : ''; ?>" data-step="1">
                <div class="step-number">1</div>
                <div class="step-label">Verify Account</div>
            </div>
            <div class="step <?php echo $step >= 2 ? 'active' : ''; ?>" data-step="2">
                <div class="step-number">2</div>
                <div class="step-label">Security Questions</div>
            </div>
            <div class="step <?php echo $step >= 3 ? 'active' : ''; ?>" data-step="3">
                <div class="step-number">3</div>
                <div class="step-label">New Password</div>
            </div>
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
        
        <?php if ($step == 1): ?>
            <!-- Step 1: Verify Username/Email -->
            <form method="POST" action="forgot_password.php?step=1">
                <div class="form-group">
                    <label for="username">Username or Email <span class="required">*</span></label>
                    <input type="text" name="username" id="username" required 
                           placeholder="Enter your username or email address">
                </div>
                
                <button type="submit" class="btn btn-primary">Continue</button>
            </form>
            
        <?php elseif ($step == 2): ?>
            <!-- Step 2: Security Questions -->
            <div class="user-info">
                <p><strong>Account:</strong> <?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?></p>
                <p><strong>Username:</strong> <?php echo htmlspecialchars($user_data['username']); ?></p>
            </div>
            
            <form method="POST" action="forgot_password.php?step=2">
                <h3>Please answer your security questions:</h3>
                
                <div class="form-group">
                    <label for="answer1">Who is your best friend in Elementary? <span class="required">*</span></label>
                    <input type="text" name="answer1" id="answer1" required>
                </div>
                
                <div class="form-group">
                    <label for="answer2">What is the name of your favorite pet? <span class="required">*</span></label>
                    <input type="text" name="answer2" id="answer2" required>
                </div>
                
                <div class="form-group">
                    <label for="answer3">Who is your favorite teacher in high school? <span class="required">*</span></label>
                    <input type="text" name="answer3" id="answer3" required>
                </div>
                
                <div class="button-row">
                    <a href="forgot_password.php" class="btn btn-secondary">Back</a>
                    <button type="submit" class="btn btn-primary">Verify Answers</button>
                </div>
            </form>
            
        <?php elseif ($step == 3): ?>
            <!-- Step 3: New Password -->
            <div class="user-info">
                <p><strong>Account:</strong> <?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?></p>
                <p><strong>Username:</strong> <?php echo htmlspecialchars($user_data['username']); ?></p>
            </div>
            
            <form method="POST" action="forgot_password.php?step=3">
                <h3>Enter your new password:</h3>
                
                <div class="form-group">
                    <label for="password">New Password <span class="required">*</span></label>
                    <div class="password-container">
                        <input type="password" name="password" id="password" required>
                        <button type="button" class="show-password" onclick="togglePassword('password')">👁️</button>
                    </div>
                    <div class="password-strength" id="password_strength"></div>
                    <small>Password must be at least 8 characters with uppercase, lowercase, and number</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Re-enter Password <span class="required">*</span></label>
                    <div class="password-container">
                        <input type="password" name="confirm_password" id="confirm_password" required>
                        <button type="button" class="show-password" onclick="togglePassword('confirm_password')">👁️</button>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Change Password</button>
            </form>
            
        <?php else: ?>
            <!-- Step 4: Success -->
            <div class="success-container">
                <div class="success-icon">✅</div>
                <h3>Password Successfully Changed!</h3>
                <p>Your password has been updated successfully. You can now log in with your new password.</p>
                <a href="index.php" class="btn btn-primary">Go to Login</a>
            </div>
        <?php endif; ?>
        
        <div class="back-to-login">
            <a href="index.php">← Back to Login</a>
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

// Password strength checker
document.addEventListener('DOMContentLoaded', function() {
    const passwordField = document.getElementById('password');
    const strengthIndicator = document.getElementById('password_strength');
    
    if (passwordField && strengthIndicator) {
        passwordField.addEventListener('input', function() {
            const strength = getPasswordStrength(this.value);
            strengthIndicator.textContent = strength.text;
            strengthIndicator.className = `password-strength ${strength.class}`;
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
</script>

<?php include "../includes/footer.php"; ?>
