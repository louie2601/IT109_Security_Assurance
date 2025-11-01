<?php
// Only allow AJAX or include requests
if (!(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
      strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
    http_response_code(404);
    exit("Page Not Found");
}

// --- Rest of your PHP code below ---
$conn = new mysqli("localhost", "root", "", "enrollment_system");
header('Content-Type: application/json');

if (isset($_GET['username'])) {
    $username = $conn->real_escape_string($_GET['username']);
    $query = $conn->query("SELECT id FROM users WHERE username = '$username' LIMIT 1");

    echo json_encode(["exists" => $query->num_rows > 0]);
}
$conn->close();
