// Registration Form JavaScript
document.addEventListener('DOMContentLoaded', function() {
    let currentStep = 1;
    const totalSteps = 4;
    
    // Initialize form
    initializeForm();
    
    function initializeForm() {
        setupValidation();
        setupPasswordStrength();
        setupUsernameCheck();
        setupAgeCalculation();
        setupFormSubmission();
        setupAutoCapitalization();
        showStep(1);
    }
    
    // Step Navigation
    window.nextStep = function(step) {
        if (validateStep(step)) {
            if (step < totalSteps) showStep(step + 1);
        }
    };
    
    window.prevStep = function(step) {
        if (step > 1) showStep(step - 1);
    };
    
    function showStep(step) {
        document.querySelectorAll('.form-step').forEach(s => s.classList.remove('active'));
        document.getElementById(`step-${step}`).classList.add('active');
        document.querySelectorAll('.step').forEach((s, index) => {
            s.classList.toggle('active', index + 1 <= step);
        });
        currentStep = step;
    }
    
    // Validation Functions
    function validateStep(step) {
        let isValid = true;
        const stepElement = document.getElementById(`step-${step}`);
        if (!stepElement) return false;
        const inputs = stepElement.querySelectorAll('input[required], select[required]');
        inputs.forEach(input => { if (!validateField(input)) isValid = false; });
        return isValid;
    }
    
    function validateField(field) {
        const value = field.value.trim();
        const fieldName = field.name;
        let isValid = true;
        let errorMessage = '';
        
        clearError(fieldName);
        
        // Required field validation
        if (field.hasAttribute('required') && !value) {
            errorMessage = `${getFieldLabel(fieldName)} is required`;
            isValid = false;
        }
        
        // Specific field validations
        if (value && isValid) {
            switch (fieldName) {
                case 'first_name':
                case 'middle_name':
                case 'last_name':
                    if (!validateName(value)) {
                        errorMessage = getNameErrorMessage(value);
                        isValid = false;
                    }
                    break;

                case 'extension':
                    if (value && value.trim() !== '' && !validateExtension(value)) {
                        errorMessage = 'Invalid extension format';
                        isValid = false;
                    }
                    break;
                    
                case 'age':
                    const age = parseInt(value);
                    if (age < 18 || age > 120) {
                        errorMessage = 'Age must be between 18 and 120';
                        isValid = false;
                    }
                    break;
                    
                case 'zipcode':
                    if (!/^[0-9]{4,6}$/.test(value)) {
                        errorMessage = 'Zip code must be 4-6 digits';
                        isValid = false;
                    }
                    break;
                    
                case 'email':
                    if (!validateEmail(value)) {
                        errorMessage = 'Please enter a valid email address';
                        isValid = false;
                    }
                    break;
                    
                case 'username':
                    if (!validateUsername(value)) {
                        errorMessage = 'Username must be 3-20 characters, letters and numbers only';
                        isValid = false;
                    }
                    break;
                    
                case 'password':
                    if (!validatePassword(value)) {
                        errorMessage = 'Password must be at least 8 characters with uppercase, lowercase, and number';
                        isValid = false;
                    }
                    break;
                    
                case 'confirm_password':
                    const password = document.getElementById('password').value;
                    if (value !== password) {
                        errorMessage = 'Passwords do not match';
                        isValid = false;
                    }
                    break;
            }
        }
        
        if (!isValid) {
            showError(fieldName, errorMessage);
            field.classList.add('invalid');
        } else {
            field.classList.add('valid');
            field.classList.remove('invalid');
        }
        
        return isValid;
    }
    
    // Validation Helpers
    function validateName(name) {
        if (/[^a-zA-Z\s]/.test(name)) return false;
        if (/\s{2,}/.test(name)) return false;
        if (name === name.toUpperCase() && name.length > 1) return false;
        if (/(.)\1{2,}/i.test(name)) return false; // Prevent 3+ consecutive letters (case-insensitive)
        return true;
    }
    
    function getNameErrorMessage(name) {
        if (/[^a-zA-Z\s]/.test(name)) return 'Only letters and spaces are allowed';
        if (/\s{2,}/.test(name)) return 'Double spaces are not allowed';
        if (name === name.toUpperCase() && name.length > 1) return 'All capital letters are not allowed';
        if (/(.)\1{2,}/i.test(name)) return 'Three or more consecutive identical letters are not allowed';
        return 'Invalid name format';
    }
    
    function validateExtension(ext) {
        if (!ext || ext.trim() === '') return true;
        const validExtensions = ['Jr', 'Sr', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X'];
        return validExtensions.includes(ext.trim()) || /^[IVX]+$/.test(ext.trim());
    }
    
    function validateEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }
    
    function validateUsername(username) {
        return /^[a-zA-Z0-9]{3,20}$/.test(username);
    }
    
    function validatePassword(password) {
        return password.length >= 8 && /[a-z]/.test(password) && /[A-Z]/.test(password) && /\d/.test(password);
    }
    
    function getFieldLabel(fieldName) {
        const labels = {
            'first_name': 'First Name',
            'middle_name': 'Middle Name',
            'last_name': 'Last Name',
            'extension': 'Extension',
            'birthdate': 'Birthdate',
            'age': 'Age',
            'sex': 'Sex',
            'purok_street': 'Street/Purok',
            'barangay': 'Barangay',
            'municipal_city': 'Municipal/City',
            'province': 'Province',
            'country': 'Country',
            'zipcode': 'Zip Code',
            'answer1': 'Security Answer 1',
            'answer2': 'Security Answer 2',
            'answer3': 'Security Answer 3',
            'email': 'Email',
            'username': 'Username',
            'password': 'Password',
            'confirm_password': 'Confirm Password'
        };
        return labels[fieldName] || fieldName;
    }
    
    function showError(fieldName, message) {
        const errorElement = document.getElementById(`${fieldName}_error`);
        if (errorElement) errorElement.textContent = message;
    }
    
    function clearError(fieldName) {
        const errorElement = document.getElementById(`${fieldName}_error`);
        if (errorElement) errorElement.textContent = '';
    }
    
    // Setup functions
    function setupValidation() {
        document.querySelectorAll('input, select').forEach(field => {
            field.addEventListener('blur', () => validateField(field));
            field.addEventListener('input', () => {
                if (field.classList.contains('invalid')) validateField(field);
            });
        });
    }
    
    function setupPasswordStrength() {
        const passwordField = document.getElementById('password');
        const strengthIndicator = document.getElementById('password_strength');
        
        if (passwordField && strengthIndicator) {
            passwordField.addEventListener('input', function() {
                const strength = getPasswordStrength(this.value);
                strengthIndicator.textContent = strength.text;
                strengthIndicator.className = `password-strength ${strength.class}`;
            });
        }
    }
    
    function getPasswordStrength(password) {
        let score = 0;
        if (password.length >= 8) score++;
        if (/[a-z]/.test(password)) score++;
        if (/[A-Z]/.test(password)) score++;
        if (/\d/.test(password)) score++;
        if (/[^a-zA-Z\d]/.test(password)) score++;
        if (score < 3) return { text: 'Weak', class: 'weak' };
        if (score < 5) return { text: 'Medium', class: 'medium' };
        return { text: 'Strong', class: 'strong' };
    }
    
    function setupUsernameCheck() {
        const usernameField = document.getElementById('username');
        const checkElement = document.getElementById('username_check');
        
        if (usernameField && checkElement) {
            let timeout;
            usernameField.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    checkUsernameAvailability(this.value, checkElement);
                }, 500);
            });
        }
    }
    
    function checkUsernameAvailability(username, checkElement) {
        if (username.length < 3) {
            checkElement.textContent = '';
            return;
        }
        
        fetch(`../PHP/check_username.php?username=${encodeURIComponent(username)}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.exists) {
                checkElement.textContent = 'Username already taken';
                checkElement.className = 'validation-message error';
            } else {
                checkElement.textContent = 'Username available';
                checkElement.className = 'validation-message success';
            }
        })
        .catch(() => { checkElement.textContent = ''; });
    }
    
    function setupAgeCalculation() {
        const birthdateField = document.getElementById('birthdate');
        const ageField = document.getElementById('age');
        
        if (birthdateField && ageField) {
            birthdateField.addEventListener('change', function() {
                const birthDate = new Date(this.value);
                const today = new Date();
                let age = today.getFullYear() - birthDate.getFullYear();
                const monthDiff = today.getMonth() - birthDate.getMonth();
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                if (age >= 0 && age <= 150) {
                    ageField.value = age;
                    validateField(ageField);
                }
            });
        }
    }
    
    // ✅ AJAX Submission with feedback
    function setupFormSubmission() {
        const form = document.getElementById('multiStepForm');
        const submitBtn = document.getElementById('submitBtn');
        
        if (form && submitBtn) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                let allValid = true;
                for (let i = 1; i <= totalSteps; i++) {
                    if (!validateStep(i)) {
                        allValid = false;
                        showStep(i);
                        break;
                    }
                }

                if (!allValid) {
                    alert('Please complete all required fields correctly before submitting.');
                    return;
                }

                submitBtn.textContent = 'Registering...';
                submitBtn.disabled = true;

                const formData = new FormData(form);

                fetch('../PHP/register_action.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Registration Successful!',
                            text: data.message,
                            showConfirmButton: false,
                            timer: 2000
                        });
                        setTimeout(() => {
                            window.location.href = '../Project/index.php';
                        }, 2000);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Registration Failed',
                            text: data.message || 'An error occurred.'
                        });
                        if (data.error_details) console.error(data.error_details);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Server Error',
                        text: 'An unexpected error occurred. Please try again later.'
                    });
                })
                .finally(() => {
                    submitBtn.textContent = 'Register';
                    submitBtn.disabled = false;
                });
            });
        }
    }

    // Auto-capitalization setup
    function setupAutoCapitalization() {
        const fieldsToCapitalize = [
            'first_name', 'middle_name', 'last_name', 'extension',
            'purok_street', 'barangay', 'municipal_city', 'province', 'country',
            'answer1', 'answer2', 'answer3'
        ];
        fieldsToCapitalize.forEach(fieldName => {
            const field = document.getElementById(fieldName);
            if (field) field.addEventListener('input', e => autoCapitalizeInput(e.target));
        });
    }

    function autoCapitalizeInput(input) {
        const cursorPosition = input.selectionStart;
        const originalValue = input.value;
        const capitalizedValue = originalValue
            .toLowerCase()
            .split(' ')
            .map(word => word.length > 0 ? word.charAt(0).toUpperCase() + word.slice(1) : word)
            .join(' ');
        if (originalValue !== capitalizedValue) {
            input.value = capitalizedValue;
            const newCursorPosition = Math.min(cursorPosition, capitalizedValue.length);
            input.setSelectionRange(newCursorPosition, newCursorPosition);
        }
    }

    // Password toggle function
    window.togglePassword = function(fieldId) {
        const field = document.getElementById(fieldId);
        const button = field.nextElementSibling;
        if (field.type === 'password') {
            field.type = 'text';
            button.textContent = '🙈';
        } else {
            field.type = 'password';
            button.textContent = '👁️';
        }
    };
});
