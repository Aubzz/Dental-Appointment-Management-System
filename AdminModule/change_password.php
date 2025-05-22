<?php
require_once '../config.php';
require_once 'security_functions.php';

session_start();

// Check if user is forced to change password
if (!isset($_SESSION['force_password_change']) || !isset($_SESSION['temp_admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Get admin user
    $stmt = $conn->prepare("SELECT id, password_hash FROM admins WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['temp_admin_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    
    // Verify current password
    if (!password_verify($current_password, $admin['password_hash'])) {
        $error = 'Current password is incorrect.';
    } else {
        // Validate new password
        $validation = validatePassword($new_password);
        if (!$validation['valid']) {
            $error = $validation['message'];
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match.';
        } else {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE admins SET password_hash = ?, last_password_change = NOW() WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $admin['id']);
            
            if ($stmt->execute()) {
                // Clear temporary session data
                unset($_SESSION['force_password_change']);
                unset($_SESSION['temp_admin_id']);
                
                // Set proper session data
                $_SESSION['loggedin'] = true;
                $_SESSION['id'] = $admin['id'];
                $_SESSION['role'] = 'admin';
                $_SESSION['last_activity'] = date('Y-m-d H:i:s');
                
                $success = 'Password changed successfully. Redirecting...';
                header('refresh:2;url=admin_dashboard.php');
            } else {
                $error = 'Failed to update password. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Escosia Dental Clinic</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .change-password-container {
            max-width: 500px;
            margin: 100px auto;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .change-password-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .change-password-header img {
            width: 80px;
            margin-bottom: 1rem;
        }
        .change-password-header h1 {
            color: #16a085;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        .change-password-form {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .form-group label {
            color: #2c3e50;
            font-weight: 500;
        }
        .form-group input {
            padding: 0.8rem;
            border: 1.5px solid #e0e0e0;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        .form-group input:focus {
            border-color: #16a085;
            outline: none;
        }
        .submit-btn {
            background: #16a085;
            color: white;
            border: none;
            padding: 0.8rem;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }
        .submit-btn:hover {
            background: #138d75;
        }
        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 0.8rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            text-align: center;
        }
        .success-message {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 0.8rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            text-align: center;
        }
        .password-requirements {
            background: #f5f5f5;
            padding: 1rem;
            border-radius: 6px;
            margin-top: 1rem;
        }
        .password-requirements h3 {
            color: #2c3e50;
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }
        .password-requirements ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .password-requirements li {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .password-requirements li i {
            color: #16a085;
        }
    </style>
</head>
<body>
    <div class="change-password-container">
        <div class="change-password-header">
            <img src="../images/tooth.png" alt="Clinic Logo">
            <h1>Change Password</h1>
            <p>Your password must be changed before continuing</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <form class="change-password-form" method="POST" action="">
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
            
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <div class="password-requirements">
                <h3>Password Requirements:</h3>
                <ul>
                    <?php
                    $settings = getSecuritySettings();
                    $min_length = (int)($settings['min_length'] ?? 8);
                    $complexity = $settings['complexity'] ?? 'medium';
                    ?>
                    <li><i class="fas fa-check-circle"></i> Minimum <?php echo $min_length; ?> characters</li>
                    <?php if ($complexity === 'medium' || $complexity === 'high'): ?>
                        <li><i class="fas fa-check-circle"></i> Must contain uppercase and lowercase letters</li>
                        <li><i class="fas fa-check-circle"></i> Must contain numbers</li>
                    <?php endif; ?>
                    <?php if ($complexity === 'high'): ?>
                        <li><i class="fas fa-check-circle"></i> Must contain special characters</li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <button type="submit" class="submit-btn">Change Password</button>
        </form>
    </div>
</body>
</html> 