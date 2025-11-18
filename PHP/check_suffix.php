<?php
include("../includes/db.php");
header('Content-Type: application/json');

if (isset($_GET['suffix'])) {
    $suffix = $_GET['suffix'];
    $stmt = $conn->prepare("SELECT id FROM users WHERE suffix = ? LIMIT 1");
    $stmt->bind_param("s", $suffix);
    $stmt->execute();
    $result = $stmt->get_result();

    echo json_encode(["exists" => $result->num_rows > 0]);
    $stmt->close();
} else {
    echo json_encode(["exists" => false]);
}

$conn->close();
