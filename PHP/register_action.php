<?php
session_start();
include("../includes/db.php");

// ✅ Toggle for debugging
$DEBUG = true;

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit("Method Not Allowed");
}

$response = ['success' => false, 'message' => '', 'errors' => []];



try {
    // Sanitize and validate input data
    $data = sanitizeInput($_POST);
    
    // Validate data
    $validation = validateRegistrationData($data);
    if (!$validation['valid']) {
        $_SESSION['form_data'] = $data; // Store all submitted data
        $_SESSION['current_step'] = isset($_POST['current_step']) ? (int)$_POST['current_step'] : 1; // Store current step
        $error_message = reset($validation['errors']); // Get the first error message
        header("Location: ../Project/registration.php?error=" . urlencode($error_message));
        exit;
    }

    // ✅ Check for duplicates (username & email only)
    $checks = [
        ['field' => 'username', 'label' => 'Username'],
        ['field' => 'email', 'label' => 'Email']
    ];

    foreach ($checks as $check) {
        if (!in_array($check['field'], ['username', 'email'])) {
            throw new Exception("Invalid field specified for duplicate check.");
        }
        $query = "SELECT id FROM users WHERE {$check['field']} = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $data[$check['field']]);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $_SESSION['form_data'] = $data; // Store all submitted data
            $_SESSION['current_step'] = isset($_POST['current_step']) ? (int)$_POST['current_step'] : 1; // Store current step
            $_SESSION['registration_errors'][$check['field']] = "{$check['label']} already exists.";
            header("Location: ../Project/registration.php");
            exit;
        }
    }

    // ✅ Hash sensitive data
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
    $hashed_answers = [];
    foreach ($data['security_answer'] as $answer) {
        $hashed_answers[] = password_hash(strtolower(trim($answer)), PASSWORD_DEFAULT);
    }

    // ✅ Role default
    $role = 'student';

    // Generate new user ID with retry logic
    $year = date('Y');
    $new_user_id = '';
    $max_retries = 10; // Prevent infinite loops in extreme cases

    $conn->begin_transaction(); // Start transaction

    try {
        for ($i = 0; $i < $max_retries; $i++) {
            $conn->query("LOCK TABLES user_id_counter WRITE"); // Lock the counter table

            $result = $conn->query("SELECT counter FROM user_id_counter WHERE year = $year");
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $counter = $row['counter'] + 1;
                $conn->query("UPDATE user_id_counter SET counter = $counter WHERE year = $year");
            } else {
                $counter = 1;
                $conn->query("INSERT INTO user_id_counter (year, counter) VALUES ($year, $counter)");
            }
            $conn->query("UNLOCK TABLES"); // Unlock the counter table

            $generated_id = sprintf('%d-%04d', $year, $counter);

            // Check if this ID already exists in the users table
            $check_stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
            $check_stmt->bind_param("s", $generated_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows === 0) {
                $new_user_id = $generated_id;
                break; // Unique ID found
            }
            // If ID exists, loop again to generate a new one
        }

        if (empty($new_user_id)) {
            throw new Exception("Failed to generate a unique user ID after multiple retries.");
        }

    // ✅ SQL insert aligned exactly with your database columns
    $stmt = $conn->prepare("
        INSERT INTO users (
            id, first_name, middle_name, last_name, suffix, birthdate, age, sex,
            street, barangay, municipal, province, country, zipcode,
            email, username, password, role,
            security_question_1, answer_1, security_question_2, answer_2, security_question_3, answer_3,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    if (!$stmt) {
        throw new Exception("SQL prepare failed: " . $conn->error);
    }

    // ✅ Assign array values to variables (avoids “Only variables can be passed by reference”)
    $first_name = $data['first_name'];
    $middle_name = $data['middle_name'];
    $last_name = $data['last_name'];
    
    $extension = formatSuffix($data['extension']);
    $birthdate = $data['birthdate'];
    $age = $data['age'];
    $sex = $data['sex'];
    $street = $data['street'];
    $barangay = $data['barangay'];
    $municipal = $data['municipal'];
    $province = $data['province'];
    $country = $data['country'];
    $zipcode = $data['zipcode'];
    $email = $data['email'];
    $username = $data['username'];

    // ✅ Fixed bind_param order
    $stmt->bind_param(
        "ssssssisssssssssssssssss",
        $new_user_id, $first_name, $middle_name, $last_name,
        $extension, $birthdate, $age, $sex,
        $street, $barangay, $municipal, $province,
        $country, $zipcode, $email, $username,
        $hashedPassword, $role,
        $data['security_question'][0], $hashed_answers[0],
        $data['security_question'][1], $hashed_answers[1],
        $data['security_question'][2], $hashed_answers[2]
    );

    if ($stmt->execute()) {
        $conn->commit(); // Commit transaction on success
        // Removed automatic login
        header("Location: ../Project/index.php?registration_success=true");
        exit;
    } else {
        throw new Exception("Execution failed: " . $stmt->error);
    }
    } catch (Exception $e) { // This catch is for the inner transaction block
        $conn->rollback(); // Rollback on error
        throw $e; // Re-throw the exception
    }

} catch (Exception $e) { // This is the main catch block for any exceptions
    error_log("Registration error: " . $e->getMessage());
    header("Location: ../Project/registration.php?error=" . urlencode("Registration failed: " . $e->getMessage()));
    exit;
}



