// Registration Form JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const hiddenCurrentStep = document.getElementById('current_step');
    let currentStep = hiddenCurrentStep ? parseInt(hiddenCurrentStep.value) : 1;
    const totalSteps = 4;
    
    // Initialize form
    initializeForm();
    
    function initializeForm() {
        setupValidation();
        setupPasswordStrength();
        setupUsernameCheck();
        setupEmailCheck(); // Call the new email check setup
        setupAgeCalculation();
        setupFormSubmission();
        setupAutoCapitalization();
        setupAutoSaveForm(); // ✅ Added auto-save feature
        showStep(currentStep);
    }
    
    // Step Navigation
    window.nextStep = function(step) {
        if (validateStep(step)) {
            if (step < totalSteps) showStep(step + 1);
        } else {
            alert('Please fill out all required fields correctly before proceeding.');
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

        if (step === 4) {
            const questions = Array.from(document.querySelectorAll('select[name="security_question[]"]'));
            const answers = Array.from(document.querySelectorAll('input[name="security_answer[]"]'));
            const selectedQuestions = questions.map(q => q.value);

            // Check for duplicate questions
            const uniqueQuestions = new Set(selectedQuestions.filter(q => q));
            if (uniqueQuestions.size !== selectedQuestions.filter(q => q).length) {
                alert('Please select three unique security questions.');
                return false;
            }

            // Check that all questions are answered
            for (let i = 0; i < questions.length; i++) {
                if (!questions[i].value || !answers[i].value) {
                    isValid = false;
                    showError(`security_question_${i + 1}`, 'Please select a question and provide an answer.');
                } else {
                    clearError(`security_question_${i + 1}`);
                }
            }
            return isValid;
        }

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
            let validationMessage = "";
            switch (fieldName) {
                case 'first_name':
                case 'last_name':
                case 'municipal':
                case 'province':
                case 'country':
                    validationMessage = validateNoNumbersOrSpecialChars(value);
                    if (validationMessage) {
                        errorMessage = validationMessage;
                        isValid = false;
                    }
                    break;
                case 'middle_name':
                    if (value) { // middle_name is optional
                        validationMessage = validateNoNumbersOrSpecialChars(value);
                        if (validationMessage) {
                            errorMessage = validationMessage;
                            isValid = false;
                        }
                    }
                    break;

                case 'extension':
                    if (value) {
                        const hasNumbers = /\d/.test(value);
                        const hasSpecialChars = /[^a-zA-Z.]/.test(value);

                        if (hasNumbers && hasSpecialChars) {
                            errorMessage = 'Numbers and Special Characters are not allowed in suffix';
                            isValid = false;
                        } else if (hasNumbers) {
                            errorMessage = 'Numbers are not allowed in suffix';
                            isValid = false;
                        } else if (hasSpecialChars) {
                            errorMessage = 'Special characters are not allowed in suffix';
                            isValid = false;
                        }
                    }
                    break;
                
                case 'street':
                case 'barangay':
                    if (!validateStreet(value)) {
                        errorMessage = getStreetErrorMessage(value);
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
                    if (/[a-zA-Z]/.test(value)) {
                        errorMessage = 'Zip code cannot contain letters';
                        isValid = false;
                    } else if (!/^[0-9]{4}$/.test(value)) {
                        errorMessage = 'Zip code must be exactly 4 digits';
                        isValid = false;
                    }
                    break;
                    
                case 'email':
                    errorMessage = validateEmail(value);
                    if (errorMessage) {
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
    function validateNoNumbersOrSpecialChars(value) {
        const hasNumber = /\d/.test(value);
        const hasSpecialChar = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(value);
        const hasThreeIdenticalLetters = /(.)\1{2,}/i.test(value);

        if (hasNumber && hasSpecialChar) {
            return "Numbers and Special Character Is not allowed";
        } else if (hasNumber) {
            return "Number is not allowed";
        } else if (hasSpecialChar) {
            return "Special Character is not allowed";
        } else if (hasThreeIdenticalLetters) {
            return "Three or more consecutive identical letters are not allowed";
        }
        return "";
    }

    function validateStreet(street) {
        if (/^[\-\.,]/.test(street)) return false; // Disallow starting with special characters -, .
        if (/^\d/.test(street)) return false; // New check: Disallow starting with a number
        if (/[^a-zA-Z0-9\s\-\.,]/.test(street)) return false; // Allow letters, numbers, spaces, and some punctuation
        if (/\s{2,}/.test(street)) return false; // No double spaces
        if (/(.)\1{2,}/i.test(street)) return false; // No three consecutive identical letters
        return true;
    }

    function getStreetErrorMessage(street) {
        if (/^[\-\.,]/.test(street)) return 'Street cannot start with a special character';
        if (/^\d/.test(street)) return 'The number should not come first'; // New error message
        if (/[^a-zA-Z0-9\s\-\.,]/.test(street)) return 'Invalid characters in street';
        if (/\s{2,}/.test(street)) return 'Double spaces are not allowed';
        if (/(.)\1{2,}/i.test(street)) return 'Three or more consecutive identical letters are not allowed';
        return 'Invalid street format';
    }
    
    function validateExtension(ext) {
        if (!ext || ext.trim() === '') return true;
        const validExtensions = ['Jr', 'Sr', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X'];
        return validExtensions.includes(ext.trim()) || /^[IVX]+$/.test(ext.trim());
    }
    
    function validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            return 'Please enter a valid email address (e.g., example@gmail.com)';
        }
        return '';
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
            'street': 'Street',
            'barangay': 'Barangay',
            'municipal': 'Municipal/City',
            'province': 'Province',
            'country': 'Country',
            'zipcode': 'Zip Code',
            'security_question_1': 'Security Question 1',
            'security_answer_1': 'Security Answer 1',
            'security_question_2': 'Security Question 2',
            'security_answer_2': 'Security Answer 2',
            'security_question_3': 'Security Question 3',
            'security_answer_3': 'Security Answer 3',
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
                validateField(field);
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
        if (usernameField) {
            let timeout;
            usernameField.addEventListener('input', function() {
                clearTimeout(timeout);
                clearError('username'); // Clear previous error
                timeout = setTimeout(() => {
                    checkFieldAvailability('username', this.value, 'username_error');
                }, 500);
            });
            usernameField.addEventListener('blur', function() {
                clearTimeout(timeout);
                checkFieldAvailability('username', this.value, 'username_error');
            });
        }
    }

    function setupEmailCheck() {
        const emailField = document.getElementById('email');
        if (emailField) {
            let timeout;
            emailField.addEventListener('input', function() {
                clearError('email'); // Clear previous error
                checkFieldAvailability('email', this.value, 'email_error');
            });
            emailField.addEventListener('blur', function() {
                checkFieldAvailability('email', this.value, 'email_error');
            });
        }
    }
    
    async function checkFieldAvailability(field, value, errorElementId) {
        if (value.length < 3 && (field === 'username' || field === 'email')) { // Minimum length for check
            clearError(errorElementId);
            return;
        }
        
        const errorElement = document.getElementById(errorElementId);
        if (errorElement) errorElement.textContent = 'Checking availability...';

        try {
            const response = await fetch(`../PHP/check_duplicate.php?${field}=${encodeURIComponent(value)}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();

            if (data.exists) {
                showError(field, `${getFieldLabel(field)} already exists.`);
            } else {
                clearError(field);
            }
        } catch (error) {
            console.error(`Error checking ${field} availability:`, error);
            showError(field, `Error checking ${getFieldLabel(field)}.`);
        }
    }

    function setupAgeCalculation() {
        const birthdateField = document.getElementById('birthdate');
        const ageField = document.getElementById('age');
        
        if (birthdateField && ageField) {
            const calculateAgeAndSetField = () => {
                const birthDate = new Date(birthdateField.value);
                const today = new Date();
                let age = today.getFullYear() - birthDate.getFullYear();
                const monthDiff = today.getMonth() - birthDate.getMonth();
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                if (age >= 0 && age <= 150) {
                    ageField.value = age;
                    validateField(ageField);
                } else {
                    ageField.value = '';
                }
            };

            birthdateField.addEventListener('change', calculateAgeAndSetField);
            if (birthdateField.value) calculateAgeAndSetField();
        }
    }
    

    
    function setupFormSubmission() {
        const form = document.getElementById('multiStepForm');
        const submitBtn = document.getElementById('submitBtn');
        
        if (form && submitBtn) {
            form.addEventListener('submit', function(e) {
                let allValid = true;
                for (let i = 1; i <= totalSteps; i++) {
                    if (!validateStep(i)) {
                        allValid = false;
                        showStep(i);
                        break;
                    }
                }

                if (!allValid) {
                    e.preventDefault();
                    alert('Please complete all required fields correctly before submitting.');
                    return;
                }

                submitBtn.textContent = 'Registering...';
                submitBtn.disabled = true;

                clearSavedFormData(); // ✅ clear data after submission
            });
        }
    }

    // ✅ Auto-save and restore form data using localStorage
    function setupAutoSaveForm() {
        const form = document.getElementById('multiStepForm');
        if (!form) return;

        // Restore saved values on load
        const savedData = JSON.parse(localStorage.getItem('registrationFormData') || '{}');
        for (const [key, value] of Object.entries(savedData)) {
            const field = document.querySelector(`[name="${key}"]`);
            if (field) field.value = value;
        }

        // Save input changes
        form.querySelectorAll('input, select').forEach(field => {
            field.addEventListener('input', () => {
                const data = JSON.parse(localStorage.getItem('registrationFormData') || '{}');
                data[field.name] = field.value;
                localStorage.setItem('registrationFormData', JSON.stringify(data));
            });
        });
    }

    // ✅ Clear saved form data
    function clearSavedFormData() {
        localStorage.removeItem('registrationFormData');
    }

    // Auto-capitalization setup
    function setupAutoCapitalization() {
        const fieldsToCapitalize = [
            'first_name', 'middle_name', 'last_name', 'extension',
            'street', 'barangay', 'municipal', 'province', 'country',
            'security_answer_1', 'security_answer_2', 'security_answer_3'
        ];
        fieldsToCapitalize.forEach(fieldName => {
            const field = document.getElementById(fieldName);
            if (field) field.addEventListener('input', e => autoCapitalizeInput(e.target));
        });

        const streetField = document.getElementById('street');
        if (streetField) {
            streetField.addEventListener('input', e => {
                const input = e.target;
                const cursorPosition = input.selectionStart;
                let value = input.value;

                // Capitalize first letter of each word
                value = value.toLowerCase().split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');

                // Remove double spaces
                value = value.replace(/\s{2,}/g, ' ');

                // Remove invalid characters
                value = value.replace(/[^a-zA-Z0-9\s\-\.,]/g, '');

                if (input.value !== value) {
                    input.value = value;
                    input.setSelectionRange(cursorPosition, cursorPosition);
                }
            });
        }

        const municipalField = document.getElementById('municipal');
        if (municipalField) {
            municipalField.addEventListener('input', e => {
                const input = e.target;
                const cursorPosition = input.selectionStart;
                let value = input.value;

                // Capitalize first letter of each word
                value = value.toLowerCase().split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');

                // Remove double spaces
                value = value.replace(/\s{2,}/g, ' ');

                // Remove invalid characters
                value = value.replace(/[^a-zA-Z0-9\s\-\.,]/g, '');

                if (input.value !== value) {
                    input.value = value;
                    input.setSelectionRange(cursorPosition, cursorPosition);
                }
            });
        }

        const provinceField = document.getElementById('province');
        if (provinceField) {
            provinceField.addEventListener('input', e => {
                const input = e.target;
                const cursorPosition = input.selectionStart;
                let value = input.value;

                // Capitalize first letter of each word
                value = value.toLowerCase().split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');

                // Remove double spaces
                value = value.replace(/\s{2,}/g, ' ');

                // Remove invalid characters
                value = value.replace(/[^a-zA-Z0-9\s\-\.,]/g, '');

                if (input.value !== value) {
                    input.value = value;
                    input.setSelectionRange(cursorPosition, cursorPosition);
                }
            });
        }

        const barangayField = document.getElementById('barangay');
        if (barangayField) {
            barangayField.addEventListener('input', e => {
                const input = e.target;
                const cursorPosition = input.selectionStart;
                let value = input.value;

                // Capitalize first letter of each word
                value = value.toLowerCase().split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');

                // Remove double spaces
                value = value.replace(/\s{2,}/g, ' ');

                // Remove invalid characters
                value = value.replace(/[^a-zA-Z0-9\s\-\.,]/g, '');

                if (input.value !== value) {
                    input.value = value;
                    input.setSelectionRange(cursorPosition, cursorPosition);
                }
            });
        }
    }

    // ✅ Updated autoCapitalizeInput with suffix logic
    function autoCapitalizeInput(input) {
        const cursorPosition = input.selectionStart;
        const originalValue = input.value;
        let capitalizedValue = originalValue
            .toLowerCase()
            .split(' ')
            .map(word => word.length > 0 ? word.charAt(0).toUpperCase() + word.slice(1) : word)
            .join(' ');
        
        capitalizedValue = capitalizedValue.replace(/\s{2,}/g, ' ');

        if (input.id === 'extension') {
            const ext = capitalizedValue.trim();
            const romanRegex = /^(ii|iii|iv|v|vi|vii|viii|ix|x)$/i;
            if (romanRegex.test(ext)) {
                capitalizedValue = ext.toUpperCase();
            } else if (ext.toLowerCase() === 'jr') {
                capitalizedValue = 'Jr';
            } else if (ext.toLowerCase() === 'sr') {
                capitalizedValue = 'Sr';
            }
        }

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