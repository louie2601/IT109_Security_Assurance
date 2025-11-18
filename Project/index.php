<?php
// Prevent caching of this page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // A date in the past

include "../includes/header.php"; // this already starts the session safely
include("../includes/db.php");

// If already logged in (e.g., user navigates back), redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Handle AJAX login request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $ip_address = $_SERVER['REMOTE_ADDR'];

    if (empty($username) || empty($password)) {
        echo "Invalid username or password"; // Generic message for security
        exit;
    }

    $stmt = $conn->prepare("SELECT id, username, password, first_name, last_name, is_active FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if ($user['is_active'] && password_verify($password, $user['password'])) {
            // Successful login
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['login_time'] = time();

            // Create session record
            $session_id = session_id();
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $stmt = $conn->prepare("INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $user['id'], $session_id, $ip_address, $user_agent);
            $stmt->execute();

            echo "dashboard.php"; // Send success indicator
            exit;
        }
    }

    // If anything fails (user not found, inactive, wrong password), send failure message
    echo "Invalid username or password";
    exit;
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

    <form method="POST" action="index.php" id="loginForm" novalidate>
      <div class="form-group">
        <label for="username">Username<span class="required"></span></label>
        <input type="text" name="username" id="username"
               value="<?php echo htmlspecialchars($username ?? ''); ?>">
        <div class="error-message" id="username_error"></div>
      </div>

      <div class="form-group">
        <label for="password">Password <span class="required"></span></label>
        <div class="password-input-container">
          <input type="password" name="password" id="password">
          
        </div>
        <div class="error-message" id="password_error"></div>
      </div>

      <button type="submit" class="login-btn" id="loginBtn">
        Login
      </button>
    </form>

      <div class="forgot-password">
        <a href="forgot_password.php">Forgot Password? Reset<br>Here</a>
      </div>

    <div class="register-link">
      <p>Don't have an account? <a href="registration.php" id="registerLink">
         Please register here</a></p>
    </div>
  </div>
</div>

<?php include "../includes/footer.php"; ?>
<script>
    document.addEventListener('contextmenu', event => event.preventDefault());
    document.addEventListener('keydown', event => {
        if (event.keyCode == 123 || (event.ctrlKey && event.shiftKey && event.keyCode == 73) || (event.ctrlKey && event.shiftKey && event.keyCode == 74) || (event.ctrlKey && event.keyCode == 85)) {
            event.preventDefault();
        }
    });
</script>
<script src="../JS/login.js"></script>
<script src="../JS/common.js"></script>
