<?php
// Source code protection - prevent direct file access
if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'Page not Found') !== false) {
    http_response_code(404);
    die("Page not Found");
}

// Only establish a database connection if $conn is not already set (e.g., by a test environment)
if (!isset($conn)) {
    $host = "localhost";
    $username = "root";
    $password = ""; // default is empty for XAMPP
    $database = "sucurity"; // ✅ this must match your phpMyAdmin database name

    $conn = new mysqli($host, $username, $password, $database);

    if ($conn->connect_error) {
        die("❌ Database connection failed: " . $conn->connect_error);
    }

    $conn->set_charset("utf8");
}

// Function to log login attempts
if (!function_exists('logLoginAttempt')) {
    function logLoginAttempt($conn, $username, $ip, $success = false) {
        $stmt = $conn->prepare("INSERT INTO login_attempts (username, ip_address, success) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $username, $ip, $success);
        $stmt->execute();
    }
}

// Function to check login attempts
if (!function_exists('getFailedAttempts')) {
    function getFailedAttempts($conn, $username, $ip, $minutes = 60) {
        $stmt = $conn->prepare("
            SELECT success, attempt_time
            FROM login_attempts
            WHERE (username = ? OR ip_address = ?)
            AND attempt_time > DATE_SUB(NOW(), INTERVAL ? MINUTE)
            ORDER BY attempt_time DESC
        ");
        $stmt->bind_param("ssi", $username, $ip, $minutes);
        $stmt->execute();
        $result = $stmt->get_result();

        $consecutive_failures = 0;
        while ($row = $result->fetch_assoc()) {
            if ($row['success'] == 0) {
                $consecutive_failures++;
            } else {
                // Stop counting if a successful login is encountered
                break;
            }
        }
        return $consecutive_failures;
    }
}

// Function to get lockout time remaining
if (!function_exists('getLockoutTime')) {
    function getLockoutTime($conn, $username, $ip) {
        $attempts = getFailedAttempts($conn, $username, $ip, 60); // Check attempts within the last 60 minutes

        if ($attempts >= 5) return 60; // 60 seconds after 5 attempts
        if ($attempts >= 4) return 30; // 30 seconds after 4 attempts
        if ($attempts >= 3) return 15; // 15 seconds after 3 attempts

        return 0;
    }
}

if (!function_exists('getClientIP')) {
    function getClientIP() {
        if (isset($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) return $_SERVER['HTTP_X_FORWARDED_FOR'];
        if (isset($_SERVER['HTTP_X_FORWARDED'])) return $_SERVER['HTTP_X_FORWARDED'];
        if (isset($_SERVER['HTTP_FORWARDED_FOR'])) return $_SERVER['HTTP_FORWARDED_FOR'];
        if (isset($_SERVER['HTTP_FORWARDED'])) return $_SERVER['HTTP_FORWARDED'];
        if (isset($_SERVER['REMOTE_ADDR'])) return $_SERVER['REMOTE_ADDR'];
        return 'UNKNOWN';
    }
}

if (!function_exists('logSecurityEvent')) {
    function logSecurityEvent($conn, $event_type, $description, $ip_address, $user_id = null) {
        $stmt = $conn->prepare("INSERT INTO security_logs (user_id, event_type, description, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $event_type, $description, $ip_address);
        $stmt->execute();
    }
}

?>
