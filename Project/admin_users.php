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

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = intval($_POST['user_id'] ?? 0);

    if ($user_id && in_array($action, ['activate', 'deactivate'])) {
        $is_active = ($action === 'activate') ? 1 : 0;
        $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        $stmt->bind_param("ii", $is_active, $user_id);
        $stmt->execute();

        logSecurityEvent($conn, 'User_Status_Changed', "User {$action}d by admin", getClientIP(), $_SESSION['user_id']);
        $success_message = "User {$action}d successfully";
    }
}

// Get users with search and filter
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? 'all';

$where_conditions = ["role = 'student'"];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

if ($status_filter !== 'all') {
    $where_conditions[] = "is_active = ?";
    $params[] = ($status_filter === 'active') ? 1 : 0;
    $types .= 'i';
}

$where_clause = implode(' AND ', $where_conditions);

// Get users
$query = "
    SELECT id, first_name, last_name, email, username, is_active, created_at
    FROM users
    WHERE $where_clause
    ORDER BY created_at DESC
";

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $users = $stmt->get_result();
} else {
    $users = $conn->query($query);
}

include("../includes/header.php");
?>

<link rel="stylesheet" href="../CSS/dashboard.css">

<div class="main-content">
    <div class="admin-container">
        <div class="admin-header">
            <h1>User Management</h1>
            <p>Manage user accounts and permissions</p>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <!-- Search and Filter -->
        <div class="controls-section">
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Search users..."
                       value="<?php echo htmlspecialchars($search); ?>">
                <select name="status">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Users</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active Only</option>
                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive Only</option>
                </select>
                <button type="submit">Filter</button>
            </form>
        </div>

        <!-- Users Table -->
        <div class="users-section">
            <h2>Users (<?php echo $users->num_rows; ?> found)</h2>

            <table class="users-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="status-badge <?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <?php if ($user['is_active']): ?>
                                    <button onclick="manageUser(<?php echo $user['id']; ?>, 'deactivate')" class="btn btn-warning">Deactivate</button>
                                <?php else: ?>
                                    <button onclick="manageUser(<?php echo $user['id']; ?>, 'activate')" class="btn btn-success">Activate</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
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
    margin-bottom: 30px;
    padding: 30px 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 10px;
}

.success-message {
    background: #d4edda;
    color: #155724;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.controls-section {
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.search-form {
    display: flex;
    gap: 15px;
    align-items: center;
}

.search-form input,
.search-form select {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.search-form button {
    padding: 10px 20px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.users-section {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.users-section h2 {
    margin-bottom: 20px;
    color: #333;
}

.users-table {
    width: 100%;
    border-collapse: collapse;
}

.users-table th,
.users-table td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.users-table th {
    background: #f8f9fa;
    font-weight: 600;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.status-badge.active {
    background: #d4edda;
    color: #155724;
}

.status-badge.inactive {
    background: #f8d7da;
    color: #721c24;
}

.btn {
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
}

.btn-success {
    background: #28a745;
    color: white;
}

.btn-warning {
    background: #ffc107;
    color: #212529;
}
</style>

<script>
function manageUser(userId, action) {
    if (confirm(`Are you sure you want to ${action} this user?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="user_id" value="${userId}">
            <input type="hidden" name="action" value="${action}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include "../includes/footer.php"; ?>
