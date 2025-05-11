<?php
// AdminModule/admin_login.php
require_once '../config.php';

$error_message = '';

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header('Location: admin_dashboard.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $admin_username_input = trim($_POST['username'] ?? '');
    $admin_password_input = $_POST['password'] ?? '';

    if (empty($admin_username_input) || empty($admin_password_input)) {
        $error_message = "Username and password are required.";
    } else {
        $sql = "SELECT id, username, password_hash FROM admins WHERE username = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $admin_username_input);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                if ($result->num_rows == 1) {
                    $admin_user = $result->fetch_assoc();
                    if (password_verify($admin_password_input, $admin_user['password_hash'])) {
                        $_SESSION['loggedin'] = true;
                        $_SESSION['user_id'] = $admin_user['id'];
                        $_SESSION['username'] = $admin_user['username'];
                        $_SESSION['role'] = 'admin';
                        header('Location: admin_dashboard.php');
                        exit;
                    } else {
                        $error_message = "Invalid username or password.";
                    }
                } else {
                    $error_message = "Invalid username or password.";
                }
            } else {
                $error_message = "Login query failed.";
                error_log("Admin login execute error: " . $stmt->error);
            }
            $stmt->close();
        } else {
            $error_message = "Database error.";
            error_log("Admin login prepare error: " . $conn->error);
        }
    }
    if (isset($conn) && $conn instanceof mysqli && $conn->thread_id) {
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Escosia Dental Clinic</title>
    <link rel="stylesheet" href="admin_style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome for the eye icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="admin-login-page-new">

    <div class="login-form-container">
        <h2>Admin Login</h2>

        <?php if (!empty($error_message)): ?>
            <p class="login-error-message"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" novalidate>
            <div class="input-group">
                <input type="text" id="username" name="username" class="login-input" required 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                       autocomplete="username" placeholder="Username">
            </div>
            <div class="input-group">
                <!-- Password field with toggle -->
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" class="login-input" required
                           autocomplete="current-password" placeholder="Password">
                    <button type="button" class="toggle-password" aria-label="Show password">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <div class="forgot-password-link">
                <a href="#">Forgot Password?</a> <!-- Add actual link later -->
            </div>
            <button type="submit" class="btn-login">Login</button>
        </form>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const togglePasswordButtons = document.querySelectorAll('.toggle-password');

    togglePasswordButtons.forEach(button => {
        button.addEventListener('click', function () {
            // Find the input field within the same password-wrapper
            const passwordWrapper = this.closest('.password-wrapper');
            const passwordInput = passwordWrapper.querySelector('.login-input'); // Use specific class if needed
            const icon = this.querySelector('i');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
                this.setAttribute('aria-label', 'Hide password');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
                this.setAttribute('aria-label', 'Show password');
            }
        });
    });
});
</script>
</body>
</html>