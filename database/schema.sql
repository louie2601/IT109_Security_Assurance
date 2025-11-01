-- Database Schema for Security Assurance System
-- Database name: sucurity (as per existing db.php)

CREATE DATABASE IF NOT EXISTS sucurity;
USE sucurity;

-- Users table with comprehensive fields
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Personal Information
    id_number VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50) DEFAULT NULL,
    last_name VARCHAR(50) NOT NULL,
    suffix VARCHAR(10) DEFAULT NULL,
    birthdate DATE NOT NULL,
    age INT NOT NULL,
    sex ENUM('Male', 'Female', 'Other') NOT NULL,

    -- Address Information
    street VARCHAR(100) NOT NULL,
    barangay VARCHAR(50) NOT NULL,
    municipal VARCHAR(50) NOT NULL,
    province VARCHAR(50) NOT NULL,
    country VARCHAR(50) NOT NULL DEFAULT 'Philippines',
    zipcode VARCHAR(10) NOT NULL,

    -- Security Questions
    security_question_1 VARCHAR(255) NOT NULL DEFAULT 'Who is your best friend in Elementary?',
    answer_1 VARCHAR(255) NOT NULL,
    security_question_2 VARCHAR(255) NOT NULL DEFAULT 'What is the name of your favorite pet?',
    answer_2 VARCHAR(255) NOT NULL,
    security_question_3 VARCHAR(255) NOT NULL DEFAULT 'Who is your favorite teacher in high school?',
    answer_3 VARCHAR(255) NOT NULL,

    -- Account Information
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,

    -- System fields
    role ENUM('student', 'admin') DEFAULT 'student',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Validation constraints
    CONSTRAINT chk_age CHECK (age >= 18 AND age <= 120),
    CONSTRAINT chk_zipcode CHECK (zipcode REGEXP '^[0-9]{4,6}$')
);

-- Login attempts table for tracking failed login attempts
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    success BOOLEAN DEFAULT FALSE,

    INDEX idx_username_time (username, attempt_time),
    INDEX idx_ip_time (ip_address, attempt_time)
);

-- Password reset tokens table
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expires (expires_at)
);

-- Session management table
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_session_id (session_id),
    INDEX idx_user_active (user_id, is_active)
);

-- Insert default security questions (for reference)
CREATE TABLE IF NOT EXISTS security_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question TEXT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO security_questions (question) VALUES
('Who is your best friend in Elementary?'),
('What is the name of your favorite pet?'),
('Who is your favorite teacher in high school?'),
('What is your mother\'s maiden name?'),
('In what city were you born?'),
('What was the name of your first school?');

-- Create indexes for better performance
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_created_at ON users(created_at);
CREATE INDEX idx_login_attempts_username ON login_attempts(username);
CREATE INDEX idx_login_attempts_ip ON login_attempts(ip_address);
