<?php
require_once '../config.php'; // Ensures session_start() is called
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receptionist Sign Up - Escosia Dental Clinic</title>
    <link rel="stylesheet" href="receptionist_signup.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <div class="auth-container">
        <div class="auth-right">
            <div class="auth-card">
                <h2>Create Receptionist Account</h2>

                <div id="serverMessages" class="server-messages" style="display:none; margin-bottom: 15px;"></div>

                 <form id="signupForm" method="POST" action="receptionist_signup_process.php" novalidate>
                      <input type="hidden" name="role" value="receptionist" autocomplete="off"> <!-- Autocomplete off for hidden roles -->

                      <div class="form-group">
                         <input type="text" id="firstName" name="first_name" class="form-control" placeholder="First name" required autocomplete="given-name">
                      </div>
                      <div class="form-group">
                         <input type="text" id="lastName" name="last_name" class="form-control" placeholder="Last name" required autocomplete="family-name">
                      </div>
                      <div class="form-group">
                         <input type="email" id="email" name="email" class="form-control" placeholder="Email" required autocomplete="email">
                         <small class="form-help error-message" id="emailError" style="display:none;"></small>
                      </div>

                      <div class="form-group">
                         <div class="password-wrapper">
                            <input type="password" id="password" name="password" class="form-control" placeholder="Password" required aria-describedby="passwordRequirements" autocomplete="new-password">
                            <button type="button" class="toggle-password" aria-label="Show password">
                                <i class="fas fa-eye"></i>
                            </button>
                         </div>
                         <div id="passwordRequirements" class="password-requirements-list" style="display:none;">
                            <ul>
                                <li id="length" class="invalid">At least 8 characters</li>
                                <li id="uppercase" class="invalid">An uppercase letter (A-Z)</li>
                                <li id="lowercase" class="invalid">A lowercase letter (a-z)</li>
                                <li id="number" class="invalid">A number (0-9)</li>
                            </ul>
                         </div>
                         <small class="form-help error-message" id="passwordError" style="display:none;"></small>
                      </div>

                      <div class="form-group">
                         <div class="password-wrapper">
                            <input type="password" id="confirmPassword" name="confirm_password" class="form-control" placeholder="Confirm Password" required autocomplete="new-password">
                            <button type="button" class="toggle-password" aria-label="Show password">
                                <i class="fas fa-eye"></i>
                            </button>
                         </div>
                         <small class="form-help error-message" id="confirmPasswordError" style="display:none;"></small>
                      </div>

                     <div class="form-fields-section" id="receptionistFieldsDirect">
                         <hr style="margin-top: 20px; margin-bottom: 20px;">
                         <p class="section-title" style="margin-bottom: 10px; padding-top: 0;">Receptionist Details</p>
                         <div class="form-group">
                            <input type="tel" id="phoneNumberReceptionist" name="phone_number" class="form-control" placeholder="Phone Number (e.g., 09123456789)" required pattern="^\d{11}$" title="Phone number must be 11 digits." autocomplete="tel-national">
                            <small class="form-help">For contact & account verification.</small>
                         </div>
                          <div class="form-group">
                            <input type="text" id="receptionistIdInput" name="receptionist_id" class="form-control" placeholder="Receptionist ID (e.g., REP-1234)" required autocomplete="off"> <!-- Or a custom one like "username" if appropriate -->
                            <small class="form-help">Your clinic-provided receptionist ID. Example Format: REP-XXXX</small>
                            <small class="form-help error-message" id="receptionistIdError" style="display:none;"></small>
                         </div>
                     </div>

                     <button type="submit" id="signupButton" class="btn btn-primary">Create Account</button>
                 </form>

                <div class="auth-footer">
                    <p>Already have an account? <a href="receptionist_signin.html">Sign In</a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="receptionist_signup.js"></script>
    <script>
        <?php if (isset($_SESSION['signup_errors']) && !empty($_SESSION['signup_errors'])): ?>
            const serverMessagesDivSignup = document.getElementById('serverMessages');
            if(serverMessagesDivSignup) {
                serverMessagesDivSignup.style.display = 'block';
                serverMessagesDivSignup.innerHTML = `
                    <ul style="color: red; list-style-type: none; padding: 0; margin:0; font-size: 0.9em;">
                        <?php foreach ($_SESSION['signup_errors'] as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>`;
            }
            <?php unset($_SESSION['signup_errors']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['form_data']) && !empty($_SESSION['form_data'])): ?>
            const formDataSignup = <?php echo json_encode($_SESSION['form_data']); ?>;
            for (const key in formDataSignup) {
                if (formDataSignup.hasOwnProperty(key)) {
                    const element = document.querySelector(`#signupForm [name="${key}"]`);
                    if (element && element.type !== 'password' && element.name !== 'role') {
                        element.value = formDataSignup[key];
                    }
                }
            }
            <?php unset($_SESSION['form_data']); ?>
        <?php endif; ?>
    </script>

</body>
</html>  