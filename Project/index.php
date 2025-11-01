<?php
include "../includes/header.php"; // this already starts the session safely
include("../includes/db.php");

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error_message = '';
$lockout_time = 0;
$show_forgot_password = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $ip_address = $_SERVER['REMOTE_ADDR'];

    // Validate credentials first
    if (empty($username) || empty($password)) {
        $error_message = "Please enter both username and password.";
        logLoginAttempt($conn, $username, $ip_address, false);
    } else {
        // Check user credentials
        $stmt = $conn->prepare("SELECT id, username, password, first_name, last_name, is_active FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (!$user['is_active']) {
                $error_message = "Your account has been deactivated. Please contact administrator.";
                logLoginAttempt($conn, $username, $ip_address, false);
            } elseif (password_verify($password, $user['password'])) {
                // Successful login
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['login_time'] = time();

                // Log successful attempt
                logLoginAttempt($conn, $username, $ip_address, true);

                // Create session record
                $session_id = session_id();
                $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
                $stmt = $conn->prepare("INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isss", $user['id'], $session_id, $ip_address, $user_agent);
                $stmt->execute();

                header("Location: dashboard.php");
                exit;
            } else {
                $error_message = "Invalid username or password.";
                logLoginAttempt($conn, $username, $ip_address, false);
            }
        } else {
            $error_message = "Invalid username or password.";
            logLoginAttempt($conn, $username, $ip_address, false);
        }
    }

    // Check failed attempts AFTER logging the current attempt
    // Only count attempts from the last 5 minutes to avoid old data
    $current_failed_attempts = getFailedAttempts($conn, $username, $ip_address, 5);

    // Check if account should be locked (only after 3+ failed attempts)
    if ($current_failed_attempts >= 3) {
        $lockout_time = getLockoutTime($conn, $username, $ip_address);
        if ($lockout_time > 0) {
            $error_message = "Access denied. Please wait {$lockout_time} seconds before trying again.";
        }
    }

    // Check if we should show forgot password link (after 2 consecutive errors)
    $failed_attempts_15min = getFailedAttempts($conn, $username, $ip_address, 15);
    if ($failed_attempts_15min >= 2 && $lockout_time == 0) {
        $show_forgot_password = true;
    }
}
?>

<!-- Hero Section -->
<div class="hero">
  <div class="hero-left">
    <h1>Welcome to <br><span>Mindanao Institute</span></h1>
    <p>Nurturing Knowledge, Cultivating Character, and Building Leaders for Tomorrow</p>
    <button id="getStartedBtn" type="button" onclick="window.location.href='registration.php'">Get Started</button>

    <!-- 3 cards -->
    <div class="cards">
      <div class="card"><div class="icon">🎓</div><p>Building minds with knowledge, integrity, and innovation.</p></div>
      <div class="card"><div class="icon">🏃</div><p>Shaping discipline, teamwork, and resilience through play.</p></div>
      <div class="card"><div class="icon">🎭</div><p>Inspiring creativity, culture, and self-expression.</p></div>
    </div>
  </div>

  <!-- Login Box -->
  <div class="login-container">
    <div class="login-header">
      <h2>Login to Your Account</h2>
    </div>

    <?php if ($error_message): ?>
      <div class="error-message">
        <?php echo htmlspecialchars($error_message); ?>
      </div>
    <?php endif; ?>

    <?php if ($lockout_time > 0): ?>
      <div class="lockout-timer" id="lockoutTimer" data-time="<?php echo $lockout_time; ?>">
        Account locked for: <span id="timeRemaining"><?php echo $lockout_time; ?></span> seconds
      </div>
    <?php endif; ?>

    <form method="POST" action="index.php" id="loginForm">
      <div class="form-group">
        <label for="username">Username or Email <span class="required">*</span></label>
        <input type="text" name="username" id="username" required
               value="<?php echo htmlspecialchars($username ?? ''); ?>"
               <?php echo $lockout_time > 0 ? 'disabled' : ''; ?>>
      </div>

      <div class="form-group">
        <label for="password">Password <span class="required">*</span></label>
        <input type="password" name="password" id="password" required
               <?php echo $lockout_time > 0 ? 'disabled' : ''; ?>>
      </div>

      <button type="submit" class="login-btn" id="loginBtn"
              <?php echo $lockout_time > 0 ? 'disabled' : ''; ?>>
        Login
      </button>
    </form>

    <?php if ($show_forgot_password): ?>
      <div class="forgot-password">
        <a href="forgot_password.php">Forgot Password? Reset Here</a>
      </div>
    <?php endif; ?>

    <div class="register-link">
      <p>Don't have an account? <a href="registration.php" id="registerLink"
         <?php echo $lockout_time > 0 ? 'style="pointer-events: none; color: #ccc;"' : ''; ?>>
         Please register here</a></p>
    </div>
  </div>
</div>

<?php include "../includes/footer.php"; ?>
<script src="../JS/login.js"></script>
