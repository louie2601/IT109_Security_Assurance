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

// Handle backup and export actions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create_backup':
            try {
                // Get all table names
                $tables_result = $conn->query("SHOW TABLES");
                $tables = [];

                while ($row = $tables_result->fetch_row()) {
                    $tables[] = $row[0];
                }

                // Create backup directory if it doesn't exist
                $backup_dir = '../backups/';
                if (!file_exists($backup_dir)) {
                    mkdir($backup_dir, 0755, true);
                }

                $backup_file = $backup_dir . 'backup_' . date('Y-m-d_H-i-s') . '.sql';

                // Generate SQL dump
                $sql_dump = "-- Mindanao Institute Security Assurance System Backup\n";
                $sql_dump .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";

                foreach ($tables as $table) {
                    // Get table structure
                    $create_result = $conn->query("SHOW CREATE TABLE `$table`");
                    $create_row = $create_result->fetch_row();
                    $sql_dump .= "DROP TABLE IF EXISTS `$table`;\n";
                    $sql_dump .= $create_row[1] . ";\n\n";

                    // Get table data
                    $data_result = $conn->query("SELECT * FROM `$table`");
                    if ($data_result->num_rows > 0) {
                        $sql_dump .= "INSERT INTO `$table` VALUES\n";
                        $rows = [];
                        while ($row = $data_result->fetch_row()) {
                            $values = array_map(function($value) use ($conn) {
                                return isset($value) ? "'" . $conn->real_escape_string($value) . "'" : 'NULL';
                            }, $row);
                            $rows[] = "(" . implode(", ", $values) . ")";
                        }
                        $sql_dump .= implode(",\n", $rows) . ";\n\n";
                    }
                }

                // Save backup file
                file_put_contents($backup_file, $sql_dump);

                $message = 'Database backup created successfully: ' . basename($backup_file);
                $message_type = 'success';

                logSecurityEvent($conn, 'Database_Backup', 'Database backup created by admin', getClientIP(), $_SESSION['user_id']);

            } catch (Exception $e) {
                $message = 'Failed to create backup: ' . $e->getMessage();
                $message_type = 'error';
            }
            break;

        case 'export_users':
            // Export users data as CSV
            $filename = 'users_export_' . date('Y-m-d_H-i-s') . '.csv';
            $filepath = '../exports/' . $filename;

            // Create exports directory if it doesn't exist
            if (!file_exists('../exports/')) {
                mkdir('../exports/', 0755, true);
            }

            $stmt = $conn->prepare("
                SELECT id, first_name, last_name, email, username, is_active, created_at
                FROM users
                WHERE role = 'student'
                ORDER BY created_at DESC
            ");
            $stmt->execute();
            $users = $stmt->get_result();

            $csv_content = "ID,First Name,Last Name,Email,Username,Status,Registration Date\n";

            while ($user = $users->fetch_assoc()) {
                $csv_content .= implode(',', [
                    $user['id'],
                    '"' . str_replace('"', '""', $user['first_name']) . '"',
                    '"' . str_replace('"', '""', $user['last_name']) . '"',
                    $user['email'],
                    $user['username'],
                    $user['is_active'] ? 'Active' : 'Inactive',
                    $user['created_at']
                ]) . "\n";
            }

            file_put_contents($filepath, $csv_content);

            $message = 'Users data exported successfully: ' . $filename;
            $message_type = 'success';

            logSecurityEvent($conn, 'Data_Export', 'Users data exported by admin', getClientIP(), $_SESSION['user_id']);
            break;

        case 'export_logs':
            // Export security logs as CSV
            $filename = 'security_logs_' . date('Y-m-d_H-i-s') . '.csv';
            $filepath = '../exports/' . $filename;

            $stmt = $conn->prepare("
                SELECT sl.event_type, sl.description, sl.ip_address, sl.created_at, u.first_name, u.last_name
                FROM security_log sl
                LEFT JOIN users u ON sl.user_id = u.id
                ORDER BY sl.created_at DESC
                LIMIT 10000
            ");
            $stmt->execute();
            $logs = $stmt->get_result();

            $csv_content = "Event Type,Description,IP Address,User,Timestamp\n";

            while ($log = $logs->fetch_assoc()) {
                $csv_content .= implode(',', [
                    '"' . str_replace('"', '""', $log['event_type']) . '"',
                    '"' . str_replace('"', '""', $log['description']) . '"',
                    $log['ip_address'],
                    '"' . str_replace('"', '""', $log['first_name'] . ' ' . $log['last_name']) . '"',
                    $log['created_at']
                ]) . "\n";
            }

            file_put_contents($filepath, $csv_content);

            $message = 'Security logs exported successfully: ' . $filename;
            $message_type = 'success';

            logSecurityEvent($conn, 'Logs_Export', 'Security logs exported by admin', getClientIP(), $_SESSION['user_id']);
            break;
    }
}

