<?php
$conn = new mysqli("localhost", "root", "", "enrollment_system");
header('Content-Type: application/json');

if ($conn->connect_error) {
    echo json_encode(["exists" => false]);
    exit;
}

if (isset($_GET['suffix'])) {
    $suffix = $conn->real_escape_string($_GET['suffix']);
    $query = $conn->query("SELECT id FROM users WHERE suffix = '$suffix' LIMIT 1");

    if ($query->num_rows > 0) {
        echo json_encode(["exists" => true]);
    } else {
        echo json_encode(["exists" => false]);
    }
} else {
    echo json_encode(["exists" => false]);
}

$conn->close();
