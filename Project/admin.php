<?php
session_start();
include("../includes/db.php");

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get user information and check admin status
$stmt = $conn->prepare("SELECT role, is_active FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || $user['role'] !== 'admin' || !$user['is_active']) {
    header("Location: dashboard.php");
    exit;
}

// Get system statistics
$stats = [];

// Total users
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'student'");
$stmt->execute();
$stats['total_users'] = $stmt->get_result()->fetch_assoc()['total'];

// Active users
$stmt = $conn->prepare("SELECT COUNT(*) as active FROM users WHERE role = 'student' AND is_active = 1");
$stmt->execute();
$stats['active_users'] = $stmt->get_result()->fetch_assoc()['active'];

// Pending verification
$stmt = $conn->prepare("SELECT COUNT(*) as pending FROM users WHERE role = 'student' AND is_active = 0");
$stmt->execute();
$stats['pending_verification'] = $stmt->get_result()->fetch_assoc()['pending'];

// Recent registrations (last 7 days)
$stmt = $conn->prepare("SELECT COUNT(*) as recent FROM users WHERE role = 'student' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stmt->execute();
$stats['recent_registrations'] = $stmt->get_result()->fetch_assoc()['recent'];

// Failed login attempts (last 24 hours)
$stmt = $conn->prepare("SELECT COUNT(*) as failed FROM login_attempts WHERE success = 0 AND attempt_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
$stmt->execute();
$stats['failed_logins'] = $stmt->get_result()->fetch_assoc()['failed'];

// Security events (last 24 hours)
$stmt = $conn->prepare("SELECT COUNT(*) as events FROM security_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
$stmt->execute();
$stats['security_events'] = $stmt->get_result()->fetch_assoc()['events'];

include("../includes/header.php");
?>

<link rel="stylesheet" href="../CSS/dashboard.css">

<div class="main-content">
    <div class="admin-container">
        <div class="admin-header">
            <h1>Admin Panel</h1>
            <p>Mindanao Institute Security Assurance System - Administrative Dashboard</p>
        </div>

        <!-- System Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['total_users']); ?></h3>
                    <p>Total Users</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['active_users']); ?></h3>
                    <p>Active Users</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">⏳</div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['pending_verification']); ?></h3>
                    <p>Pending Verification</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🆕</div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['recent_registrations']); ?></h3>
                    <p>Recent Registrations</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🚫</div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['failed_logins']); ?></h3>
                    <p>Failed Logins (24h)</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🔒</div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['security_events']); ?></h3>
                    <p>Security Events (24h)</p>
                </div>
            </div>
        </div>

        <!-- Admin Actions -->
        <div class="admin-actions">
            <h2>Management Tools</h2>
            <div class="action-grid">
                <a href="admin_users.php" class="admin-action-card">
                    <div class="action-icon">👥</div>
                    <h3>User Management</h3>
                    <p>View, edit, and manage user accounts</p>
                </a>

                <a href="admin_security.php" class="admin-action-card">
                    <div class="action-icon">🔒</div>
                    <h3>Security Monitor</h3>
                    <p>Monitor security events and login attempts</p>
                </a>

                <a href="admin_verification.php" class="admin-action-card">
                    <div class="action-icon">✅</div>
                    <h3>Email Verification</h3>
                    <p>Manage pending email verifications</p>
                </a>

                <a href="admin_reports.php" class="admin-action-card">
                    <div class="action-icon">📊</div>
                    <h3>Reports & Analytics</h3>
                    <p>Generate system usage reports</p>
                </a>

                <a href="admin_settings.php" class="admin-action-card">
                    <div class="action-icon">⚙️</div>
                    <h3>System Settings</h3>
                    <p>Configure system parameters</p>
                </a>

                <a href="admin_backup.php" class="admin-action-card">
                    <div class="action-icon">💾</div>
                    <h3>Backup & Export</h3>
                    <p>Backup data and export reports</p>
                </a>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="recent-activity">
            <h2>Recent Security Events</h2>
            <div class="activity-list">
                <?php
                $stmt = $conn->prepare("
                    SELECT sl.event_type, sl.description, sl.ip_address, sl.created_at, u.first_name, u.last_name
                    FROM security_log sl
                    LEFT JOIN users u ON sl.user_id = u.id
                    ORDER BY sl.created_at DESC
                    LIMIT 10
                ");
                $stmt->execute();
                $events = $stmt->get_result();

                if ($events->num_rows > 0):
                    while ($event = $events->fetch_assoc()):
                ?>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <?php
                            $icon = '📝';
                            switch ($event['event_type']) {
                                case 'User_Registration': $icon = '👤'; break;
                                case 'Email_Verified': $icon = '✅'; break;
                                case 'Login_Success': $icon = '🔓'; break;
                                case 'Login_Failed': $icon = '❌'; break;
                                case 'Password_Reset': $icon = '🔑'; break;
                                case 'CSRF_Attack_Attempt': $icon = '🛡️'; break;
                                case 'Rate_Limit_Exceeded': $icon = '⏱️'; break;
                            }
                            echo $icon;
                            ?>
                        </div>
                        <div class="activity-details">
                            <div class="activity-title">
                                <?php echo htmlspecialchars($event['event_type']); ?>
                            </div>
                            <div class="activity-description">
                                <?php echo htmlspecialchars($event['description']); ?>
                                <?php if ($event['first_name']): ?>
                                    by <?php echo htmlspecialchars($event['first_name'] . ' ' . $event['last_name']); ?>
                                <?php endif; ?>
                            </div>
                            <div class="activity-meta">
                                IP: <?php echo htmlspecialchars($event['ip_address']); ?> |
                                <?php echo date('M j, Y g:i A', strtotime($event['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                <?php
                    endwhile;
                else:
                ?>
                    <div class="no-activity">
                        <p>No recent security events</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.admin-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.admin-header {
    text-align: center;
    margin-bottom: 40px;
    padding: 40px 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 10px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.stat-card {
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 15px;
}

.stat-icon {
    font-size: 2.5em;
}

.stat-info h3 {
    margin: 0;
    color: #333;
    font-size: 2em;
}

.stat-info p {
    margin: 5px 0 0 0;
    color: #666;
    font-size: 0.9em;
}

.admin-actions {
    margin-bottom: 40px;
}

.admin-actions h2 {
    margin-bottom: 20px;
    color: #333;
}

.action-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.admin-action-card {
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    text-decoration: none;
    color: inherit;
    transition: transform 0.2s, box-shadow 0.2s;
}

.admin-action-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.action-icon {
    font-size: 2.5em;
    margin-bottom: 15px;
}

.admin-action-card h3 {
    margin: 0 0 10px 0;
    color: #333;
}

.admin-action-card p {
    margin: 0;
    color: #666;
    line-height: 1.5;
}

.recent-activity {
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.recent-activity h2 {
    margin-bottom: 20px;
    color: #333;
}

.activity-list {
    max-height: 400px;
    overflow-y: auto;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    padding: 15px;
    border-bottom: 1px solid #eee;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    font-size: 1.5em;
    flex-shrink: 0;
}

.activity-details {
    flex: 1;
}

.activity-title {
    font-weight: bold;
    color: #333;
    margin-bottom: 5px;
}

.activity-description {
    color: #666;
    margin-bottom: 5px;
    line-height: 1.4;
}

.activity-meta {
    font-size: 0.85em;
    color: #999;
}

.no-activity {
    text-align: center;
    padding: 40px;
    color: #666;
}
</style>

<script>
// Auto-refresh data every 30 seconds
setInterval(function() {
    location.reload();
}, 30000);
</script>

<?php include "../includes/footer.php"; ?>
