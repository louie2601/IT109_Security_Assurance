<?php
include '../includes/db.php';

try {
    // Predict the next ID without incrementing
    $stmt = $pdo->query("SELECT last_id FROM user_id_counter");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $last_id = $row ? (int)$row['last_id'] : 0;
    $next_id_num = $last_id + 1;

    // Format the ID
    $year = date('Y');
    $predicted_user_id = $year . '-' . str_pad($next_id_num, 4, '0', STR_PAD_LEFT);

    echo json_encode(['success' => true, 'user_id' => $predicted_user_id]);

} catch (PDOException $e) {
    // Log error and return a generic error message
    error_log("ID Generation Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Could not generate user ID.']);
}
?>