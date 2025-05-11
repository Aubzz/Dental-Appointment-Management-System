<?php
// patient_signin.php
require_once '../config.php';

if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: patient_dashboard.php");
    exit;
}

$signin_errors = $_SESSION['signin_errors'] ?? [];
$form_data_signin = $_SESSION['form_data_signin'] ?? [];
$success_message = $_SESSION['success_message'] ?? '';

unset($_SESSION['signin_errors']);
unset($_SESSION['form_data_signin']);
unset($_SESSION['success_message']);

function get_signin_form_value($field_name, $form_data_array) {
    return htmlspecialchars($form_data_array[$field_name] ?? '');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Sign In - Escosia Dental Clinic</title>
    <link rel="stylesheet" href="style.css">
    <!-- ADD FONT AWESOME (if not already included) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
     <style>
        .error-messages { color: var(--danger-red, #dc3545); background-color: #fdd; border: 1px solid var(--danger-red, #dc3545); padding: 10px; margin-bottom: 15px; border-radius: 5px; }
        .error-messages ul { list-style: none; padding: 0; margin: 0; }
        .error-messages li { margin-bottom: 5px; }
        .success-message { color: green; background-color: #dfd; border: 1px solid green; padding: 10px; margin-bottom: 15px; border-radius: 5px; }
        :root { --danger-red: #dc3545; --text-muted: #6c757d; --primary-green: #0A744F; }

        /* --- STYLES FOR PASSWORD TOGGLE (can be moved to style.css) --- */
        .password-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .password-wrapper input[type="password"],
        .password-wrapper input[type="text"] {
            padding-right: 40px; /* Space for icon */
        }
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-muted, #6c757d);
            background: none;
            border: none;
            padding: 5px;
        }
        .toggle-password:hover {
            color: var(--primary-green, #0A744F);
        }
        /* --- END OF PASSWORD TOGGLE STYLES --- */
    </style>
</head>
<body class="auth-page">

    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                 <img src="teeth.png" alt="Tooth Logo" class="logo-icon">
                 <h2>Escosia Dental Clinic </h2>
                 <h2>Patient Sign In </h2>
            </div>

            <?php if (!empty($success_message)): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($signin_errors)): ?>
                <div class="error-messages">
                    <ul>
                        <?php foreach ($signin_errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form id="patientSignInForm" action="signin_process.php" method="POST">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter Email Address" value="<?php echo get_signin_form_value('email', $form_data_signin); ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <!-- ADDED password-wrapper and toggle button -->
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" placeholder="Enter Password" required>
                        <button type="button" class="toggle-password" aria-label="Show password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-submit">Sign In</button>
            </form>

            <div class="auth-links">
                <a href="#forgot" class="forgot-password">Forgot Password?</a>
                <span>Don't have an account?</span>
                <a href="patient_signup.php">Sign Up</a>
            </div>
        </div>
    </div>
    <script>
        // --- Password Toggle Functionality ---
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function () {
                const wrapper = this.closest('.password-wrapper');
                const passwordField = wrapper.querySelector('input');
                const icon = this.querySelector('i');

                if (passwordField.type === 'password') {
                    passwordField.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                    this.setAttribute('aria-label', 'Hide password');
                } else {
                    passwordField.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                    this.setAttribute('aria-label', 'Show password');
                }
            });
        });
    </script>
</body>
</html>