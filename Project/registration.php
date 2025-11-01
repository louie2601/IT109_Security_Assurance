<?php
// Start session but don't require login for registration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

include("../includes/header.php");
include("../includes/db.php");
?>

<link rel="stylesheet" href="../CSS/registration.css">

<div class="main-content">
  <div class="container">
    <div class="form-header">
      <h1>Mindanao Institute</h1>
      <h2 class="subtitle">--REGISTRATION FORM--</h2>
    </div>

    <!-- Progress Indicator -->
    <div class="step-indicator">
      <div class="step active" data-step="1">
        <div class="step-number">1</div>
        <div class="step-label">Personal Info</div>
      </div>
      <div class="step" data-step="2">
        <div class="step-number">2</div>
        <div class="step-label">Address Info</div>
      </div>
      <div class="step" data-step="3">
        <div class="step-number">3</div>
        <div class="step-label">Security Questions</div>
      </div>
      <div class="step" data-step="4">
        <div class="step-number">4</div>
        <div class="step-label">Account Info</div>
      </div>
    </div>

    <form id="multiStepForm" method="POST" action="../PHP/register_action.php" novalidate>
        <!-- Step 1: Personal Information -->
        <div class="form-step active" id="step-1">
            <h2>Personal Information</h2>

            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name <span class="required">*</span></label>
                    <input type="text" name="first_name" id="first_name" required>
                    <div class="error-message" id="first_name_error"></div>
                </div>
                <div class="form-group">
                    <label for="middle_name">Middle Name <span class="optional">optional</span></label>
                    <input type="text" name="middle_name" id="middle_name">
                    <div class="error-message" id="middle_name_error"></div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="last_name">Last Name <span class="required">*</span></label>
                    <input type="text" name="last_name" id="last_name" required>
                    <div class="error-message" id="last_name_error"></div>
                </div>
                <div class="form-group">
                    <label for="extension">Suffix/Extension <span class="optional">optional</span></label>
                    <input type="text" name="extension" id="extension" placeholder="Jr., Sr., III, etc.">
                    <div class="error-message" id="extension_error"></div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="birthdate">Birthdate <span class="required">*</span></label>
                    <input type="date" name="birthdate" id="birthdate" required>
                    <div class="error-message" id="birthdate_error"></div>
                </div>
                <div class="form-group">
                    <label for="age">Age <span class="required">*</span></label>
                    <input type="number" name="age" id="age" readonly>
                    <div class="error-message" id="age_error"></div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="sex">Sex <span class="required">*</span></label>
                    <select name="sex" id="sex" required>
                        <option value="">Select Sex</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                    <div class="error-message" id="sex_error"></div>
                </div>
            </div>

            <div class="button-row">
                <button type="button" class="next-btn" onclick="nextStep(1)">Next</button>
            </div>
        </div>

        <!-- Step 2: Address Information -->
        <div class="form-step" id="step-2">
            <h2>Address Information</h2>

            <div class="form-row">
                <div class="form-group">
                    <label for="purok_street">Purok/Street <span class="required">*</span></label>
                    <input type="text" name="purok_street" id="purok_street" required>
                    <div class="error-message" id="purok_street_error"></div>
                </div>
                <div class="form-group">
                    <label for="barangay">Barangay <span class="required">*</span></label>
                    <input type="text" name="barangay" id="barangay" required>
                    <div class="error-message" id="barangay_error"></div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="municipal_city">Municipal/City <span class="required">*</span></label>
                    <input type="text" name="municipal_city" id="municipal_city" required>
                    <div class="error-message" id="municipal_city_error"></div>
                </div>
                <div class="form-group">
                    <label for="province">Province <span class="required">*</span></label>
                    <input type="text" name="province" id="province" required>
                    <div class="error-message" id="province_error"></div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="country">Country <span class="required">*</span></label>
                    <input type="text" name="country" id="country" value="Philippines" required>
                    <div class="error-message" id="country_error"></div>
                </div>
                <div class="form-group">
                    <label for="zipcode">Zip Code <span class="required">*</span></label>
                    <input type="text" name="zipcode" id="zipcode" required pattern="[0-9]{4,6}">
                    <div class="error-message" id="zipcode_error"></div>
                </div>
            </div>

            <div class="button-row">
                <button type="button" class="prev-btn" onclick="prevStep(2)">Previous</button>
                <button type="button" class="next-btn" onclick="nextStep(2)">Next</button>
            </div>
        </div>

        <!-- Step 3: Security Questions -->
        <div class="form-step" id="step-3">
            <h2>Security Questions</h2>
            <div class="form-group">
                <label>Who is your best friend in Elementary?</label>
                <input type="text" name="answer1" required>
            </div>
            <div class="form-group">
                <label>What is the name of your favorite pet?</label>
                <input type="text" name="answer2" required>
            </div>
            <div class="form-group">
                <label>Who is your favorite teacher in high school?</label>
                <input type="text" name="answer3" required>
            </div>
            <div class="button-row">
                <button type="button" class="prev-btn" onclick="prevStep(3)">Previous</button>
                <button type="button" class="next-btn" onclick="nextStep(3)">Next</button>
            </div>
        </div>

        <!-- Step 4: Account Information -->
        <div class="form-step" id="step-4">
            <h2>Account Information</h2>

            <div class="form-group">
                <label for="email">Email <span class="required">*</span></label>
                <input type="email" name="email" id="email" required>
                <div class="error-message" id="email_error"></div>
            </div>

            <div class="form-group">
                <label for="username">Username <span class="required">*</span></label>
                <input type="text" name="username" id="username" required>
                <div class="error-message" id="username_error"></div>
            </div>

            <div class="form-group">
                <label for="password">Password <span class="required">*</span></label>
                <div class="password-container">
                    <input type="password" name="password" id="password" required>
                    <button type="button" class="show-password" onclick="togglePassword('password')">👁️</button>
                </div>
                <div class="password-strength" id="password_strength"></div>
                <div class="error-message" id="password_error"></div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Re-enter Password <span class="required">*</span></label>
                <div class="password-container">
                    <input type="password" name="confirm_password" id="confirm_password" required>
                    <button type="button" class="show-password" onclick="togglePassword('confirm_password')">👁️</button>
                </div>
                <div class="error-message" id="confirm_password_error"></div>
            </div>

            <div class="button-row">
                <button type="button" class="prev-btn" onclick="prevStep(4)">Previous</button>
                <button type="submit" class="submit-btn" id="submitBtn">Register</button>
            </div>
        </div>
    </form>
  </div>

  <script src="../JS/registration.js"></script>

</div>
