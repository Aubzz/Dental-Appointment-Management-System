<?php
// reset_password.php
session_start(); // Start session potentially for flash messages later
require_once 'db_config.php'; // Includes DB connection

$token_from_url = null; // Token from GET parameter
$token_from_form = null; // Token from POST hidden field
$error_message = '';
$success_message = '';
$show_form = false;

// --- Handle POST Request (Form Submission) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token_from_form = $_POST['token'] ?? null;
    $new_password = $_POST['new_password'] ?? null;
    $confirm_password = $_POST['confirm_password'] ?? null;

    // Basic validation
    if (!$token_from_form || !$new_password || !$confirm_password) {
        $error_message = "Missing required form data. Please try the link again.";
    } elseif (strlen($new_password) < 8) { // Example: Enforce minimum password length
         $error_message = "Password must be at least 8 characters long.";
         $token_from_url = $token_from_form; // Keep token to redisplay form
         $show_form = true;
    } elseif ($new_password !== $confirm_password) {
        $error_message = "Passwords do not match.";
        $token_from_url = $token_from_form; // Keep token to redisplay form
        $show_form = true;
    } else {
        // Validate token and process password change
        $token_hash = hash('sha256', $token_from_form);
        $now = date('Y-m-d H:i:s');

        $sql_check = "SELECT id FROM users WHERE reset_token_hash = ? AND reset_token_expires_at > ?";
        if ($stmt_check = $mysqli->prepare($sql_check)) {
            $stmt_check->bind_param("ss", $token_hash, $now);
            if ($stmt_check->execute()) {
                $result = $stmt_check->get_result();
                if ($row = $result->fetch_assoc()) {
                    $user_id = $row['id']; // Get user ID associated with token
                    $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                    if ($new_hashed_password === false) {
                        $error_message = "Error processing new password.";
                        error_log("Password hashing failed during reset for user ID: " . $user_id);
                    } else {
                        // Update password and invalidate token (set to NULL)
                        $sql_update = "UPDATE users SET password = ?, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE id = ?";
                        if ($stmt_update = $mysqli->prepare($sql_update)) {
                            $stmt_update->bind_param("si", $new_hashed_password, $user_id);
                            if ($stmt_update->execute()) {
                                $success_message = "Your password has been successfully reset. You can now sign in.";
                                $show_form = false; // Don't show form on success
                            } else {
                                $error_message = "Failed to update password. Please try again."; error_log("Failed to update password for user ID: " . $user_id . " Error: " . $stmt_update->error);
                                $token_from_url = $token_from_form; $show_form = true; // Show form again on DB error
                            }
                            $stmt_update->close();
                        } else { $error_message = "Database error during password update preparation."; error_log("Failed to prepare password update statement: " . $mysqli->error); $token_from_url = $token_from_form; $show_form = true;}
                    }
                } else { $error_message = "Invalid or expired password reset token. Please request a new one."; } // Token not found or expired
                $result->free();
            } else { $error_message = "Error checking token validity."; error_log("Failed to execute token check statement: " . $stmt_check->error); }
            $stmt_check->close();
        } else { $error_message = "Database error preparing token check."; error_log("Failed to prepare token check statement: " . $mysqli->error); }
    }
    // Ensure connection is closed after POST processing
    if (isset($mysqli) && $mysqli instanceof mysqli) $mysqli->close();
}
// --- Handle GET Request (Link from Email) ---
elseif (isset($_GET['token'])) {
    $token_from_url = $_GET['token'];
    $token_hash = hash('sha256', $token_from_url);
    $now = date('Y-m-d H:i:s');

    $sql_check = "SELECT id FROM users WHERE reset_token_hash = ? AND reset_token_expires_at > ?";
    if ($stmt_check = $mysqli->prepare($sql_check)) {
        $stmt_check->bind_param("ss", $token_hash, $now);
        if ($stmt_check->execute()) {
            $result = $stmt_check->get_result();
            if ($result->num_rows === 1) { $show_form = true; } // Valid token, show form
            else { $error_message = "Invalid or expired password reset token."; }
            $result->free();
        } else { $error_message = "Error checking token validity."; error_log("Failed to execute token check statement: " . $stmt_check->error); }
        $stmt_check->close();
    } else { $error_message = "Database error preparing token check."; error_log("Failed to prepare token check statement: " . $mysqli->error); }
    // Close connection after GET processing
    if (isset($mysqli) && $mysqli instanceof mysqli) $mysqli->close();
} else {
    // No token in GET or POST
    $error_message = "Invalid password reset request.";
    if (isset($mysqli) && $mysqli instanceof mysqli) $mysqli->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/auth.css">
    <link rel="icon" href="images/favicon.png">
    <!-- Message styles embedded for simplicity or move to auth.css -->
    <style>
        .message { margin-top: 15px; font-weight: bold; padding: 10px; border-radius: 5px; }
        .success { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb;}
        .error { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb;}
    </style>
</head>
<body class="auth-page">
    <div class="signin-container"> <!-- Reuse container style -->
        <h1>Reset Your Password</h1>

        <?php if (!empty($error_message)): ?>
            <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php if (!$success_message): // Don't show request link if password was just successfully reset ?>
            <p style="margin-top: 20px;"><a href="forgot_password.html">Request a new reset link</a></p>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
            <p style="margin-top: 20px;"><a href="signin.html">Proceed to Sign In</a></p>
        <?php endif; ?>

        <?php if ($show_form && !empty($token_from_url) && empty($success_message)): ?>
            <form id="resetPasswordForm" method="POST" action="reset_password.php">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token_from_url); ?>">
                <div>
                    <label for="new_password">New Password (min. 8 characters)</label>
                    <input type="password" id="new_password" name="new_password" required minlength="8">
                </div>
                <div>
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                </div>
                <button type="submit">Reset Password</button>
            </form>
        <?php elseif (empty($error_message) && empty($success_message) && !$show_form): ?>
            <!-- Default message if somehow no other condition met, maybe token invalid on GET -->
             <div class="message error">Invalid request state or token.</div>
              <p style="margin-top: 20px;"><a href="forgot_password.html">Request a new reset link</a></p>
        <?php endif; ?>

    </div>
</body>
</html>