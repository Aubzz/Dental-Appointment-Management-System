<?php
require_once '../config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Sign Up - Escosia Dental Clinic</title>
    <link rel="stylesheet" href="doctor_signup.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-right">
            <div class="auth-card">
                <h2>Create Doctor Account</h2>
                <div id="serverMessages" class="server-messages" style="display:none; margin-bottom: 15px;"></div>
                <form id="signupForm" method="POST" action="doctor_signup_process.php" enctype="multipart/form-data" novalidate>
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <input type="text" name="first_name" class="form-control" placeholder="First Name" required>
                            </div>
                            <div class="form-group">
                                <input type="text" name="last_name" class="form-control" placeholder="Last Name" required>
                            </div>
                            <div class="form-group">
                                <input type="email" name="email" class="form-control" placeholder="Email" required>
                            </div>
                            <div class="form-group">
                                <input type="tel" name="phone_number" class="form-control" placeholder="Phone Number (e.g., 09123456789)" required pattern="^\d{11}$">
                            </div>
                            <div class="form-group">
                                <div class="password-wrapper">
                                    <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
                                    <button type="button" class="toggle-password" aria-label="Show password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="password-wrapper">
                                    <input type="password" name="confirm_password" id="confirmPassword" class="form-control" placeholder="Confirm Password" required>
                                    <button type="button" class="toggle-password" aria-label="Show password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <input type="number" name="experience_years" class="form-control" placeholder="Years of Experience" min="0" required>
                            </div>
                            <div class="form-group">
                                <input type="text" name="specialty" class="form-control" placeholder="Specialty (e.g., Orthodontist)" required>
                            </div>
                            <div class="form-group">
                                <input type="number" name="consultation_fee" class="form-control" placeholder="Consultation Fee" min="0" step="0.01" required>
                            </div>
                            <div class="form-group no-margin-top">
                                <label class="profile-picture-box">
                                    <input type="file" id="profilePictureInput" name="profile_picture" accept="image/*" style="display:none;">
                                    <span class="profile-picture-label">Profile Picture</span>
                                    <span class="profile-picture-filename" id="fileChosenText">No file chosen</span>
                                </label>
                            </div>
                            <div class="form-group no-margin-bottom">
                                <textarea name="bio" class="form-control" placeholder="Short Bio" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col" style="flex: 1 1 100%; text-align: center;">
                            <button type="submit" class="btn btn-primary" style="margin: 0 auto;">Create Account</button>
                        </div>
                    </div>
                </form>
                <div class="auth-footer">
                    <p>Already have an account? <a href="doctor_signin.html">Sign In</a></p>
                </div>
            </div>
        </div>
    </div>
    <script src="doctor_signup.js"></script>
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