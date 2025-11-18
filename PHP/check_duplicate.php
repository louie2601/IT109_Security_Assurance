<?php
include("../includes/db.php");

header('Content-Type: application/json');

$response = ['exists' => false];

if (isset($_GET['username'])) {
    $username = trim($_GET['username']);
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $response['exists'] = true;
    }
} elseif (isset($_GET['email'])) {
    $email = trim($_GET['email']);
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $response['exists'] = true;
    }
}

echo json_encode($response);
$conn->close();
?>