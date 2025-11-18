<?php
session_start();
include("../includes/db.php");

// Get client IP for security logging
$ip_address = getClientIP();

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    $response['message'] = 'Method Not Allowed';
    echo json_encode($response);
    exit;
}

try {
    $email = $_POST['email'] ?? '';

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address');
    }

    // Find user by email
    $stmt = $conn->prepare("SELECT id, first_name, last_name, is_active FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Email address not found');
    }

    $user = $result->fetch_assoc();

    if ($user['is_active']) {
        throw new Exception('Account is already active');
    }

    // Generate a new verification token
    $token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Store the new token in the database
    $stmt = $conn->prepare("INSERT INTO email_verification_tokens (user_id, email, token, expires_at) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user['id'], $email, $token, $expires_at);
    $stmt->execute();

    // Send the verification email
    $verification_link = "http://{$_SERVER['HTTP_HOST']}/IT_109%20SECURITY%20ASSURANCE/Project/verify_email.php?token={$token}";
    $subject = "Verify Your Email Address";
    $message = "Hello {$user['first_name']} {$user['last_name']},<br><br>Please click the following link to verify your email address:<br><a href='{$verification_link}'>{$verification_link}</a><br><br>This link will expire in one hour.<br><br>Thank you,<br>Mindanao Institute";

    // Use a mailer library like PHPMailer for better email sending capabilities
    // For now, we will just log the email content to a file for debugging purposes
    $log_message = "Subject: {$subject}\nMessage: {$message}";
    file_put_contents("../logs/email.log", $log_message, FILE_APPEND);

    // Log security event
    logSecurityEvent($conn, 'Email_Verification_Resent', 'User requested to resend email verification', $ip_address, $user['id']);

    $response['success'] = true;
    $response['message'] = 'Verification email sent successfully!';

} catch (Exception $e) {
    error_log("Email verification resend error: " . $e->getMessage());
    $response['message'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);

?>
