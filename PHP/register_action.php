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
        $response['message'] = 'Validation failed.';
        $response['errors'] = $validation['errors'];
        echo json_encode($response);
        exit;
    }

    // ✅ Check for duplicates (username & email only)
    $checks = [
        ['field' => 'username', 'label' => 'Username'],
        ['field' => 'email', 'label' => 'Email']
    ];

    foreach ($checks as $check) {
        $query = "SELECT id FROM users WHERE {$check['field']} = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $data[$check['field']]);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $response['message'] = "{$check['label']} already exists.";
            echo json_encode($response);
            exit;
        }
    }

    // ✅ Hash sensitive data
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
    $answer1 = password_hash(strtolower(trim($data['answer1'])), PASSWORD_DEFAULT);
    $answer2 = password_hash(strtolower(trim($data['answer2'])), PASSWORD_DEFAULT);
    $answer3 = password_hash(strtolower(trim($data['answer3'])), PASSWORD_DEFAULT);

    // ✅ Role default
    $role = 'student';

    // ✅ SQL insert aligned exactly with your database columns
    $stmt = $conn->prepare("
        INSERT INTO users (
            first_name, middle_name, last_name, extension, birthdate, age, sex,
            purok_street, barangay, municipal_city, province, country, zipcode,
            email, username, password, role,
            security1, answer1, security2, answer2, security3, answer3,
            created_at
        ) VALUES (?, ?, ?,  ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    if (!$stmt) {
        throw new Exception("SQL prepare failed: " . $conn->error);
    }

    // ✅ Assign array values to variables (avoids “Only variables can be passed by reference”)
    $first_name = $data['first_name'];
    $middle_name = $data['middle_name'];
    $last_name = $data['last_name'];
    
    $extension = $data['extension'];
    $birthdate = $data['birthdate'];
    $age = $data['age'];
    $sex = $data['sex'];
    $purok_street = $data['purok_street'];
    $barangay = $data['barangay'];
    $municipal_city = $data['municipal_city'];
    $province = $data['province'];
    $country = $data['country'];
    $zipcode = $data['zipcode'];
    $email = $data['email'];
    $username = $data['username'];
    $security1 = 'Who is your best friend in Elementary?';
    $security2 = 'What is the name of your favorite pet?';
    $security3 = 'Who is your favorite teacher in high school?';

    // ✅ Fixed bind_param order — exactly 24 parameters
    $stmt->bind_param(
        "ssssssissssssssssssssss",
        $first_name, $middle_name, $last_name,
        $extension, $birthdate, $age, $sex,
        $purok_street, $barangay, $municipal_city, $province,
        $country, $zipcode, $email, $username,
        $hashedPassword, $role,
        $security1, $answer1,
        $security2, $answer2,
        $security3, $answer3
    );

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Registration successful! Redirecting to login...';
    } else {
        throw new Exception("Execution failed: " . $stmt->error);
    }

} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    $response['message'] = 'An error occurred during registration.';
    if ($DEBUG) {
        $response['error_details'] = $e->getMessage();
    }
}

// ✅ Send JSON response
header('Content-Type: application/json');
echo json_encode($response);

// ------------------------------------------------
// HELPER FUNCTIONS
// ------------------------------------------------
function sanitizeInput($data) {
    $fields = [
        'first_name', 'middle_name', 'last_name', 'extension', 'birthdate',
        'age', 'sex', 'purok_street', 'barangay', 'municipal_city', 'province', 'country', 'zipcode',
        'email', 'username', 'password', 'confirm_password',
        'answer1', 'answer2', 'answer3'
    ];

    $clean = [];
    foreach ($fields as $f) {
        $clean[$f] = isset($data[$f]) ? trim($data[$f]) : '';
    }
    $clean['age'] = intval($clean['age']);
    if (empty($clean['country'])) $clean['country'] = 'Philippines';
    return $clean;
}

function validateRegistrationData($data) {
    $errors = [];

    $required = [
        'first_name', 'last_name', 'birthdate', 'age', 'sex',
        'purok_street', 'barangay', 'municipal_city', 'province', 'country', 'zipcode',
        'email', 'username', 'password', 'answer1', 'answer2', 'answer3'
    ];

    foreach ($required as $field) {
        if (empty($data[$field])) {
            $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
        }
    }

    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL))
        $errors['email'] = 'Invalid email format.';

    if (strlen($data['password']) < 8)
        $errors['password'] = 'Password must be at least 8 characters.';

    if ($data['password'] !== $data['confirm_password'])
        $errors['confirm_password'] = 'Passwords do not match.';

    return ['valid' => empty($errors), 'errors' => $errors];
}
?>
