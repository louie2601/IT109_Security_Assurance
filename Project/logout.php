<?php
session_start();
include("../includes/db.php");

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $session_id = session_id();
    
    // Deactivate current session in database
    $stmt = $conn->prepare("UPDATE user_sessions SET is_active = 0 WHERE user_id = ? AND session_id = ?");
    $stmt->bind_param("is", $user_id, $session_id);
    $stmt->execute();
    
    // Clear all session data
    $_SESSION = array();
    
    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
}

// Redirect to login page
header("Location: index.php?logout=success");
exit;
?>
