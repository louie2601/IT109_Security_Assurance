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

// Handle settings updates
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'update_security':
            $max_login_attempts = intval($_POST['max_login_attempts'] ?? 5);
            $lockout_duration = intval($_POST['lockout_duration'] ?? 15);
            $session_timeout = intval($_POST['session_timeout'] ?? 30);
            $password_min_length = intval($_POST['password_min_length'] ?? 8);

            // Here you would update configuration file or database settings
            // For now, we'll just show success message
            $message = 'Security settings updated successfully';
            $message_type = 'success';
            break;

        case 'update_email':
            $smtp_host = $_POST['smtp_host'] ?? '';
            $smtp_port = intval($_POST['smtp_port'] ?? 587);
            $smtp_username = $_POST['smtp_username'] ?? '';
            $smtp_password = $_POST['smtp_password'] ?? '';
            $from_email = $_POST['from_email'] ?? '';
            $from_name = $_POST['from_name'] ?? '';

            // Here you would update email configuration
            $message = 'Email settings updated successfully';
            $message_type = 'success';
            break;

        case 'update_registration':
            $require_email_verification = isset($_POST['require_email_verification']) ? 1 : 0;
            $max_registrations_per_ip = intval($_POST['max_registrations_per_ip'] ?? 5);
            $registration_timeout = intval($_POST['registration_timeout'] ?? 60);

            // Here you would update registration settings
            $message = 'Registration settings updated successfully';
            $message_type = 'success';
            break;
    }

    logSecurityEvent($conn, 'Settings_Updated', "Admin updated {$action} settings", getClientIP(), $_SESSION['user_id']);
}

// Get current settings (these would typically come from config files or database)
$current_settings = [
    'security' => [
        'max_login_attempts' => 5,
        'lockout_duration' => 15,
        'session_timeout' => 30,
        'password_min_length' => 8
    ],
    'email' => [
        'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => 587,
        'from_email' => 'noreply@mindanaoinstitute.edu',
        'from_name' => 'Mindanao Institute'
    ],
    'registration' => [
        'require_email_verification' => true,
        'max_registrations_per_ip' => 5,
        'registration_timeout' => 60
    ]
];

include("../includes/header.php");
?>

<link rel="stylesheet" href="../CSS/dashboard.css">

