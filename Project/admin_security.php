<?php
session_start();
include("../includes/db.php");

// Check admin access
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$stmt = $conn->prepare("SELECT role, is_active FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || $user['role'] !== 'admin' || !$user['is_active']) {
    header("Location: dashboard.php");
    exit;
}

// Get security statistics
$stats = [];

// Failed login attempts (last 24 hours)
$stmt = $conn->prepare("SELECT COUNT(*) as failed FROM login_attempts WHERE success = 0 AND attempt_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
$stmt->execute();
$stats['failed_logins_24h'] = $stmt->get_result()->fetch_assoc()['failed'];

// Failed login attempts (last 7 days)
$stmt = $conn->prepare("SELECT COUNT(*) as failed FROM login_attempts WHERE success = 0 AND attempt_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stmt->execute();
$stats['failed_logins_7d'] = $stmt->get_result()->fetch_assoc()['failed'];

// Successful login attempts (last 24 hours)
$stmt = $conn->prepare("SELECT COUNT(*) as successful FROM login_attempts WHERE success = 1 AND attempt_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
$stmt->execute();
$stats['successful_logins_24h'] = $stmt->get_result()->fetch_assoc()['successful'];

// Security events (last 24 hours)
$stmt = $conn->prepare("SELECT COUNT(*) as events FROM security_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
$stmt->execute();
$stats['security_events_24h'] = $stmt->get_result()->fetch_assoc()['events'];

// CSRF attack attempts (last 24 hours)
$stmt = $conn->prepare("SELECT COUNT(*) as csrf FROM security_log WHERE event_type = 'CSRF_Attack_Attempt' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
$stmt->execute();
$stats['csrf_attempts_24h'] = $stmt->get_result()->fetch_assoc()['csrf'];

// Rate limit exceeded (last 24 hours)
$stmt = $conn->prepare("SELECT COUNT(*) as rate_limit FROM security_log WHERE event_type = 'Rate_Limit_Exceeded' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
$stmt->execute();
$stats['rate_limit_24h'] = $stmt->get_result()->fetch_assoc()['rate_limit'];

// Get recent security events
$stmt = $conn->prepare("
    SELECT sl.*, u.first_name, u.last_name
    FROM security_log sl
    LEFT JOIN users u ON sl.user_id = u.id
    ORDER BY sl.created_at DESC
    LIMIT 50
");
$stmt->execute();
$security_events = $stmt->get_result();

// Get top IP addresses with failed attempts
$stmt = $conn->prepare("
    SELECT ip_address, COUNT(*) as attempts
    FROM login_attempts
    WHERE success = 0 AND attempt_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    GROUP BY ip_address
    ORDER BY attempts DESC
    LIMIT 10
");
$stmt->execute();
$top_failed_ips = $stmt->get_result();

include("../includes/header.php");
?>

<link rel="stylesheet" href="../CSS/dashboard.css">

<div class="main-content">
    <div class="admin-container">
        <div class="admin-header">
            <h1>Security Monitor</h1>
            <p>Monitor security events, login attempts, and system threats</p>
        </div>

        <!-- Security Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">❌</div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['failed_logins_24h']); ?></h3>
                    <p>Failed Logins (24h)</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['successful_logins_24h']); ?></h3>
                    <p>Successful Logins (24h)</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🛡️</div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['csrf_attempts_24h']); ?></h3>
                    <p>CSRF Attempts (24h)</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">⏱️</div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['rate_limit_24h']); ?></h3>
                    <p>Rate Limited (24h)</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['security_events_24h']); ?></h3>
                    <p>Security Events (24h)</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📈</div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['failed_logins_7d']); ?></h3>
                    <p>Failed Logins (7d)</p>
                </div>
            </div>
        </div>

        <!-- Security Events Table -->
        <div class="security-section">
            <h2>Recent Security Events</h2>
            <div class="table-container">
                <table class="security-table">
                    <thead>
                        <tr>
                            <th>Event Type</th>
                            <th>Description</th>
                            <th>User</th>
                            <th>IP Address</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($event = $security_events->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <span class="event-type <?php echo strtolower(str_replace('_', '-', $event['event_type'])); ?>">
                                        <?php echo htmlspecialchars($event['event_type']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($event['description']); ?></td>
                                <td>
                                    <?php if ($event['first_name']): ?>
                                        <?php echo htmlspecialchars($event['first_name'] . ' ' . $event['last_name']); ?>
                                    <?php else: ?>
                                        <em>System</em>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($event['ip_address']); ?></td>
                                <td><?php echo date('M j, Y g:i A', strtotime($event['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Failed Login IPs -->
        <div class="threats-section">
            <h2>Top Failed Login Sources (24h)</h2>
            <div class="table-container">
                <table class="threats-table">
                    <thead>
                        <tr>
                            <th>IP Address</th>
                            <th>Failed Attempts</th>
                            <th>Risk Level</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($ip = $top_failed_ips->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($ip['ip_address']); ?></td>
                                <td><?php echo number_format($ip['attempts']); ?></td>
                                <td>
                                    <?php
                                    $risk = 'Low';
                                    $risk_class = 'low';
                                    if ($ip['attempts'] >= 20) {
                                        $risk = 'High';
                                        $risk_class = 'high';
                                    } elseif ($ip['attempts'] >= 10) {
                                        $risk = 'Medium';
                                        $risk_class = 'medium';
                                    }
                                    ?>
                                    <span class="risk-badge <?php echo $risk_class; ?>"><?php echo $risk; ?></span>
                                </td>
                                <td>
                                    <button onclick="blockIP('<?php echo htmlspecialchars($ip['ip_address']); ?>')" class="btn btn-warning">Block IP</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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

.security-section, .threats-section {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.security-section h2, .threats-section h2 {
    margin-bottom: 20px;
    color: #333;
}

.table-container {
    overflow-x: auto;
}

.security-table, .threats-table {
    width: 100%;
    border-collapse: collapse;
}

.security-table th,
.security-table td,
.threats-table th,
.threats-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.security-table th,
.threats-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #333;
}

.event-type {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
}

.event-type.user-registration { background: #d4edda; color: #155724; }
.event-type.email-verified { background: #cce7ff; color: #0066cc; }
.event-type.login-success { background: #d4edda; color: #155724; }
.event-type.login-failed { background: #f8d7da; color: #721c24; }
.event-type.csrf-attack-attempt { background: #fff3cd; color: #856404; }
.event-type.rate-limit-exceeded { background: #e2e3e5; color: #383d41; }

.risk-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
}

.risk-badge.low { background: #d4edda; color: #155724; }
.risk-badge.medium { background: #fff3cd; color: #856404; }
.risk-badge.high { background: #f8d7da; color: #721c24; }

.btn {
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
}

.btn-warning {
    background: #ffc107;
    color: #212529;
}
</style>

<script>
function blockIP(ipAddress) {
    if (confirm(`Are you sure you want to block IP address: ${ipAddress}?`)) {
        // Here you would implement IP blocking functionality
        alert('IP blocking feature would be implemented here');
    }
}

function refreshData() {
    location.reload();
}

// Auto-refresh every 60 seconds
setInterval(refreshData, 60000);
</script>

<?php include "../includes/footer.php"; ?>
