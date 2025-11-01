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

// Generate reports based on type
$report_type = $_GET['type'] ?? 'overview';
$timeframe = $_GET['timeframe'] ?? '7d';

// Calculate date range
$end_date = date('Y-m-d');
switch ($timeframe) {
    case '24h':
        $start_date = date('Y-m-d', strtotime('-1 day'));
        break;
    case '7d':
        $start_date = date('Y-m-d', strtotime('-7 days'));
        break;
    case '30d':
        $start_date = date('Y-m-d', strtotime('-30 days'));
        break;
    case '90d':
        $start_date = date('Y-m-d', strtotime('-90 days'));
        break;
    default:
        $start_date = date('Y-m-d', strtotime('-7 days'));
}

// Get report data based on type
$report_data = [];
$report_title = '';

switch ($report_type) {
    case 'user_registration':
        $report_title = 'User Registration Report';

        // Daily registrations
        $stmt = $conn->prepare("
            SELECT DATE(created_at) as date, COUNT(*) as count
            FROM users
            WHERE created_at >= ? AND role = 'student'
            GROUP BY DATE(created_at)
            ORDER BY date
        ");
        $stmt->bind_param("s", $start_date);
        $stmt->execute();
        $report_data['registrations'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Total registrations in period
        $stmt = $conn->prepare("
            SELECT COUNT(*) as total FROM users
            WHERE created_at >= ? AND role = 'student'
        ");
        $stmt->bind_param("s", $start_date);
        $stmt->execute();
        $report_data['total_registrations'] = $stmt->get_result()->fetch_assoc()['total'];

        break;

    case 'login_activity':
        $report_title = 'Login Activity Report';

        // Login attempts over time
        $stmt = $conn->prepare("
            SELECT DATE(attempt_time) as date, success, COUNT(*) as count
            FROM login_attempts
            WHERE attempt_time >= ?
            GROUP BY DATE(attempt_time), success
            ORDER BY date, success
        ");
        $stmt->bind_param("s", $start_date);
        $stmt->execute();
        $report_data['login_attempts'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Success rate
        $stmt = $conn->prepare("
            SELECT
                COUNT(CASE WHEN success = 1 THEN 1 END) as successful,
                COUNT(*) as total
            FROM login_attempts
            WHERE attempt_time >= ?
        ");
        $stmt->bind_param("s", $start_date);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $report_data['success_rate'] = $result['total'] > 0 ? round(($result['successful'] / $result['total']) * 100, 1) : 0;

        break;

    case 'security_events':
        $report_title = 'Security Events Report';

        // Security events by type
        $stmt = $conn->prepare("
            SELECT event_type, COUNT(*) as count
            FROM security_log
            WHERE created_at >= ?
            GROUP BY event_type
            ORDER BY count DESC
        ");
        $stmt->bind_param("s", $start_date);
        $stmt->execute();
        $report_data['events_by_type'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Events over time
        $stmt = $conn->prepare("
            SELECT DATE(created_at) as date, COUNT(*) as count
            FROM security_log
            WHERE created_at >= ?
            GROUP BY DATE(created_at)
            ORDER BY date
        ");
        $stmt->bind_param("s", $start_date);
        $stmt->execute();
        $report_data['events_over_time'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        break;

    default:
        $report_title = 'System Overview Report';

        // Key metrics
        $stmt = $conn->prepare("SELECT COUNT(*) as total_users FROM users WHERE role = 'student'");
        $stmt->execute();
        $report_data['total_users'] = $stmt->get_result()->fetch_assoc()['total_users'];

        $stmt = $conn->prepare("SELECT COUNT(*) as active_users FROM users WHERE role = 'student' AND is_active = 1");
        $stmt->execute();
        $report_data['active_users'] = $stmt->get_result()->fetch_assoc()['active_users'];

        $stmt = $conn->prepare("SELECT COUNT(*) as pending_users FROM users WHERE role = 'student' AND is_active = 0");
        $stmt->execute();
        $report_data['pending_users'] = $stmt->get_result()->fetch_assoc()['pending_users'];

        // Recent activity (last 30 items)
        $stmt = $conn->prepare("
            SELECT 'User Registration' as type, first_name, created_at as date
            FROM users
            WHERE role = 'student'
            UNION ALL
            SELECT 'Security Event' as type, event_type as first_name, created_at as date
            FROM security_log
            ORDER BY date DESC
            LIMIT 30
        ");
        $stmt->execute();
        $report_data['recent_activity'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

include("../includes/header.php");
?>

<link rel="stylesheet" href="../CSS/dashboard.css">

<div class="main-content">
    <div class="admin-container">
        <div class="admin-header">
            <h1>Reports & Analytics</h1>
            <p>Generate comprehensive system reports and analytics</p>
        </div>

        <!-- Report Controls -->
        <div class="report-controls">
            <div class="control-group">
                <label>Report Type:</label>
                <select id="reportType" onchange="changeReport()">
                    <option value="overview" <?php echo $report_type === 'overview' ? 'selected' : ''; ?>>System Overview</option>
                    <option value="user_registration" <?php echo $report_type === 'user_registration' ? 'selected' : ''; ?>>User Registration</option>
                    <option value="login_activity" <?php echo $report_type === 'login_activity' ? 'selected' : ''; ?>>Login Activity</option>
                    <option value="security_events" <?php echo $report_type === 'security_events' ? 'selected' : ''; ?>>Security Events</option>
                </select>
            </div>

            <div class="control-group">
                <label>Time Frame:</label>
                <select id="timeframe" onchange="changeReport()">
                    <option value="24h" <?php echo $timeframe === '24h' ? 'selected' : ''; ?>>Last 24 Hours</option>
                    <option value="7d" <?php echo $timeframe === '7d' ? 'selected' : ''; ?>>Last 7 Days</option>
                    <option value="30d" <?php echo $timeframe === '30d' ? 'selected' : ''; ?>>Last 30 Days</option>
                    <option value="90d" <?php echo $timeframe === '90d' ? 'selected' : ''; ?>>Last 90 Days</option>
                </select>
            </div>

            <div class="control-group">
                <button onclick="exportReport()" class="btn btn-primary">📊 Export Report</button>
                <button onclick="printReport()" class="btn btn-secondary">🖨️ Print Report</button>
            </div>
        </div>

        <!-- Report Content -->
        <div class="report-content">
            <div class="report-header">
                <h2><?php echo htmlspecialchars($report_title); ?></h2>
                <p class="report-period">
                    Period: <?php echo date('M j, Y', strtotime($start_date)); ?> - <?php echo date('M j, Y'); ?>
                </p>
            </div>

            <?php if ($report_type === 'overview'): ?>
                <!-- System Overview -->
                <div class="overview-stats">
                    <div class="stat-card large">
                        <div class="stat-icon">👥</div>
                        <div class="stat-info">
                            <h3><?php echo number_format($report_data['total_users']); ?></h3>
                            <p>Total Users</p>
                        </div>
                    </div>

                    <div class="stat-card large">
                        <div class="stat-icon">✅</div>
                        <div class="stat-info">
                            <h3><?php echo number_format($report_data['active_users']); ?></h3>
                            <p>Active Users</p>
                        </div>
                    </div>

                    <div class="stat-card large">
                        <div class="stat-icon">⏳</div>
                        <div class="stat-info">
                            <h3><?php echo number_format($report_data['pending_users']); ?></h3>
                            <p>Pending Verification</p>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="activity-section">
                    <h3>Recent System Activity</h3>
                    <div class="activity-list">
                        <?php foreach ($report_data['recent_activity'] as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-type"><?php echo htmlspecialchars($activity['type']); ?></div>
                                <div class="activity-details">
                                    <?php if ($activity['type'] === 'User Registration'): ?>
                                        <?php echo htmlspecialchars($activity['first_name']); ?> registered
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($activity['first_name']); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="activity-time">
                                    <?php echo date('M j, g:i A', strtotime($activity['date'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            <?php elseif ($report_type === 'user_registration'): ?>
                <!-- User Registration Report -->
                <div class="chart-section">
                    <h3>Daily Registrations</h3>
                    <div class="chart-placeholder">
                        <canvas id="registrationChart" width="400" height="200"></canvas>
                    </div>
                </div>

                <div class="summary-stats">
                    <p><strong>Total Registrations (Period):</strong> <?php echo number_format($report_data['total_registrations']); ?></p>
                    <p><strong>Average per Day:</strong> <?php echo number_format($report_data['total_registrations'] / max(1, count($report_data['registrations'])), 1); ?></p>
                </div>

            <?php elseif ($report_type === 'login_activity'): ?>
                <!-- Login Activity Report -->
                <div class="chart-section">
                    <h3>Login Success Rate: <?php echo $report_data['success_rate']; ?>%</h3>
                    <div class="chart-placeholder">
                        <canvas id="loginChart" width="400" height="200"></canvas>
                    </div>
                </div>

            <?php elseif ($report_type === 'security_events'): ?>
                <!-- Security Events Report -->
                <div class="events-summary">
                    <h3>Security Events by Type</h3>
                    <div class="events-list">
                        <?php foreach ($report_data['events_by_type'] as $event): ?>
                            <div class="event-item">
                                <span class="event-type"><?php echo htmlspecialchars($event['event_type']); ?></span>
                                <span class="event-count"><?php echo number_format($event['count']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.report-controls {
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    gap: 20px;
    align-items: center;
    flex-wrap: wrap;
}

.control-group {
    display: flex;
    align-items: center;
    gap: 10px;
}

.control-group label {
    font-weight: 500;
    color: #333;
}

.control-group select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.report-content {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.report-header {
    border-bottom: 2px solid #eee;
    padding-bottom: 15px;
    margin-bottom: 30px;
}

.report-header h2 {
    margin: 0 0 10px 0;
    color: #333;
}

.report-period {
    color: #666;
    margin: 0;
    font-style: italic;
}

.overview-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card.large {
    padding: 30px;
    text-align: center;
}

.stat-card.large .stat-icon {
    font-size: 3em;
    margin-bottom: 15px;
}

.activity-section {
    margin-top: 30px;
}

.activity-section h3 {
    margin-bottom: 15px;
    color: #333;
}

.activity-list {
    max-height: 400px;
    overflow-y: auto;
    border: 1px solid #eee;
    border-radius: 5px;
}

.activity-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    border-bottom: 1px solid #f0f0f0;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-type {
    font-weight: 500;
    color: #007bff;
}

.activity-details {
    flex: 1;
    margin-left: 15px;
    color: #333;
}

.activity-time {
    color: #666;
    font-size: 0.9em;
}

.chart-section {
    margin-bottom: 30px;
}

.chart-section h3 {
    margin-bottom: 15px;
    color: #333;
}

.chart-placeholder {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 5px;
    padding: 20px;
    text-align: center;
    color: #6c757d;
}

.summary-stats {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 5px;
    margin-top: 20px;
}

.summary-stats p {
    margin: 10px 0;
    font-size: 1.1em;
}

.events-summary h3 {
    margin-bottom: 20px;
    color: #333;
}

.events-list {
    display: grid;
    gap: 10px;
}

.event-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 15px;
    background: #f8f9fa;
    border-radius: 5px;
}

.event-type {
    font-weight: 500;
    color: #333;
}

.event-count {
    background: #007bff;
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.9em;
    font-weight: 500;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 12px;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}
</style>

<script>
function changeReport() {
    const reportType = document.getElementById('reportType').value;
    const timeframe = document.getElementById('timeframe').value;
    window.location.href = `?type=${reportType}&timeframe=${timeframe}`;
}

function exportReport() {
    const reportType = document.getElementById('reportType').value;
    const timeframe = document.getElementById('timeframe').value;
    alert(`Export functionality would generate ${reportType} report for ${timeframe} period`);
}

function printReport() {
    window.print();
}

// Simple chart drawing (you could replace with Chart.js)
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($report_type === 'user_registration' && isset($report_data['registrations'])): ?>
        drawRegistrationChart(<?php echo json_encode($report_data['registrations']); ?>);
    <?php endif; ?>

    <?php if ($report_type === 'login_activity' && isset($report_data['login_attempts'])): ?>
        drawLoginChart(<?php echo json_encode($report_data['login_attempts']); ?>);
    <?php endif; ?>
});

function drawRegistrationChart(data) {
    const canvas = document.getElementById('registrationChart');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    // Simple bar chart implementation
    let maxCount = Math.max(...data.map(d => d.count));
    let barWidth = canvas.width / data.length;

    data.forEach((item, index) => {
        const height = (item.count / maxCount) * canvas.height * 0.8;
        const x = index * barWidth;
        const y = canvas.height - height;

        ctx.fillStyle = '#007bff';
        ctx.fillRect(x, y, barWidth - 2, height);
    });
}

function drawLoginChart(data) {
    const canvas = document.getElementById('loginChart');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    // Simple implementation for login success/failure
    const successData = data.filter(d => d.success == 1);
    const failureData = data.filter(d => d.success == 0);

    // This would be implemented with actual chart drawing
    ctx.fillStyle = '#28a745';
    ctx.fillRect(50, 50, 100, 50);

    ctx.fillStyle = '#dc3545';
    ctx.fillRect(200, 50, 100, 50);
}
</script>

<?php include "../includes/footer.php"; ?>
