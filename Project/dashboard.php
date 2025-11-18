<?php
session_start();
include("../includes/db.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Get user information
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("s", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    session_destroy();
    header("Location: index.php");
    exit;
}

include("../includes/header.php");
?>

<link rel="stylesheet" href="../CSS/dashboard.css">

<div class="main-content">
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>WELCOME <?php echo htmlspecialchars($user['first_name']); ?></h1>
            <p>Mindanao Institute Dashboard</p>
        </div>
    </div>
</div>

<script>
 

// Auto-refresh session every 30 minutes
setInterval(function() {
    fetch('session_refresh.php')
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                alert('Your session has expired. Please log in again.');
                window.location.href = 'index.php';
            }
        })
        .catch(() => {
            // Silently fail - user can continue
        });
}, 30 * 60 * 1000); // 30 minutes

// Prevent viewing source code
document.addEventListener('keydown', function(e) {
    if (e.keyCode === 123 || 
        (e.ctrlKey && e.shiftKey && (e.keyCode === 73 || e.keyCode === 74)) ||
        (e.ctrlKey && e.keyCode === 85)) {
        e.preventDefault();
        return false;
    }
});

document.addEventListener('contextmenu', function(e) {
    e.preventDefault();
});
</script>

<?php include "../includes/footer.php"; ?>
<script src="../JS/common.js"></script>