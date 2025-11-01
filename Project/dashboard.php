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
$stmt->bind_param("i", $_SESSION['user_id']);
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
            <h1>Welcome, <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>!</h1>
            <p>Mindanao Institute Security Assurance System Dashboard</p>
        </div>
        
        <div class="dashboard-grid">
            <!-- Profile Summary Card -->
            <div class="dashboard-card profile-card">
                <div class="card-header">
                    <h3>Profile Summary</h3>
                    <span class="card-icon">👤</span>
                </div>
                <div class="card-content">
                    <div class="profile-info">
                        <p><strong>Full Name:</strong> <?php echo htmlspecialchars($user['first_name'] . ' ' . ($user['middle_name'] ? $user['middle_name'] . ' ' : '') . $user['last_name'] . ($user['suffix'] ? ' ' . $user['suffix'] : '')); ?></p>
                        <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                        <p><strong>Age:</strong> <?php echo htmlspecialchars($user['age']); ?> years old</p>
                        <p><strong>Sex:</strong> <?php echo htmlspecialchars($user['sex']); ?></p>
                    </div>
                    <div class="card-actions">
                        <a href="profile.php" class="btn btn-primary">View Full Profile</a>
                    </div>
                </div>
            </div>
            
            <!-- Account Information Card -->
            <div class="dashboard-card account-card">
                <div class="card-header">
                    <h3>Account Information</h3>
                    <span class="card-icon">⚙️</span>
                </div>
                <div class="card-content">
                    <div class="account-info">
                        <p><strong>Account Status:</strong> 
                            <span class="status <?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </p>
                        <p><strong>Member Since:</strong> <?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
                        <p><strong>Last Updated:</strong> <?php echo date('F j, Y g:i A', strtotime($user['updated_at'])); ?></p>
                        <p><strong>Role:</strong> <?php echo ucfirst(htmlspecialchars($user['role'])); ?></p>
                    </div>
                    <div class="card-actions">
                        <a href="change_password.php" class="btn btn-secondary">Change Password</a>
                    </div>
                </div>
            </div>
            
            <!-- Address Information Card -->
            <div class="dashboard-card address-card">
                <div class="card-header">
                    <h3>Address Information</h3>
                    <span class="card-icon">📍</span>
                </div>
                <div class="card-content">
                    <div class="address-info">
                        <p><strong>Street:</strong> <?php echo htmlspecialchars($user['street']); ?></p>
                        <p><strong>Barangay:</strong> <?php echo htmlspecialchars($user['barangay']); ?></p>
                        <p><strong>City/Municipality:</strong> <?php echo htmlspecialchars($user['municipal']); ?></p>
                        <p><strong>Province:</strong> <?php echo htmlspecialchars($user['province']); ?></p>
                        <p><strong>Country:</strong> <?php echo htmlspecialchars($user['country']); ?></p>
                        <p><strong>Zip Code:</strong> <?php echo htmlspecialchars($user['zipcode']); ?></p>
                    </div>
                    <div class="card-actions">
                        <a href="edit_profile.php" class="btn btn-secondary">Edit Address</a>
                    </div>
                </div>
            </div>
            
            <!-- Security Information Card -->
            <div class="dashboard-card security-card">
                <div class="card-header">
                    <h3>Security Settings</h3>
                    <span class="card-icon">🔒</span>
                </div>
                <div class="card-content">
                    <div class="security-info">
                        <p><strong>Security Questions:</strong> Configured</p>
                        <p><strong>Password:</strong> Last changed <?php echo date('F j, Y', strtotime($user['updated_at'])); ?></p>
                        <p><strong>Login Sessions:</strong> 
                            <?php
                            $sessionStmt = $conn->prepare("SELECT COUNT(*) as count FROM user_sessions WHERE user_id = ? AND is_active = 1");
                            $sessionStmt->bind_param("i", $_SESSION['user_id']);
                            $sessionStmt->execute();
                            $sessionCount = $sessionStmt->get_result()->fetch_assoc()['count'];
                            echo $sessionCount . ' active session' . ($sessionCount != 1 ? 's' : '');
                            ?>
                        </p>
                    </div>
                    <div class="card-actions">
                        <a href="security_questions.php" class="btn btn-secondary">Update Security Questions</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <h3>Quick Actions</h3>
            <div class="action-buttons">
                <a href="profile.php" class="action-btn">
                    <span class="action-icon">👤</span>
                    <span class="action-text">View Profile</span>
                </a>
                <a href="edit_profile.php" class="action-btn">
                    <span class="action-icon">✏️</span>
                    <span class="action-text">Edit Profile</span>
                </a>
                <a href="change_password.php" class="action-btn">
                    <span class="action-icon">🔑</span>
                    <span class="action-text">Change Password</span>
                </a>
                <a href="security_questions.php" class="action-btn">
                    <span class="action-icon">❓</span>
                    <span class="action-text">Security Questions</span>
                </a>
                <a href="logout.php" class="action-btn logout">
                    <span class="action-icon">🚪</span>
                    <span class="action-text">Logout</span>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Disable back button
history.pushState(null, null, location.href);
window.onpopstate = function () {
    history.go(1);
};

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