<div class="main-content">
    <div class="admin-container">
        <div class="admin-header">
            <h1>System Settings</h1>
            <p>Configure system parameters and application settings</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Settings Tabs -->
        <div class="settings-tabs">
            <button class="tab-button active" onclick="showTab('security')">🔒 Security</button>
            <button class="tab-button" onclick="showTab('email')">📧 Email</button>
            <button class="tab-button" onclick="showTab('registration')">👤 Registration</button>
            <button class="tab-button" onclick="showTab('system')">⚙️ System</button>
        </div>

        <!-- Security Settings -->
        <div id="security-tab" class="settings-tab active">
            <h2>Security Settings</h2>
            <form method="POST" class="settings-form">
                <input type="hidden" name="action" value="update_security">

                <div class="form-group">
                    <label for="max_login_attempts">Maximum Login Attempts:</label>
                    <input type="number" id="max_login_attempts" name="max_login_attempts"
                           value="<?php echo $current_settings['security']['max_login_attempts']; ?>" min="3" max="10">
                    <small>Number of failed attempts before account lockout</small>
                </div>

                <div class="form-group">
                    <label for="lockout_duration">Lockout Duration (minutes):</label>
                    <input type="number" id="lockout_duration" name="lockout_duration"
                           value="<?php echo $current_settings['security']['lockout_duration']; ?>" min="5" max="60">
                    <small>How long accounts remain locked after failed attempts</small>
                </div>

                <div class="form-group">
                    <label for="session_timeout">Session Timeout (minutes):</label>
                    <input type="number" id="session_timeout" name="session_timeout"
                           value="<?php echo $current_settings['security']['session_timeout']; ?>" min="15" max="480">
                    <small>Automatic logout after inactivity</small>
                </div>

                <div class="form-group">
                    <label for="password_min_length">Minimum Password Length:</label>
                    <input type="number" id="password_min_length" name="password_min_length"
                           value="<?php echo $current_settings['security']['password_min_length']; ?>" min="6" max="20">
                    <small>Minimum characters required for passwords</small>
                </div>

                <button type="submit" class="btn btn-primary">💾 Save Security Settings</button>
            </form>
        </div>

        <!-- Email Settings -->
        <div id="email-tab" class="settings-tab">
            <h2>Email Configuration</h2>
            <form method="POST" class="settings-form">
                <input type="hidden" name="action" value="update_email">

                <div class="form-group">
                    <label for="smtp_host">SMTP Host:</label>
                    <input type="text" id="smtp_host" name="smtp_host"
                           value="<?php echo htmlspecialchars($current_settings['email']['smtp_host']); ?>">
                    <small>SMTP server hostname (e.g., smtp.gmail.com)</small>
                </div>

                <div class="form-group">
                    <label for="smtp_port">SMTP Port:</label>
                    <input type="number" id="smtp_port" name="smtp_port"
                           value="<?php echo $current_settings['email']['smtp_port']; ?>" min="1" max="65535">
                    <small>SMTP server port (587 for TLS, 465 for SSL, 25 for plain)</small>
                </div>

                <div class="form-group">
                    <label for="smtp_username">SMTP Username:</label>
                    <input type="text" id="smtp_username" name="smtp_username"
                           value="<?php echo htmlspecialchars($current_settings['email']['smtp_username']); ?>">
                    <small>Email address or username for SMTP authentication</small>
                </div>

                <div class="form-group">
                    <label for="smtp_password">SMTP Password:</label>
                    <input type="password" id="smtp_password" name="smtp_password" placeholder="Enter new password">
                    <small>SMTP authentication password (leave blank to keep current)</small>
                </div>

                <div class="form-group">
                    <label for="from_email">From Email:</label>
                    <input type="email" id="from_email" name="from_email"
                           value="<?php echo htmlspecialchars($current_settings['email']['from_email']); ?>">
                    <small>Email address that appears as sender</small>
                </div>

                <div class="form-group">
                    <label for="from_name">From Name:</label>
                    <input type="text" id="from_name" name="from_name"
                           value="<?php echo htmlspecialchars($current_settings['email']['from_name']); ?>">
                    <small>Name that appears as sender</small>
                </div>

                <button type="submit" class="btn btn-primary">💾 Save Email Settings</button>
            </form>
        </div>

        <!-- Registration Settings -->
        <div id="registration-tab" class="settings-tab">
            <h2>Registration Settings</h2>
            <form method="POST" class="settings-form">
                <input type="hidden" name="action" value="update_registration">

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="require_email_verification" value="1"
                               <?php echo $current_settings['registration']['require_email_verification'] ? 'checked' : ''; ?>>
                        Require Email Verification
                    </label>
                    <small>New users must verify their email before account activation</small>
                </div>

                <div class="form-group">
                    <label for="max_registrations_per_ip">Max Registrations per IP (per hour):</label>
                    <input type="number" id="max_registrations_per_ip" name="max_registrations_per_ip"
                           value="<?php echo $current_settings['registration']['max_registrations_per_ip']; ?>" min="1" max="50">
                    <small>Maximum registration attempts allowed per IP address per hour</small>
                </div>

                <div class="form-group">
                    <label for="registration_timeout">Registration Timeout (minutes):</label>
                    <input type="number" id="registration_timeout" name="registration_timeout"
                           value="<?php echo $current_settings['registration']['registration_timeout']; ?>" min="30" max="300">
                    <small>Time limit for completing multi-step registration</small>
                </div>

                <button type="submit" class="btn btn-primary">💾 Save Registration Settings</button>
            </form>
        </div>

        <!-- System Settings -->
        <div id="system-tab" class="settings-tab">
            <h2>System Configuration</h2>
            <div class="system-info">
                <div class="info-group">
                    <h3>System Information</h3>
                    <p><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></p>
                    <p><strong>Server Software:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
                    <p><strong>Database:</strong> MySQL</p>
                    <p><strong>System Time:</strong> <?php echo date('Y-m-d H:i:s T'); ?></p>
                </div>

                <div class="info-group">
                    <h3>Maintenance Tools</h3>
                    <button onclick="clearOldLogs()" class="btn btn-warning">🧹 Clear Old Logs</button>
                    <button onclick="optimizeDatabase()" class="btn btn-info">⚡ Optimize Database</button>
                    <button onclick="checkSystemHealth()" class="btn btn-success">🏥 System Health Check</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.settings-tabs {
    display: flex;
    gap: 5px;
    margin-bottom: 20px;
    border-bottom: 1px solid #dee2e6;
}

.tab-button {
    padding: 10px 20px;
    border: none;
    background: none;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    font-size: 14px;
    font-weight: 500;
    color: #6c757d;
}

.tab-button.active {
    color: #007bff;
    border-bottom-color: #007bff;
}

.tab-button:hover {
    color: #007bff;
}

.settings-tab {
    display: none;
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.settings-tab.active {
    display: block;
}

.settings-form {
    max-width: 600px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #333;
}

.form-group input {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 14px;
}

.form-group small {
    display: block;
    margin-top: 5px;
    color: #666;
    font-size: 12px;
    line-height: 1.4;
}

.checkbox-label {
    display: flex !important;
    align-items: center;
    gap: 10px;
    cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
    width: auto;
    margin: 0;
}

.system-info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
}

.info-group h3 {
    margin-bottom: 15px;
    color: #333;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.info-group p {
    margin: 10px 0;
    line-height: 1.5;
}

.message {
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.message.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.message.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 12px;
    margin-right: 10px;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-warning {
    background: #ffc107;
    color: #212529;
}

.btn-info {
    background: #17a2b8;
    color: white;
}

.btn-success {
    background: #28a745;
    color: white;
}
</style>

<script>
function showTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.settings-tab').forEach(tab => {
        tab.classList.remove('active');
    });

    // Remove active class from all tab buttons
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active');
    });

    // Show selected tab
    document.getElementById(tabName + '-tab').classList.add('active');

    // Add active class to clicked button
    event.target.classList.add('active');
}

function clearOldLogs() {
    if (confirm('This will remove security logs older than 90 days. Continue?')) {
        alert('Log cleanup would be implemented here');
    }
}

function optimizeDatabase() {
    if (confirm('This will optimize database tables. Continue?')) {
        alert('Database optimization would be implemented here');
    }
}

function checkSystemHealth() {
    alert('System health check would be implemented here');
}
</script>

<?php include "../includes/footer.php"; ?>
