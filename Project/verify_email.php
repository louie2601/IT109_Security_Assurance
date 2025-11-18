<?php
session_start();
include("../includes/db.php");

// Get client IP for security logging
$ip_address = getClientIP();

$message = '';
$message_type = 'error';

if (isset($_GET['token'])) {
    try {
        $token = $_GET['token'];

        if (empty($token)) {
            throw new Exception('Verification token is required');
        }

        // Find valid, unused token that hasn't expired
        $stmt = $conn->prepare("
            SELECT evt.id, evt.user_id, evt.email, u.first_name, u.last_name
            FROM email_verification_tokens evt
            JOIN users u ON evt.user_id = u.id
            WHERE evt.token = ? AND evt.used = 0 AND evt.expires_at > NOW()
        ");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception('Invalid or expired verification token');
        }

        $verification = $result->fetch_assoc();

        // Mark token as used and activate user account
        $conn->begin_transaction();

        try {
            // Mark token as used
            $stmt = $conn->prepare("UPDATE email_verification_tokens SET used = 1 WHERE id = ?");
            $stmt->bind_param("i", $verification['id']);
            $stmt->execute();

            // Activate user account
            $stmt = $conn->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
            $stmt->bind_param("i", $verification['user_id']);
            $stmt->execute();

            $conn->commit();

            // Log successful verification
            logSecurityEvent($conn, 'Email_Verified', 'User email verified successfully', $ip_address, $verification['user_id']);

            $message = "Email verified successfully! Welcome {$verification['first_name']} {$verification['last_name']}, your account is now active.";
            $message_type = 'success';

        } catch (Exception $e) {
            $conn->rollback();
            throw new Exception('Failed to verify email. Please try again.');
        }

    } catch (Exception $e) {
        error_log("Email verification page error: " . $e->getMessage());
        $message = $e->getMessage();
        $message_type = 'error';
    }
} else {
    $message = "A verification email has been sent to your email address. Please check your inbox and follow the instructions to activate your account.";
    $message_type = 'success';
}

include("../includes/header.php");
?>

<link rel="stylesheet" href="../CSS/login.css">

<div class="main-content">
    <div class="login-container">
        <div class="login-header">
            <h2>Email Verification</h2>
            <p>Mindanao Institute Security Assurance System</p>
        </div>

        <div class="verification-message <?php echo $message_type; ?>">
            <p><?php echo htmlspecialchars($message); ?></p>
        </div>

        <div class="verification-actions">
    <a href="login.php" class="btn btn-secondary">Back to Login</a>
    <button onclick="resendVerification()" class="btn btn-primary">Resend Verification Email</button>
</div>
    </div>
</div>

<script>
function resendVerification() {
    const email = prompt('Please enter your email address to resend verification:');
    if (email && email.includes('@')) {
        fetch('../PHP/email_verification.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'email=' + encodeURIComponent(email)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Verification email sent successfully!');
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('An error occurred. Please try again later.');
        });
    } else {
        alert('Please enter a valid email address.');
    }
}
</script>

<?php include "../includes/footer.php"; ?>
