# Mindanao Institute Security Assurance System

A comprehensive registration and authentication system built with PHP, MySQL, HTML, CSS, and JavaScript.

## Features

### 1. Multi-Step Registration System
- **Step 1: Personal Information**
  - ID Number (primary key, unique)
  - First Name, Middle Name (optional), Last Name
  - Extension (optional) - supports Jr., Sr., III, etc.
  - Birthdate with automatic age calculation
  - Sex selection

- **Step 2: Address Information**
  - Purok/Street, Barangay, Municipal/City
  - Province, Country (default: Philippines)
  - Zip Code (4-6 digits)

- **Step 3: Security Questions**
  - Who is your best friend in Elementary?
  - What is the name of your favorite pet?
  - Who is your favorite teacher in high school?

- **Step 4: Account Information**
  - Email address
  - Username (auto-checked for availability)
  - Password with strength indicator
  - Password confirmation

### 2. Input Validation
- **Name Fields**: 
  - Only letters and spaces allowed
  - No special characters or numbers
  - No double spaces
  - No all capital letters
  - No three consecutive same letters
  - First letter of each word must be capitalized

- **Age Validation**: 
  - Must be 18+ (legal age only)
  - Automatically calculated from birthdate

- **Password Requirements**:
  - Minimum 8 characters
  - Must contain uppercase, lowercase, and numbers
  - Strength indicator (Weak/Medium/Strong)

- **Username**: 
  - 3-20 characters
  - Letters and numbers only
  - Real-time availability checking

### 3. Authentication System
- **Progressive Lockout**:
  - 3 failed attempts = 15 seconds lockout
  - 6 failed attempts = 30 seconds lockout
  - 9 failed attempts = 60 seconds lockout

- **Security Features**:
  - Show/hide password functionality
  - Forgot password after 2 failed attempts
  - Browser back button disabled
  - Source code viewing protection

### 4. Password Recovery
- **3-Step Process**:
  1. Verify username/email
  2. Answer security questions
  3. Set new password

### 5. User Dashboard
- Profile summary
- Account information
- Address details
- Security settings
- Quick actions menu

### 6. Security Features
- Password hashing using PHP's password_hash()
- Security answers hashed for protection
- Session management with database tracking
- Login attempt logging
- CSRF protection
- SQL injection prevention using prepared statements

## File Structure

```
IT_109 SECURITY ASSURANCE/
├── CSS/
│   ├── style.css           # Main stylesheet
│   ├── registration.css    # Registration form styles
│   ├── login.css          # Login page styles
│   ├── dashboard.css      # Dashboard styles
│   ├── forgot_password.css # Password recovery styles
│   ├── change_password.css # Change password styles
│   └── home.css           # Home page styles
├── JS/
│   ├── script.js          # Main JavaScript
│   ├── registration.js    # Registration form validation
│   └── register.js        # Legacy registration script
├── PHP/
│   ├── register_action.php # Registration form handler
│   ├── check_username.php  # Username availability checker
│   └── check_suffix.php    # Suffix validation
├── Project/
│   ├── index.php          # Home/Login page
│   ├── registration.php   # Multi-step registration
│   ├── login.php          # Login page
│   ├── dashboard.php      # User dashboard
│   ├── forgot_password.php # Password recovery
│   ├── change_password.php # Change password
│   └── logout.php         # Logout handler
├── includes/
│   ├── header.php         # Header with dynamic navigation
│   ├── footer.php         # Footer with copyright
│   └── db.php             # Database connection
├── database/
│   └── schema.sql         # Database schema
└── IMAGES/
    └── logo.png           # Institute logo
```

## Database Schema

### Users Table
- `id` - Primary key
- `id_number` - Unique student ID
- `first_name`, `middle_name`, `last_name`, `suffix` - Name fields
- `birthdate`, `age`, `sex` - Personal info
- `street`, `barangay`, `municipal`, `province`, `country`, `zipcode` - Address
- `security_question_1-3`, `answer_1-3` - Security questions
- `username`, `email`, `password` - Account info
- `role`, `is_active`, `created_at`, `updated_at` - System fields

### Additional Tables
- `login_attempts` - Track failed login attempts
- `password_reset_tokens` - Password reset tokens
- `user_sessions` - Active user sessions
- `security_questions` - Available security questions

## Installation

1. **Database Setup**:
   ```sql
   CREATE DATABASE sucurity;
   ```
   Import `database/schema.sql`

2. **Web Server**:
   - Place files in XAMPP htdocs directory
   - Ensure PHP and MySQL are running

3. **Configuration**:
   - Update database credentials in `includes/db.php`
   - Ensure proper file permissions

## Usage

1. **Registration**:
   - Navigate to `Project/registration.php`
   - Complete all 4 steps with valid information
   - System validates all inputs in real-time

2. **Login**:
   - Use username/email and password
   - System tracks failed attempts
   - Progressive lockout for security

3. **Password Recovery**:
   - Click "Forgot Password" after 2 failed attempts
   - Answer security questions
   - Set new password

## Security Measures

- All passwords are hashed using PHP's `password_hash()`
- Security answers are also hashed
- SQL injection prevention with prepared statements
- Session management with database tracking
- Browser security (back button disabled, source protection)
- Progressive login lockout system
- Real-time input validation

## Browser Compatibility

- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+

## Requirements

- PHP 7.4+
- MySQL 5.7+
- Apache/Nginx web server
- Modern web browser with JavaScript enabled

## License

This project is for educational purposes as part of IT 109 Security Assurance coursework.