// ------------------------------------------------
// HELPER FUNCTIONS
// ------------------------------------------------
function sanitizeInput($data) {
    $fields = [
        'first_name', 'middle_name', 'last_name', 'extension', 'birthdate',
        'age', 'sex', 'street', 'barangay', 'municipal', 'province', 'country', 'zipcode',
        'email', 'username', 'password', 'confirm_password'
    ];

    $clean = [];
    foreach ($fields as $f) {
        $value = isset($data[$f]) ? trim($data[$f]) : '';
        if (in_array($f, ['first_name', 'middle_name', 'last_name', 'street', 'barangay', 'municipal', 'province', 'country'])) {
            $value = preg_replace('/\s+/', ' ', $value);
        }
        $clean[$f] = $value;
    }
    $clean['age'] = intval($clean['age']);
    if (empty($clean['country'])) $clean['country'] = 'Philippines';

    // Sanitize security questions and answers
    if (isset($data['security_question']) && is_array($data['security_question'])) {
        $clean['security_question'] = array_map('trim', $data['security_question']);
    }
    if (isset($data['security_answer']) && is_array($data['security_answer'])) {
        $clean['security_answer'] = array_map('trim', $data['security_answer']);
    }

    return $clean;
}

function formatSuffix($suffix) {
    $suffix = trim($suffix);
    if (empty($suffix)) {
        return '';
    }

    // Convert to uppercase first
    $formattedSuffix = strtoupper($suffix);

    // Specific exceptions for Jr. and Sr.
    if ($formattedSuffix === 'JR.' || $formattedSuffix === 'JR') {
        return 'Jr.';
    }
    if ($formattedSuffix === 'SR.' || $formattedSuffix === 'SR') {
        return 'Sr.';
    }

    // For Roman numerals, ensure they are uppercase.
    // This regex checks for common Roman numeral patterns.
    if (preg_match('/^M{0,4}(CM|CD|D?C{0,3})(XC|XL|L?X{0,3})(IX|IV|V?I{0,3})$/', $formattedSuffix)) {
        return $formattedSuffix;
    }

    // Default to original uppercase if not a special case or Roman numeral
    return $formattedSuffix;
}

function validateRegistrationData($data) {
    $errors = [];

    $required = [
        'first_name', 'last_name', 'birthdate', 'age', 'sex',
        'street', 'barangay', 'municipal', 'province', 'country', 'zipcode',
        'email', 'username', 'password', 'confirm_password'
    ];

    foreach ($required as $field) {
        if (empty($data[$field])) {
            $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
        }
    }

    if (!empty($data['extension']) && !preg_match('/^[a-zA-Z.]*$/', $data['extension'])) {
        $errors['extension'] = 'Suffix can only contain letters and periods.';
    }

    if (!empty($data['age']) && ($data['age'] < 18 || $data['age'] > 120)) {
        $errors['age'] = 'Age must be between 18 and 120.';
    }

    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL))
        $errors['email'] = 'Invalid email format.';

    if (strlen($data['password']) < 8)
        $errors['password'] = 'Password must be at least 8 characters.';

    if ($data['password'] !== $data['confirm_password'])
        $errors['confirm_password'] = 'Passwords do not match.';

    // Validate security questions
    if (!isset($data['security_question']) || !is_array($data['security_question']) || count($data['security_question']) !== 3) {
        $errors['security_questions'] = 'Please select exactly 3 security questions.';
    } else {
        $unique_questions = array_unique($data['security_question']);
        if (count($unique_questions) !== 3) {
            $errors['security_questions'] = 'Please select 3 unique security questions.';
        }
    }

    if (!isset($data['security_answer']) || !is_array($data['security_answer']) || count($data['security_answer']) !== 3) {
        $errors['security_answers'] = 'Please answer all 3 security questions.';
    } else {
        foreach ($data['security_answer'] as $answer) {
            if (empty($answer)) {
                $errors['security_answers'] = 'Please answer all 3 security questions.';
                break;
            }
        }
    }

    return ['valid' => empty($errors), 'errors' => $errors];
}
?>
