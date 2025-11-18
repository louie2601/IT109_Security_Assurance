<?php
session_start();
include("../includes/db.php");

// Check admin access
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$stmt = $conn->prepare("SELECT role, is_active FROM users WHERE id = ?");
$stmt->bind_param("s", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || $user['role'] !== 'admin' || !$user['is_active']) {
    header("Location: dashboard.php");
    exit;
}

// Handle manual verification actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = $_POST['user_id'] ?? '';

    if ($user_id && $action === 'verify_user') {
        $conn->begin_transaction();
        try {
            // Activate user account
            $stmt = $conn->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
            $stmt->bind_param("s", $user_id);
            $stmt->execute();

            // Mark any pending verification tokens as used
            $stmt = $conn->prepare("UPDATE email_verification_tokens SET used = 1 WHERE user_id = ? AND used = 0");
            $stmt->bind_param("s", $user_id);
            $stmt->execute();

            $conn->commit();

            logSecurityEvent($conn, 'User_Manually_Verified', 'User manually verified by admin', getClientIP(), $_SESSION['user_id']);
            $success_message = 'User verified successfully';
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = 'Failed to verify user';
        }
    }
}

// Get pending verifications
$stmt = $conn->prepare("
    SELECT u.id, u.first_name, u.last_name, u.email, u.created_at,
           evt.token, evt.created_at as token_created
    FROM users u
    LEFT JOIN email_verification_tokens evt ON u.id = evt.user_id AND evt.used = 0
    WHERE u.is_active = 0 AND u.role = 'student'
    ORDER BY u.created_at DESC
");
$stmt->execute();
$pending_users = $stmt->get_result();

// Get verification statistics
$stats = [];

// Total pending verifications
$stmt = $conn->prepare("SELECT COUNT(*) as pending FROM users WHERE is_active = 0 AND role = 'student'");
$stmt->execute();
$stats['pending_total'] = $stmt->get_result()->fetch_assoc()['pending'];

// Pending verifications older than 24 hours
$stmt = $conn->prepare("SELECT COUNT(*) as old FROM users WHERE is_active = 0 AND role = 'student' AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
$stmt->execute();
$stats['pending_old'] = $stmt->get_result()->fetch_assoc()['old'];

// Total verification tokens issued (last 7 days)
$stmt = $conn->prepare("SELECT COUNT(*) as tokens FROM email_verification_tokens WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stmt->execute();
$stats['tokens_7d'] = $stmt->get_result()->fetch_assoc()['tokens'];

// Verification success rate (last 7 days)
$stmt = $conn->prepare("
    SELECT
        COUNT(CASE WHEN used = 1 THEN 1 END) as successful,
        COUNT(*) as total
    FROM email_verification_tokens
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
");
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stats['success_rate'] = $result['total'] > 0 ? round(($result['successful'] / $result['total']) * 100, 1) : 0;

include("../includes/header.php");
?>

<link rel="stylesheet" href="../CSS/dashboard.css">

<div class="main-content">
    <div class="admin-container">
        <div class="admin-header">
            <h1>Email Verification Management</h1>
            <p>Manage pending email verifications and user activation</p>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Verification Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">⏳</div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['pending_total']); ?></h3>
                    <p>Pending Verifications</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🕐</div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['pending_old']); ?></h3>
                    <p>Pending > 24h</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📧</div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['tokens_7d']); ?></h3>
                    <p>Tokens Issued (7d)</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📈</div>
                <div class="stat-info">
                    <h3><?php echo $stats['success_rate']; ?>%</h3>
                    <p>Success Rate (7d)</p>
                </div>
            </div>
        </div>

        <!-- Pending Verifications Table -->
        <div class="verification-section">
            <h2>Pending Email Verifications</h2>
            <div class="table-container">
                <table class="verification-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Registration Date</th>
                            <th>Verification Token</th>
                            <th>Token Age</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($pending_users->num_rows > 0): ?>
                            <?php while ($user = $pending_users->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <?php if ($user['token']): ?>
                                            <code><?php echo substr($user['token'], 0, 16) . '...'; ?></code>
                                        <?php else: ?>
                                            <em>No token</em>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        if ($user['token']) {
                                            $token_age = strtotime($user['token_created']);
                                            $hours_old = floor((time() - $token_age) / 3600);
                                            echo "{$hours_old}h ago";
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <button onclick="verifyUser('<?php echo $user['id']; ?>')" class="btn btn-success">✅ Verify User</button>
                                        <button onclick="resendVerification('<?php echo htmlspecialchars($user['email']); ?>')" class="btn btn-primary">📧 Resend Email</button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="no-data">No pending verifications</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h2>Quick Actions</h2>
            <div class="action-buttons">
                <button onclick="sendBulkVerification()" class="btn btn-primary">📧 Send All Pending</button>
                <button onclick="cleanupOldTokens()" class="btn btn-warning">🧹 Cleanup Old Tokens</button>
                <button onclick="exportPendingList()" class="btn btn-secondary">📊 Export List</button>
            </div>
        </div>
    </div>
</div>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 15px;
}

.stat-icon {
    font-size: 2em;
}

.stat-info h3 {
    margin: 0;
    color: #333;
    font-size: 1.8em;
}

.stat-info p {
    margin: 5px 0 0 0;
    color: #666;
    font-size: 0.9em;
}

.verification-section {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.verification-section h2 {
    margin-bottom: 20px;
    color: #333;
}

.table-container {
    overflow-x: auto;
}

.verification-table {
    width: 100%;
    border-collapse: collapse;
}

.verification-table th,
.verification-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.verification-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #333;
}

.verification-table code {
    background: #f8f9fa;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: monospace;
    font-size: 11px;
}

.no-data {
    text-align: center;
    padding: 40px;
    color: #666;
}

.quick-actions {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.quick-actions h2 {
    margin-bottom: 15px;
    color: #333;
}

.action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 12px;
}

.btn-success {
    background: #28a745;
    color: white;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-warning {
    background: #ffc107;
    color: #212529;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.success-message {
    background: #d4edda;
    color: #155724;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.error-message {
    background: #f8d7da;
    color: #721c24;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}
</style>

<script>
function verifyUser(userId) {
    if (confirm('Are you sure you want to manually verify this user?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="user_id" value="${userId}">
            <input type="hidden" name="action" value="verify_user">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function resendVerification(email) {
    fetch('../PHP/email_verification.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'email=' + encodeURIComponent(email)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Verification email sent successfully!');
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('An error occurred. Please try again later.');
    });
}

function sendBulkVerification() {
    if (confirm('This will send verification emails to all users with pending verifications. Continue?')) {
        alert('Bulk email sending would be implemented here');
    }
}

function cleanupOldTokens() {
    if (confirm('This will remove expired verification tokens. Continue?')) {
        alert('Token cleanup would be implemented here');
    }
}

function exportPendingList() {
    alert('Export functionality would be implemented here');
}
</script>

<?php include "../includes/footer.php"; ?>
