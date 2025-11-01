<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Determine current page for navigation logic
$current_page = basename($_SERVER['PHP_SELF']);
$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mindanao Institute Security Assurance System</title>
  <link rel="stylesheet" href="../CSS/style.css">
  <style>
    /* === Navbar Layout === */
    .navbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 10px 40px;
      background-color: #fff;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    /* === Logo and Institute Name Section === */
    .logo-section {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .logo img {
      height: 50px;
      width: 50px;
      object-fit: contain;
    }

    .institute-text {
      display: flex;
      flex-direction: column;
      line-height: 1.2;
    }

    .institute-name {
      font-size: 40px;
      font-weight: 900;
      color: #6e1d1d;
      white-space: nowrap;
    }

    .institute-subtitle {
      font-size: 13px;
      font-weight: 600;
      color: #444;
      white-space: nowrap;
    }

    /* === Menu Styling === */
    .navbar .menu ul {
      display: flex;
      gap: 20px;
      align-items: center;
      margin: 0;
      padding: 0;
    }

    .navbar .menu ul li {
      list-style: none;
    }

    .navbar .menu ul li a {
      text-decoration: none;
      color: #6e1d1d;
      font-size: 16px;
      font-weight: 600;
      padding: 8px 15px;
      border-radius: 4px;
      transition: all 0.3s ease;
    }

    .navbar .menu ul li a:hover {
      background-color: #f0f0f0;
      color: #d4af37;
    }
  </style>
</head>
<body>
  <!-- Navbar -->
  <div class="navbar">
    <div class="logo-section">
      <div class="logo">
        <img src="../IMAGES/logo.png" alt="Mindanao Institute Logo">
      </div>
      <div class="institute-text">
        <div class="institute-name">Mindanao Institute</div>
        <div class="institute-subtitle">Enrollment and Payment Management System</div>
      </div>
    </div>

    <div class="menu">
      <ul>
        <li><a href="index.php">Home</a></li>

        <?php if ($is_logged_in): ?>
          <!-- Logged in user navigation -->
          <li><a href="dashboard.php">Dashboard</a></li>
          <li><a href="profile.php">Profile</a></li>
          <li><a href="logout.php">Log-out</a></li>
        <?php else: ?>
          <!-- Not logged in navigation -->
          <?php if ($current_page === 'index.php'): ?>
            <li><a href="registration.php">Register</a></li>
          <?php elseif ($current_page === 'registration.php' || $current_page === 'register.php'): ?>
            <!-- “Back to Home” removed -->
          <?php else: ?>
            <!-- Other pages, no extra links -->
          <?php endif; ?>
        <?php endif; ?>
      </ul>
    </div>
  </div>
