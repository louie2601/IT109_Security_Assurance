<?php
session_start();
global $conn; // Declare $conn as global
include(dirname(__FILE__) . "/../includes/db.php");

$step = $_GET['step'] ?? 1;

if ($step == 1) {
    unset($_SESSION['submitted_security_data']);
}

$error_message = '';
$success_message = '';
$user_data = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step == 1) {
        $id_number = trim($_POST['id_number'] ?? '');
        
        if (empty($id_number)) {
            $error_message = "Please enter your ID Number.";
        } else {
            $stmt = $conn->prepare("SELECT id, username, email, first_name, last_name FROM users WHERE id = ?");
            $stmt->bind_param("s", $id_number);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user_data = $result->fetch_assoc();
                $_SESSION['reset_user_id'] = $user_data['id'];
                $step = 2;
            } else {
                $error_message = "ID Number not found.";
            }
        }
    } elseif ($step == 2) {
        // Step 2: Verify security questions
        if (!isset($_SESSION['reset_user_id'])) {
            header("Location: forgot_password.php");
            exit;
        }
        
        $user_id = $_SESSION['reset_user_id'];
        $submitted_questions = $_POST['security_question'] ?? [];
        $submitted_answers = $_POST['security_answer'] ?? [];
        
        $_SESSION['submitted_security_data'] = [
            'questions' => $submitted_questions,
            'answers' => $submitted_answers
        ];

        if (count($submitted_questions) !== 3 || count(array_filter($submitted_answers)) !== 3) {
            $error_message = "Please answer all 3 security questions.";
        } elseif (count(array_unique($submitted_questions)) !== 3) {
            $error_message = "Please select three distinct security questions.";
        } else {
            $stmt = $conn->prepare("SELECT security_question_1, answer_1, security_question_2, answer_2, security_question_3, answer_3 FROM users WHERE id = ?");
            $stmt->bind_param("s", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user_security = $result->fetch_assoc();

            $valid_pairs = [
                $user_security['security_question_1'] => $user_security['answer_1'],
                $user_security['security_question_2'] => $user_security['answer_2'],
                $user_security['security_question_3'] => $user_security['answer_3']
            ];

            $answer_correctness = [];
            for ($i = 0; $i < 3; $i++) {
                $submitted_q = $submitted_questions[$i];
                $submitted_a = $submitted_answers[$i];
                $is_correct = false;

                if (isset($valid_pairs[$submitted_q])) {
                    if (password_verify(strtolower(trim($submitted_a)), $valid_pairs[$submitted_q])) {
                        $is_correct = true;
                    }
                }
                $answer_correctness[$i] = $is_correct;
            }

            $correct_answers = count(array_filter($answer_correctness));
            $_SESSION['submitted_security_data']['correctness'] = $answer_correctness;

            if ($correct_answers === 3) {
                unset($_SESSION['submitted_security_data']);
                $step = 3;
            } else {
                $error_message = "One or more security answers are incorrect.";
            }
        }
        
        // Get user data for display
        $stmt = $conn->prepare("SELECT username, email, first_name, last_name FROM users WHERE id = ?");
        $stmt->bind_param("s", $user_id);
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
                unset($_SESSION['submitted_security_data']);
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
                    <label for="id_number">ID Number <span class="required"></span></label>
                    <input type="text" name="id_number" id="id_number" required 
                           placeholder="Enter your ID Number">
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
                
                <?php
                $stmt = $conn->prepare("SELECT security_question_1, security_question_2, security_question_3 FROM users WHERE id = ?");
                $stmt->bind_param("s", $_SESSION['reset_user_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $questions = $result->fetch_assoc();
                
                $submitted_data = $_SESSION['submitted_security_data'] ?? null;
                $answer_correctness = $submitted_data['correctness'] ?? null;

                for ($i = 1; $i <= 3; $i++) {
                    $selected_question = $submitted_data['questions'][$i-1] ?? '';
                    $submitted_answer = $submitted_data['answers'][$i-1] ?? '';
                    $is_correct = $answer_correctness[$i-1] ?? null;
                ?>
                <div class="form-group">
                    <label for="security_question_<?php echo $i; ?>">Security Question <?php echo $i; ?> <span class="required"></span></label>
                    <select name="security_question[]" id="security_question_<?php echo $i; ?>" required>
                        <option value="">-- Select a Question --</option>
                        <option value="<?php echo htmlspecialchars($questions['security_question_1']); ?>" <?php if($selected_question == $questions['security_question_1']) echo 'selected'; ?>><?php echo htmlspecialchars($questions['security_question_1']); ?></option>
                        <option value="<?php echo htmlspecialchars($questions['security_question_2']); ?>" <?php if($selected_question == $questions['security_question_2']) echo 'selected'; ?>><?php echo htmlspecialchars($questions['security_question_2']); ?></option>
                        <option value="<?php echo htmlspecialchars($questions['security_question_3']); ?>" <?php if($selected_question == $questions['security_question_3']) echo 'selected'; ?>><?php echo htmlspecialchars($questions['security_question_3']); ?></option>
                    </select>
                    <div class="password-container">
                        <input type="password" name="security_answer[]" id="security_answer_<?php echo $i; ?>" placeholder="Answer" required value="<?php echo htmlspecialchars($submitted_answer); ?>">
                        <button type="button" class="show-password" onclick="togglePassword('security_answer_<?php echo $i; ?>')">👁️</button>
                        <?php if (isset($_POST['security_answer']) && $is_correct === true): ?>
                            <span class="answer-status correct">✔</span>
                        <?php elseif (isset($_POST['security_answer']) && $is_correct === false): ?>
                            <span class="answer-status incorrect">✖</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php } ?>
                
                <div class="button-row">
                    <a href="index.php" class="btn btn-secondary">Back to Login</a>
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
                    <label for="password">New Password <span class="required"></span></label>
                    <div class="password-container">
                        <input type="password" name="password" id="password" required>
                        <button type="button" class="show-password" onclick="togglePassword('password')">👁️</button>
                    </div>
                    <div class="password-strength" id="password_strength"></div>
                    <small>Password must be at least 8 characters with uppercase, lowercase, and number</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Re-enter Password <span class="required"></span></label>
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

 
</script>

<?php include "../includes/footer.php"; ?>