// Get backup information
$backup_info = [];
$backups_dir = '../backups/';
if (file_exists($backups_dir)) {
    $backup_files = scandir($backups_dir);
    foreach ($backup_files as $file) {
        if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $backup_info[] = [
                'filename' => $file,
                'size' => filesize($backups_dir . $file),
                'created' => date('Y-m-d H:i:s', filemtime($backups_dir . $file))
            ];
        }
    }
}

// Get export information
$export_info = [];
$exports_dir = '../exports/';
if (file_exists($exports_dir)) {
    $export_files = scandir($exports_dir);
    foreach ($export_files as $file) {
        if ($file !== '.' && $file !== '..') {
            $export_info[] = [
                'filename' => $file,
                'size' => filesize($exports_dir . $file),
                'created' => date('Y-m-d H:i:s', filemtime($exports_dir . $file))
            ];
        }
    }
}

include("../includes/header.php");
?>

<link rel="stylesheet" href="../CSS/dashboard.css">

<div class="main-content">
    <div class="admin-container">
        <div class="admin-header">
            <h1>Backup & Export</h1>
            <p>Create database backups and export system data</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Backup Actions -->
        <div class="backup-section">
            <h2>Database Backup</h2>
            <div class="backup-actions">
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="create_backup">
                    <button type="submit" class="btn btn-primary" onclick="return confirm('This will create a full database backup. Continue?')">💾 Create Full Backup</button>
                </form>

                <button onclick="downloadSample()" class="btn btn-secondary">📋 Generate Sample Data</button>
                <button onclick="cleanupOldBackups()" class="btn btn-warning">🧹 Cleanup Old Backups</button>
            </div>

            <?php if (!empty($backup_info)): ?>
                <div class="backup-list">
                    <h3>Available Backups</h3>
                    <div class="file-list">
                        <?php foreach ($backup_info as $backup): ?>
                            <div class="file-item">
                                <div class="file-info">
                                    <strong><?php echo htmlspecialchars($backup['filename']); ?></strong>
                                    <br>
                                    <small>
                                        Size: <?php echo formatFileSize($backup['size']); ?> |
                                        Created: <?php echo $backup['created']; ?>
                                    </small>
                                </div>
                                <div class="file-actions">
                                    <button onclick="downloadFile('../backups/<?php echo urlencode($backup['filename']); ?>')" class="btn btn-small btn-primary">⬇️ Download</button>
                                    <button onclick="deleteFile('../backups/<?php echo urlencode($backup['filename']); ?>', '<?php echo htmlspecialchars($backup['filename']); ?>')" class="btn btn-small btn-danger">🗑️ Delete</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Export Actions -->
        <div class="export-section">
            <h2>Data Export</h2>
            <div class="export-actions">
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="export_users">
                    <button type="submit" class="btn btn-success">👥 Export Users Data</button>
                </form>

                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="export_logs">
                    <button type="submit" class="btn btn-info">📋 Export Security Logs</button>
                </form>

                <button onclick="exportReports()" class="btn btn-secondary">📊 Export All Reports</button>
            </div>

            <?php if (!empty($export_info)): ?>
                <div class="export-list">
                    <h3>Available Exports</h3>
                    <div class="file-list">
                        <?php foreach ($export_info as $export): ?>
                            <div class="file-item">
                                <div class="file-info">
                                    <strong><?php echo htmlspecialchars($export['filename']); ?></strong>
                                    <br>
                                    <small>
                                        Size: <?php echo formatFileSize($export['size']); ?> |
                                        Created: <?php echo $export['created']; ?>
                                    </small>
                                </div>
                                <div class="file-actions">
                                    <button onclick="downloadFile('../exports/<?php echo urlencode($export['filename']); ?>')" class="btn btn-small btn-primary">⬇️ Download</button>
                                    <button onclick="deleteFile('../exports/<?php echo urlencode($export['filename']); ?>', '<?php echo htmlspecialchars($export['filename']); ?>')" class="btn btn-small btn-danger">🗑️ Delete</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- System Information -->
        <div class="system-info-section">
            <h2>System Information</h2>
            <div class="info-grid">
                <div class="info-card">
                    <h3>Database Status</h3>
                    <p><strong>Database:</strong> <?php echo $database; ?></p>
                    <p><strong>Tables:</strong>
                        <?php
                        $tables_result = $conn->query("SHOW TABLES");
                        echo $tables_result->num_rows . ' tables';
                        ?>
                    </p>
                    <p><strong>Database Size:</strong> <?php echo getDatabaseSize(); ?></p>
                </div>

                <div class="info-card">
                    <h3>File Storage</h3>
                    <p><strong>Backups:</strong> <?php echo count($backup_info); ?> files</p>
                    <p><strong>Exports:</strong> <?php echo count($export_info); ?> files</p>
                    <p><strong>Total Size:</strong> <?php echo formatFileSize(array_sum(array_column($backup_info, 'size')) + array_sum(array_column($export_info, 'size'))); ?></p>
                </div>

                <div class="info-card">
                    <h3>Maintenance</h3>
                    <button onclick="optimizeTables()" class="btn btn-info">⚡ Optimize Tables</button>
                    <button onclick="checkIntegrity()" class="btn btn-success">🔍 Check Integrity</button>
                    <button onclick="cleanupTempFiles()" class="btn btn-warning">🧹 Cleanup Temp Files</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.backup-actions, .export-actions {
    margin-bottom: 30px;
}

