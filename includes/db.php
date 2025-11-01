<?php
// Source code protection - prevent direct file access
if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'Page not Found') !== false) {
    http_response_code(404);
    die("Page not Found");
}

$host = "localhost";
$username = "root";
$password = ""; // default is empty for XAMPP
$database = "sucurity"; // ✅ this must match your phpMyAdmin database name

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("❌ Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8");

// Function to log login attempts
function logLoginAttempt($conn, $username, $ip, $success = false) {
    $stmt = $conn->prepare("INSERT INTO login_attempts (username, ip_address, success) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $username, $ip, $success);
    $stmt->execute();
}

// Function to check login attempts
function getFailedAttempts($conn, $username, $ip, $minutes = 15) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) as attempts
        FROM login_attempts
        WHERE (username = ? OR ip_address = ?)
        AND success = 0
        AND attempt_time > DATE_SUB(NOW(), INTERVAL ? MINUTE)
    ");
    $stmt->bind_param("ssi", $username, $ip, $minutes);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['attempts'];
}

// Function to get lockout time remaining
function getLockoutTime($conn, $username, $ip) {
    $attempts = getFailedAttempts($conn, $username, $ip, 60);

    if ($attempts >= 9) return 60; // 60 seconds after 9 attempts
    if ($attempts >= 6) return 30; // 30 seconds after 6 attempts
    if ($attempts >= 3) return 15; // 15 seconds after 3 attempts

    return 0;
}


?>
