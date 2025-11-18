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

// Function to get the next user ID for display purposes
function getNextUserIDForDisplay($conn) {
    $current_year = date('Y');
    $stmt = $conn->prepare("SELECT counter FROM user_id_counter WHERE year = ?");
    $stmt->bind_param("i", $current_year);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $next_counter = $row['counter'] + 1;
    } else {
        $next_counter = 1;
    }
    
    return $current_year . '-' . str_pad($next_counter, 4, '0', STR_PAD_LEFT);
}

$next_user_id = getNextUserIDForDisplay($conn);

// Retrieve and clear form data from session if available
$formData = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : [];
$currentStep = isset($_SESSION['current_step']) ? $_SESSION['current_step'] : 1;
$registrationErrors = isset($_SESSION['registration_errors']) ? $_SESSION['registration_errors'] : [];

unset($_SESSION['form_data']);
unset($_SESSION['current_step']);
unset($_SESSION['registration_errors']);
?>

<link rel="stylesheet" href="../CSS/registration.css">

<div class="main-content">
  <div class="container">
    <div class="form-header">
      <h1>Mindanao Institute</h1>
      <h2 class="subtitle">--REGISTRATION FORM--</h2>
    </div>
    <div class="error-container">
        <?php
        if (isset($_GET['error'])) {
            $error_message = htmlspecialchars($_GET['error']);
            echo "<p class='error'>$error_message</p>";
        }
        // Display general registration errors if any
        if (!empty($registrationErrors) && !isset($registrationErrors['email']) && !isset($registrationErrors['username'])) {
            echo "<p class='error'>Registration failed. Please check the form for errors.</p>";
        }
        ?>
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
        <div class="step-label">Account Info</div>
      </div>
      <div class="step" data-step="4">
        <div class="step-number">4</div>
        <div class="step-label">Security Questions</div>
      </div>
    </div>

    <form id="multiStepForm" method="POST" action="../PHP/register_action.php" novalidate>
        <input type="hidden" name="current_step" id="current_step" value="<?php echo $currentStep; ?>">
        <!-- Step 1: Personal Information -->
        <div class="form-step active" id="step-1">
            <h2>Personal Information</h2>

            <div class="form-row">
                <div class="form-group">
                    <label for="id_number">ID Number</label>
                    <input type="text" name="id_number" id="id_number" value="<?php echo htmlspecialchars($next_user_id); ?>" readonly>
                    <div class="error-message" id="id_number_error"></div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name <span class="required"></span></label>
                    <input type="text" name="first_name" id="first_name" placeholder="Ex.Juan" value="<?php echo htmlspecialchars($formData['first_name'] ?? ''); ?>" required>
                    <div class="error-message" id="first_name_error"></div>
                </div>
                <div class="form-group">
                    <label for="middle_name">Middle Name <span class="optional">optional</span></label>
                    <input type="text" name="middle_name" id="middle_name" placeholder="Ex.Reyes" value="<?php echo htmlspecialchars($formData['middle_name'] ?? ''); ?>">
                    <div class="error-message" id="middle_name_error"></div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="last_name">Last Name <span class="required"></span></label>
                    <input type="text" name="last_name" id="last_name" placeholder="Ex.Dela Cruz" value="<?php echo htmlspecialchars($formData['last_name'] ?? ''); ?>" required>
                    <div class="error-message" id="last_name_error"></div>
                </div>
                <div class="form-group">
                    <label for="extension">Suffix/Extension <span class="optional">optional</span></label>
                    <input type="text" name="extension" id="extension" placeholder="Jr., Sr., III, etc." value="<?php echo htmlspecialchars($formData['extension'] ?? ''); ?>">
                    <div class="error-message" id="extension_error"></div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="birthdate">Birthdate <span class="required"></span></label>
                    <input type="date" name="birthdate" id="birthdate" value="<?php echo htmlspecialchars($formData['birthdate'] ?? ''); ?>" required>
                    <div class="error-message" id="birthdate_error"></div>
                </div>
                <div class="form-group">
                    <label for="age">Age <span class="required"></span></label>
                    <input type="number" name="age" id="age" required readonly>
                    <div class="error-message" id="age_error"></div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="sex">Sex <span class="required"></span></label>
                    <select name="sex" id="sex" required>
                        <option value="">Select Sex</option>
                        <option value="Male" <?php echo (isset($formData['sex']) && $formData['sex'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo (isset($formData['sex']) && $formData['sex'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                        
                    </select>
                    <div class="error-message" id="sex_error"></div>
                </div>
            </div>

            <div class="button-row button-row-single-right">
                <button type="button" class="next-btn" onclick="nextStep(1)">Next</button>
            </div>
        </div>

        <!-- Step 2: Address Information -->
        <div class="form-step" id="step-2">
            <h2>Address Information</h2>

            <div class="form-row">
                <div class="form-group">
                    <label for="street">Purok/Street <span class="required"></span></label>
                    <input type="text" name="street" id="street" placeholder="Ex.C.Curato st." value="<?php echo htmlspecialchars($formData['street'] ?? ''); ?>" required>
                    <div class="error-message" id="street_error"></div>
                </div>
                <div class="form-group">
                    <label for="barangay">Barangay <span class="required"></span></label>
                    <input type="text" name="barangay" id="barangay" placeholder="Ex.Barangay Uno" value="<?php echo htmlspecialchars($formData['barangay'] ?? ''); ?>" required>
                    <div class="error-message" id="barangay_error"></div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="municipal">Municipal/City <span class="required"></span></label>
                    <input type="text" name="municipal" id="municipal" placeholder="Ex.Cabadbaran City" value="<?php echo htmlspecialchars($formData['municipal'] ?? ''); ?>" required>
                    <div class="error-message" id="municipal_error"></div>
                </div>
                <div class="form-group">
                    <label for="province">Province <span class="required"></span></label>
                    <input type="text" name="province" id="province" placeholder="Ex.Agusan del Norte" value="<?php echo htmlspecialchars($formData['province'] ?? ''); ?>" required>
                    <div class="error-message" id="province_error"></div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="country">Country <span class="required"></span></label>
                    <input type="text" name="country" id="country" value="<?php echo htmlspecialchars($formData['country'] ?? 'Philippines'); ?>"  required>
                    <div class="error-message" id="country_error"></div>
                </div>
                <div class="form-group">
                    <label for="zipcode">Zip Code <span class="required"></span></label>
                    <input type="text" name="zipcode" id="zipcode" placeholder="Ex.0958" value="<?php echo htmlspecialchars($formData['zipcode'] ?? ''); ?>" required pattern="[0-9]{4}">
                    <div class="error-message" id="zipcode_error"></div>
                </div>
            </div>

            <div class="button-row">
                <button type="button" class="prev-btn" onclick="prevStep(2)">Previous</button>
                <button type="button" class="next-btn" onclick="nextStep(2)">Next</button>
            </div>
        </div>

        <!-- Step 3: Account Information -->
        <div class="form-step" id="step-3">
            <h2>Account Information</h2>

            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email <span class="required"></span></label>
                    <input type="email" name="email" id="email" placeholder="Ex.Example@gmail.com" value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>" required>
                    <div class="error-message" id="email_error">
                        <?php echo htmlspecialchars($registrationErrors['email'] ?? ''); ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="username">Username <span class="required"></span></label>
                    <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($formData['username'] ?? ''); ?>" required>
                    <div class="error-message" id="username_error">
                        <?php echo htmlspecialchars($registrationErrors['username'] ?? ''); ?>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password <span class="required"></span></label>
                    <div class="password-container">
                        <input type="password" name="password" id="password" required>
                        <button type="button" class="show-password" onclick="togglePassword('password')">👁️</button>
                    </div>
                    <div class="password-strength" id="password_strength"></div>
                    <div class="error-message" id="password_error"></div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Re-enter Password <span class="required"></span></label>
                    <div class="password-container">
                        <input type="password" name="confirm_password" id="confirm_password" required>
                        <button type="button" class="show-password" onclick="togglePassword('confirm_password')">👁️</button>
                    </div>
                    <div class="error-message" id="confirm_password_error"></div>
                </div>
            </div>

            <div class="button-row">
                <button type="button" class="prev-btn" onclick="prevStep(3)">Previous</button>
                <button type="button" class="next-btn" onclick="nextStep(3)">Next</button>
            </div>
        </div>

        <!-- Step 4: Security Questions -->
        <div class="form-step" id="step-4">
            <h2>Security Questions</h2>
            <p>Please choose and answer 3 of the following security questions.</p>
            
            <?php
            $security_questions = [
                "Who is your best friend in Elementary?",
                "What is the name of your favorite pet?",
                "Who is your favorite teacher in high school?",
                "What is your favorite color?",
                "What is your favorite fruit?",
                "In what city were you born?"
            ];

            for ($i = 1; $i <= 3; $i++) {
            ?>
            <div class="form-group">
                <label for="security_question_<?php echo $i; ?>">Security Question <?php echo $i; ?> <span class="required"></span></label>
                <select name="security_question[]" id="security_question_<?php echo $i; ?>" required>
                    <option value="">-- Select a Question --</option>
                    <?php foreach ($security_questions as $question) { ?>
                        <option value="<?php echo htmlspecialchars($question); ?>"><?php echo htmlspecialchars($question); ?></option>
                    <?php } ?>
                </select>
                <div class="password-container">
                    <input type="password" name="security_answer[]" id="security_answer_<?php echo $i; ?>" placeholder="Answer" required>
                    <button type="button" class="show-password" onclick="togglePassword('security_answer_<?php echo $i; ?>')">👁️</button>
                </div>
                <div class="error-message" id="security_question_<?php echo $i; ?>_error"></div>
            </div>
            <?php } ?>

            <div class="button-row">
                <button type="button" class="prev-btn" onclick="prevStep(4)">Previous</button>
                <button type="submit" class="submit-btn" id="submitBtn">Register</button>
            </div>
        </div>
    </form>
  </div>

  <script src="../JS/registration.js"></script>
<script>
    document.addEventListener('contextmenu', event => event.preventDefault());
    document.addEventListener('keydown', event => {
        if (event.keyCode == 123 || (event.ctrlKey && event.shiftKey && event.keyCode == 73) || (event.ctrlKey && event.shiftKey && event.keyCode == 74) || (event.ctrlKey && event.keyCode == 85)) {
            event.preventDefault();
        }
    });
</script>
</div>