.backup-actions .btn, .export-actions .btn {
    margin-right: 10px;
    margin-bottom: 10px;
}

.backup-list, .export-list {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.file-list {
    max-height: 300px;
    overflow-y: auto;
}

.file-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    border-bottom: 1px solid #eee;
}

.file-item:last-child {
    border-bottom: none;
}

.file-info strong {
    color: #333;
}

.file-info small {
    color: #666;
}

.file-actions {
    display: flex;
    gap: 5px;
}

.system-info-section {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.info-card {
    padding: 20px;
    border: 1px solid #dee2e6;
    border-radius: 5px;
}

.info-card h3 {
    margin-bottom: 15px;
    color: #333;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.info-card p {
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
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-success {
    background: #28a745;
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

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.btn-small {
    padding: 4px 8px;
    font-size: 11px;
}
</style>

<script>
function downloadFile(filepath) {
    // Create a temporary link to trigger download
    const link = document.createElement('a');
    link.href = filepath;
    link.download = filepath.split('/').pop();
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function deleteFile(filepath, filename) {
    if (confirm(`Are you sure you want to delete ${filename}?`)) {
        // Here you would implement file deletion via AJAX
        alert('File deletion would be implemented here');
    }
}

function downloadSample() {
    alert('Sample data generation would be implemented here');
}

function cleanupOldBackups() {
    if (confirm('This will remove backup files older than 30 days. Continue?')) {
        alert('Old backup cleanup would be implemented here');
    }
}

function exportReports() {
    alert('Reports export would be implemented here');
}

function optimizeTables() {
    if (confirm('This will optimize all database tables. Continue?')) {
        alert('Table optimization would be implemented here');
    }
}

function checkIntegrity() {
    if (confirm('This will check database integrity and fix any issues. Continue?')) {
        alert('Integrity check would be implemented here');
    }
}

function cleanupTempFiles() {
    if (confirm('This will remove temporary files and old cached data. Continue?')) {
        alert('Temp file cleanup would be implemented here');
    }
}

function formatFileSize(bytes) {
    if (bytes >= 1073741824) {
        return number_format(bytes / 1073741824, 2) + ' GB';
    } elseif (bytes >= 1048576) {
        return number_format(bytes / 1048576, 2) + ' MB';
    } elseif (bytes >= 1024) {
        return number_format(bytes / 1024, 2) + ' KB';
    } else {
        return bytes + ' bytes';
    }
}

function getDatabaseSize() {
    // This would calculate actual database size
    return '~10 MB (estimated)';
}
</script>

<?php include "../includes/footer.php";

// Helper function to format file sizes
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

function getDatabaseSize() {
    global $conn;
    $result = $conn->query("
        SELECT
            ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
    ");

    if ($result) {
        $row = $result->fetch_assoc();
        return $row['size_mb'] ? $row['size_mb'] . ' MB' : 'Unknown';
    }

    return 'Unknown';
}
?>
