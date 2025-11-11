<?php
use PHPUnit\Framework\TestCase;

class ForgotPasswordTest extends TestCase
{
    private $conn;
    private $testDbName;

    protected function setUp(): void
    {
        // Generate a unique database name for each test run
        $this->testDbName = 'test_sucurity_' . uniqid();

        // Establish a connection to the MySQL server
        $this->conn = new mysqli("localhost", "root", "");

        if ($this->conn->connect_error) {
            $this->fail("Failed to connect to MySQL: " . $this->conn->connect_error);
        }

        // Drop the test database if it exists from a previous failed run
        $this->conn->query("DROP DATABASE IF EXISTS " . $this->testDbName);

        // Create the test database
        if (!$this->conn->query("CREATE DATABASE " . $this->testDbName)) {
            $this->fail("Failed to create test database: " . $this->conn->error);
        }

        // Select the test database
        $this->conn->select_db($this->testDbName);

        // Read and execute the schema.sql file
        $sql_file = file_get_contents('database/schema.sql');
        if ($sql_file === false) {
            $this->fail("Failed to read schema.sql file.");
        }

        $queries = explode(';', $sql_file);
        foreach ($queries as $query) {
            $query = trim($query);
            if (!empty($query)) {
                if (!$this->conn->query($query)) {
                    $this->fail("Error executing query: " . $this->conn->error . " - " . substr($query, 0, 50) . "...");
                }
            }
        }

        // Disable foreign key checks
        if (!$this->conn->query("SET FOREIGN_KEY_CHECKS = 0")) {
            $this->fail("Failed to disable foreign key checks: " . $this->conn->error);
        }

        // Truncate all tables that might contain test data
        $tables = ['users', 'user_id_counter', 'login_attempts', 'password_reset_tokens', 'user_sessions', 'security_questions'];
        foreach ($tables as $table) {
            if (!$this->conn->query("TRUNCATE TABLE " . $table)) {
                $this->fail("Failed to truncate table " . $table . ": " . $this->conn->error);
            }
        }

        // Initialize user_id_counter for the current year
        $currentYear = date('Y');
        if (!$this->conn->query("INSERT INTO user_id_counter (year, counter) VALUES ($currentYear, 0) ON DUPLICATE KEY UPDATE counter = 0")) {
            $this->fail("Failed to initialize user_id_counter: " . $this->conn->error);
        }

        // Re-enable foreign key checks
        if (!$this->conn->query("SET FOREIGN_KEY_CHECKS = 1")) {
            $this->fail("Failed to re-enable foreign key checks: " . $this->conn->error);
        }

        // Insert test data
        $hashedPassword = password_hash('Password123', PASSWORD_DEFAULT);
        $hashedAnswer1 = password_hash(strtolower('Pedro'), PASSWORD_DEFAULT);
        $hashedAnswer2 = password_hash(strtolower('Brownie'), PASSWORD_DEFAULT);
        $hashedAnswer3 = password_hash(strtolower('Marian Reyes'), PASSWORD_DEFAULT);

        $stmt = $this->conn->prepare("
            INSERT INTO users (
                id, first_name, middle_name, last_name, suffix, birthdate, age, sex,
                street, barangay, municipal, province, country, zipcode,
                email, username, password, role,
                security_question_1, answer_1, security_question_2, answer_2, security_question_3, answer_3,
                security_question_4, answer_4, security_question_5, answer_5, security_question_6, answer_6,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        if (!$stmt) {
            $this->fail("Failed to prepare user insert statement: " . $this->conn->error);
        }

        $id = '2025-0001';
        $first_name = 'Test';
        $middle_name = 'M';
        $last_name = 'User';
        $suffix = '';
        $birthdate = '2000-01-01';
        $age = 23;
        $sex = 'Male';
        $street = 'Test Street';
        $barangay = 'Test Barangay';
        $municipal = 'Test Municipal';
        $province = 'Test Province';
        $country = 'Philippines';
        $zipcode = '1234';
        $email = 'test@example.com';
        $username = 'testuser';
        $role = 'student';
        $security1 = 'Who is your best friend in Elementary?';
        $security2 = 'What is the name of your favorite pet?';
        $security3 = 'Who is your favorite teacher in high school?';
        $security4 = 'What is your favorite color?';
        $security5 = 'What is your favorite fruit?';
        $security6 = 'In what city were you born?';

        $stmt->bind_param(
            "ssssssisssssssssssssssssssssss",
            $id, $first_name, $middle_name, $last_name,
            $suffix, $birthdate, $age, $sex,
            $street, $barangay, $municipal, $province,
            $country, $zipcode, $email, $username,
            $hashedPassword, $role,
            $security1, $hashedAnswer1,
            $security2, $hashedAnswer2,
            $security3, $hashedAnswer3,
            $security4, $hashedAnswer1, // Using same answer for simplicity
            $security5, $hashedAnswer2, // Using same answer for simplicity
            $security6, $hashedAnswer3  // Using same answer for simplicity
        );

        if (!$stmt->execute()) {
            $this->fail("Failed to insert test user: " . $stmt->error);
        }
        $stmt->close();

        // Mock session handling
        $_SESSION = [];
        
        // Set the global $conn variable for the included script
        global $conn;
        $conn = $this->conn;
    }

    protected function tearDown(): void
    {
        // Drop the test database
        if ($this->conn) {
            $this->conn->query("DROP DATABASE IF EXISTS " . $this->testDbName);
            $this->conn->close();
        }
        // Clear session and superglobal
        $_SESSION = [];
        $_POST = [];
        $_GET = [];
        $_SERVER = [];
    }

    public function testStep1_IDNumberNotFound()
    {
        global $error_message, $step, $success_message, $user_data;

        // Simulate POST request for step 1 with a nonexistent ID
        $_POST['id_number'] = 'nonexistent-id';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET['step'] = 1;

        // Include the script to execute its logic
        ob_start(); // Start output buffering to prevent headers from being sent
        include 'Project/forgot_password.php';
        ob_end_clean(); // End output buffering

        // Assertions
        $this->assertEquals('ID Number not found.', $error_message);
        $this->assertEquals(1, $step);
        $this->assertArrayNotHasKey('reset_user_id', $_SESSION);
    }

    public function testStep1_IDNumberFound()
    {
        global $error_message, $step, $success_message, $user_data;

        // Simulate POST request for step 1 with an existing ID
        $_POST['id_number'] = '2025-0001'; // Assuming this ID exists in your test database
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET['step'] = 1;

        // Include the script to execute its logic
        ob_start(); // Start output buffering to prevent headers from being sent
        include 'Project/forgot_password.php';
        ob_end_clean(); // End output buffering

        // Assertions
        $this->assertEquals(2, $step);
        $this->assertEquals('2025-0001', $_SESSION['reset_user_id']);
    }
}
