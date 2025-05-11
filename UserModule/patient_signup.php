<?php
// patient_signup.php
require_once '../config.php'; // For session access

// Retrieve errors and form data from session, then unset them
$signup_errors = $_SESSION['signup_errors'] ?? [];
$form_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['signup_errors']);
unset($_SESSION['form_data']);

// Helper function to safely get form values
function get_form_value($field_name, $form_data_array) {
    return htmlspecialchars($form_data_array[$field_name] ?? '');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Sign Up - Escosia Dental Clinic</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        /* Keep existing styles for errors, password requirements, password toggle */
        .error-messages { color: var(--danger-red, #dc3545); background-color: #fdd; border: 1px solid var(--danger-red, #dc3545); padding: 10px; margin-bottom: 15px; border-radius: 5px; }
        .error-messages ul { list-style: disc; padding-left: 20px; margin: 0; }
        .error-messages li { margin-bottom: 5px; }
        .success-message { color: green; background-color: #dfd; border: 1px solid green; padding: 10px; margin-bottom: 15px; border-radius: 5px; }
        .password-requirements { font-size: 0.85em; color: #555; margin-top: -10px; margin-bottom: 15px; padding-left: 5px; }
        .password-requirements ul { list-style: disc; padding-left: 20px; margin: 5px 0 0 0; }
        .password-requirements li { margin-bottom: 3px; }
        :root { --danger-red: #dc3545; --border-color: #ced4da; --text-muted: #6c757d; }
        .password-wrapper { position: relative; display: flex; align-items: center; }
        .password-wrapper input[type="password"],
        .password-wrapper input[type="text"] { padding-right: 40px; }
        .toggle-password { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-muted, #6c757d); background: none; border: none; padding: 5px; }
        .toggle-password:hover { color: var(--primary-green, #0A744F); }
        /* Style for select dropdown to match inputs */
        .auth-card select {
             width: 100%;
             padding: 10px 12px;
             border: 1px solid var(--old-input-border, #0A744F); /* Use same border as inputs */
             border-radius: 6px;
             font-size: 0.95em;
             background-color: var(--old-white, #fff); /* Use same background as inputs */
             color: var(--old-text-color, #333); /* Use same text color as inputs */
             -webkit-appearance: none; /* Remove default styling */
             -moz-appearance: none;
             appearance: none;
             background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23007bff%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.6-3.6%205.4-7.8%205.4-12.8%200-5-1.8-9.2-5.4-12.8z%22%2F%3E%3C%2Fsvg%3E'); /* Basic dropdown arrow */
             background-repeat: no-repeat;
             background-position: right .7em top 50%;
             background-size: .65em auto;
             cursor: pointer;
        }
        .auth-card select:focus {
             outline: none;
             border-color: var(--focus-border-color); /* Use shared focus color */
             box-shadow: 0 0 0 3px var(--focus-box-shadow); /* Use shared focus glow */
        }
         /* Style for disabled option */
         .auth-card select option[disabled] {
             color: #999;
         }
    </style>
</head>
<body class="auth-page">

    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <!-- Adjust path if needed -->
                <img src="../images/tooth.png" alt="Tooth Logo" class="logo-icon">
                <h2>Create Patient Account</h2>
            </div>

            <?php if (!empty($signup_errors)): ?>
                <div class="error-messages">
                    <p style="font-weight: bold; margin-bottom: 5px;">Please correct the following errors:</p>
                    <ul>
                        <?php foreach ($signup_errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form id="patientSignUpForm" action="signup_process.php" method="POST" novalidate>
                <div class="form-group">
                    <label for="firstName">First Name</label>
                    <input type="text" id="firstName" name="firstName" placeholder="Enter First Name" value="<?php echo get_form_value('firstName', $form_data); ?>" required>
                </div>
                <div class="form-group">
                    <label for="lastName">Last Name</label>
                    <input type="text" id="lastName" name="lastName" placeholder="Enter Last Name" value="<?php echo get_form_value('lastName', $form_data); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter Email Address" value="<?php echo get_form_value('email', $form_data); ?>" required>
                </div>

                <!-- **** ADDED GENDER DROPDOWN HERE **** -->
                <div class="form-group">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender" required>
                         <!-- Pre-select based on form_data if validation failed -->
                        <option value="" disabled <?php echo (!isset($form_data['gender']) || $form_data['gender'] === '') ? 'selected' : ''; ?>>Select Gender</option>
                        <option value="male" <?php echo (isset($form_data['gender']) && $form_data['gender'] === 'male') ? 'selected' : ''; ?>>Male</option>
                        <option value="female" <?php echo (isset($form_data['gender']) && $form_data['gender'] === 'female') ? 'selected' : ''; ?>>Female</option>
                        <option value="other" <?php echo (isset($form_data['gender']) && $form_data['gender'] === 'other') ? 'selected' : ''; ?>>Other</option>
                        <option value="prefer_not_say" <?php echo (isset($form_data['gender']) && $form_data['gender'] === 'prefer_not_say') ? 'selected' : ''; ?>>Prefer not to say</option>
                    </select>
                </div>
                <!-- **** END OF GENDER DROPDOWN **** -->

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" placeholder="Create Password" required>
                        <button type="button" class="toggle-password" aria-label="Show password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirmPassword">Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm Password" required>
                        <button type="button" class="toggle-password" aria-label="Show password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                 <!-- You might add password requirements display here if desired -->
                 <!-- <div class="password-requirements"> ... </div> -->

                <div class="form-group">
                    <label for="phoneNumber">Phone Number</label>
                    <input type="tel" id="phoneNumber" name="phoneNumber" placeholder="e.g., 09123456789"
                           value="<?php echo get_form_value('phoneNumber', $form_data); ?>" required
                           pattern="[0-9]{11}" maxlength="11" title="Phone number must be 11 digits.">
                </div>
                <div class="form-group">
                    <label for="dob">Date of Birth</label>
                    <input type="date" id="dob" name="dob" value="<?php echo get_form_value('dob', $form_data); ?>" required>
                </div>
                 <div class="form-group">
                    <label for="medicalInfo">Medical Information (Optional)</label>
                    <textarea id="medicalInfo" name="medicalInfo" rows="3" placeholder="List any allergies, conditions, or medications"><?php echo get_form_value('medicalInfo', $form_data); ?></textarea>
                </div>

                <button type="submit" class="btn btn-submit">Create Account</button>
            </form>

            <div class="auth-links">
                <span>Already have an account?</span>
                <a href="patient_signin.php">Sign In</a>
            </div>
        </div>
    </div>

     <script>
         // Keep existing JavaScript for password toggle and form validation
         const form = document.getElementById('patientSignUpForm');
         const passwordInput = document.getElementById('password');
         const confirmPasswordInput = document.getElementById('confirmPassword');
         const phoneNumberInput = document.getElementById('phoneNumber');
         const genderSelect = document.getElementById('gender'); // Get gender select

         document.querySelectorAll('.toggle-password').forEach(button => { button.addEventListener('click', function () { const wrapper = this.closest('.password-wrapper'); const passwordField = wrapper.querySelector('input'); const icon = this.querySelector('i'); if (passwordField.type === 'password') { passwordField.type = 'text'; icon.classList.add('fa-eye'); icon.classList.remove('fa-eye-slash'); this.setAttribute('aria-label', 'Hide password'); } else { passwordField.type = 'password'; icon.classList.add('fa-eye-slash'); icon.classList.remove('fa-eye'); this.setAttribute('aria-label', 'Show password'); } }); });

         form.addEventListener('submit', (e) => {
            let formIsValid = true;
            let clientSideErrors = [];

            // Reset previous validation styles
            form.querySelectorAll('input[required], select[required]').forEach(el => el.style.borderColor = 'var(--border-color)');

            // --- Client-Side Validation ---
            // Check required fields (basic check)
            form.querySelectorAll('input[required], select[required]').forEach(input => {
                if (!input.value.trim()) {
                    clientSideErrors.push(`${input.previousElementSibling.textContent} is required.`); // Get label text
                    input.style.borderColor = 'var(--danger-red)';
                    formIsValid = false;
                }
            });

            // Phone Number Check
            const phoneVal = phoneNumberInput.value.trim();
            if (phoneVal && !/^\d{11}$/.test(phoneVal)) { // Check only if not empty
                 clientSideErrors.push("Phone number must be exactly 11 digits and contain only numbers.");
                 phoneNumberInput.style.borderColor = 'var(--danger-red)';
                 formIsValid = false;
            }

            // Password Complexity Check
            const passwordVal = passwordInput.value;
            const passwordRequirementsMessages = [];
             // ... (keep existing password checks) ...
            if (passwordVal.length < 8) passwordRequirementsMessages.push("Be at least 8 characters long.");
            if (!/[A-Z]/.test(passwordVal)) passwordRequirementsMessages.push("Contain at least one uppercase letter (A-Z).");
            if (!/[a-z]/.test(passwordVal)) passwordRequirementsMessages.push("Contain at least one lowercase letter (a-z).");
            if (!/[0-9]/.test(passwordVal)) passwordRequirementsMessages.push("Contain at least one number (0-9).");
            if (!/[\'^£$%&*()}{@#~?><>,|=_+¬-]/.test(passwordVal)) passwordRequirementsMessages.push("Contain at least one special character.");


            if (passwordRequirementsMessages.length > 0) {
                let fullPasswordErrorMsg = "Password does not meet requirements:\n- " + passwordRequirementsMessages.join("\n- ");
                clientSideErrors.push(fullPasswordErrorMsg);
                passwordInput.style.borderColor = 'var(--danger-red)';
                formIsValid = false;
            }

            // Confirm Password Check
            if (passwordInput.value !== confirmPasswordInput.value) {
                clientSideErrors.push("Passwords do not match!");
                confirmPasswordInput.style.borderColor = 'var(--danger-red)';
                formIsValid = false;
            }

            // --- End Validation ---

            if (!formIsValid) {
                e.preventDefault(); // Prevent form submission
                // Use join with line breaks for alert
                alert("Please correct the following issues:\n\n- " + clientSideErrors.join("\n- "));
                // Focus first invalid field (simple approach)
                const firstInvalid = form.querySelector('[style*="border-color: var(--danger-red)"]');
                if (firstInvalid) firstInvalid.focus();
            }
            // If formIsValid is true, the form will submit normally
         });
     </script>
</body>
</html>