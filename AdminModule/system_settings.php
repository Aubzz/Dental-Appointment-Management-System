<?php
require_once '../config.php';
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit;
}
$current_page = basename($_SERVER['PHP_SELF']);

// --- Create settings table if not exists ---
$create_settings_sql = "CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(64) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL
)";
$conn->query($create_settings_sql);

// --- Helper: Get setting value ---
function get_setting($key, $default = '') {
    global $conn;
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) return $row['setting_value'];
    return $default;
}

// --- Helper: Set setting value ---
function set_setting($key, $value) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->bind_param('sss', $key, $value, $value);
    $stmt->execute();
}

// --- Fetch current settings ---
$clinic_name = get_setting('clinic_name', 'Escosia Dental Clinic');
$clinic_address = get_setting('clinic_address', '123 Main St, City');
$clinic_phone = get_setting('clinic_phone', '(123) 456-7890');
$clinic_email = get_setting('clinic_email', 'info@escosiadental.com');
$hours_weekdays = get_setting('hours_weekdays', '08:00 AM - 05:00 PM');
$hours_saturday = get_setting('hours_saturday', '09:00 AM - 01:00 PM');
$hours_sunday = get_setting('hours_sunday', 'Closed');

// --- Feedback message ---
$feedback = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_clinic_info'])) {
        set_setting('clinic_name', $_POST['clinic_name']);
        set_setting('clinic_address', $_POST['clinic_address']);
        set_setting('clinic_phone', $_POST['clinic_phone']);
        set_setting('clinic_email', $_POST['clinic_email']);
        $feedback = '<div class="form-feedback-message success">Clinic information updated successfully.</div>';
        // Update local variables for immediate display
        $clinic_name = $_POST['clinic_name'];
        $clinic_address = $_POST['clinic_address'];
        $clinic_phone = $_POST['clinic_phone'];
        $clinic_email = $_POST['clinic_email'];
    } elseif (isset($_POST['save_working_hours'])) {
        set_setting('hours_weekdays', $_POST['hours_weekdays']);
        set_setting('hours_saturday', $_POST['hours_saturday']);
        set_setting('hours_sunday', $_POST['hours_sunday']);
        $feedback = '<div class="form-feedback-message success">Working hours updated successfully.</div>';
        $hours_weekdays = $_POST['hours_weekdays'];
        $hours_saturday = $_POST['hours_saturday'];
        $hours_sunday = $_POST['hours_sunday'];
    } elseif (isset($_POST['add_holiday'])) {
        $feedback = '<div class="form-feedback-message success">Holiday/closure added.</div>';
    } elseif (isset($_POST['save_notifications'])) {
        $feedback = '<div class="form-feedback-message success">Notification settings updated.</div>';
    } elseif (isset($_POST['backup_now'])) {
        // Simulate backup download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="clinic_backup.sql"');
        echo '-- SQL BACKUP DUMMY FILE --';
        exit;
    } elseif (isset($_POST['restore_backup'])) {
        $feedback = '<div class="form-feedback-message success">Backup restored (simulated).</div>';
    } elseif (isset($_POST['change_password'])) {
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if ($new && $new === $confirm) {
            $feedback = '<div class="form-feedback-message success">Password changed successfully (simulated).</div>';
        } else {
            $feedback = '<div class="form-feedback-message error">Passwords do not match.</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Escosia Dental Clinic</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body.admin-layout-page {
            background: #f4f7f6;
        }
        .settings-section {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(22,160,133,0.07);
            padding: 32px 36px 28px 36px;
            margin-bottom: 32px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        .settings-section h2 {
            color: #16a085;
            font-size: 1.3em;
            font-weight: 700;
            margin-bottom: 18px;
            letter-spacing: 0.5px;
        }
        .settings-section form label {
            display: block;
            margin-bottom: 12px;
            font-weight: 500;
            color: #2c3e50;
        }
        .settings-section input[type="text"],
        .settings-section input[type="email"],
        .settings-section input[type="password"],
        .settings-section input[type="date"] {
            width: 100%;
            padding: 10px 14px;
            border-radius: 8px;
            border: 1.5px solid #e0f2f1;
            background: #f8f9fa;
            font-size: 1em;
            margin-top: 4px;
            margin-bottom: 8px;
            transition: border-color 0.2s;
        }
        .settings-section input:focus {
            border-color: #16a085;
            outline: none;
            background: #e8f8f5;
        }
        .settings-section button.btn {
            padding: 10px 28px;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 600;
            border: none;
            margin-top: 10px;
            margin-right: 10px;
            background: linear-gradient(90deg, #16a085 60%, #1abc9c 100%);
            color: #fff;
            box-shadow: 0 2px 8px rgba(22,160,133,0.08);
            cursor: pointer;
            opacity: 1;
            min-width: 160px;
            height: 40px;
        }
        .settings-section button.btn-secondary {
            background: #e0f2f1;
            color: #16a085;
            border: 1.5px solid #16a085;
            box-shadow: none;
        }
        .settings-section ul {
            margin: 0;
            padding-left: 18px;
            color: #555;
            font-size: 0.98em;
        }
        .panel-page-title {
            text-align: center;
            color: #0A744F;
            font-size: 2em;
            font-weight: 700;
            margin-top: 30px;
            margin-bottom: 10px;
            letter-spacing: 1px;
        }
        .main-panel-content-wrapper > .panel-intro-text {
            text-align: center;
            color: #388e5c;
            font-size: 1.1em;
            margin-bottom: 32px;
        }
        .form-feedback-message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 0.95em;
            text-align: center;
        }
        .form-feedback-message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .form-feedback-message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        @media (max-width: 900px) {
            .settings-section {
                padding: 18px 8px 18px 8px;
            }
        }
        .custom-file-input-wrapper {
            position: relative;
            display: inline-block;
            width: 250px;
            margin-top: 10px;
        }
        .custom-file-input {
            opacity: 0;
            width: 100%;
            height: 40px;
            position: absolute;
            left: 0;
            top: 0;
            cursor: pointer;
            z-index: 2;
        }
        .custom-file-label {
            display: inline-block;
            background: #e0f2f1;
            color: #16a085;
            border: 1.5px solid #16a085;
            border-radius: 8px;
            padding: 10px 28px;
            font-size: 1em;
            font-weight: 600;
            text-align: center;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
            z-index: 1;
            position: relative;
            min-width: 160px;
            height: 40px;
            line-height: 20px;
        }
        .custom-file-label.selected {
            background: #d4f9e5;
            color: #0A744F;
        }
        .custom-file-filename {
            display: block;
            margin-top: 6px;
            color: #388e5c;
            font-size: 0.98em;
            font-style: italic;
        }
        .feedback-message {
            margin: 0 auto 1.5rem auto;
            max-width: 700px;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            font-size: 1.08rem;
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            text-align: center;
            z-index: 100;
        }
        .feedback-message.alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1.5px solid #b2dfdb;
        }
        .feedback-message.alert-error {
            background: #ffebee;
            color: #c62828;
            border: 1.5px solid #ffcdd2;
        }
        /* Custom Checkbox Styles */
        .custom-checkbox {
            position: relative;
            display: inline-flex;
            align-items: center;
            cursor: pointer;
            font-size: 1em;
        }
        .custom-checkbox input[type="checkbox"] {
            opacity: 0;
            position: absolute;
            left: 0;
            top: 0;
            width: 22px;
            height: 22px;
            margin: 0;
            cursor: pointer;
        }
        .custom-checkbox .checkmark {
            width: 22px;
            height: 22px;
            border: 2px solid #16a085;
            border-radius: 6px;
            background: #fff;
            margin-right: 10px;
            display: inline-block;
            position: relative;
            transition: border-color 0.2s, background 0.2s;
            
        }
        .custom-checkbox input[type="checkbox"]:checked + .checkmark {
            background: #16a085;
            border-color: #16a085;
            
        }
        .custom-checkbox .checkmark:after {
            content: '';
            position: absolute;
            display: none;
        }
        .custom-checkbox input[type="checkbox"]:checked + .checkmark:after {
            display: block;
        }
        .custom-checkbox .checkmark:after {
            left: 6px;
            top: 2px;
            width: 6px;
            height: 12px;
            border: solid #fff;
            border-width: 0 3px 3px 0;
            transform: rotate(45deg);
        }
        .custom-checkbox-label {
            user-select: none;
            color: #138d75;
            font-weight: 500;
        }
        .notification-checkbox-label {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            cursor: pointer;
            font-size: 1.25rem;
            color: #179187;
            margin-bottom: 24px;
        }
        .notification-checkbox-label .checkbox-custom {
            margin-top: 2px;
        }
        .notification-checkbox-label .checkbox-text {
            margin-top: -2px;
            line-height: 1.1;
        }
    </style>
</head>
<body class="admin-layout-page">
    <div class="admin-page-wrapper">
        <header class="admin-top-header">
            <div class="header-logo-title">
                <img src="../images/tooth.png" alt="Clinic Logo" class="logo-icon">
                <span class="clinic-name">Escosia Dental Clinic</span>
            </div>
            <div class="header-user-actions">
                <a href="#" class="notification-bell-link"><i class="fas fa-bell"></i><span class="notification-dot"></span></a>
                <div class="user-profile-info">
                    <span class="user-name"><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Admin User'; ?></span>
                    <span class="user-role-display">Admin</span>
                </div>
            </div>
        </header>
        <div class="admin-body-content">
            <aside class="admin-sidebar">
                <nav class="sidebar-nav">
                    <ul>
                        <li><a href="admin_dashboard.php" class="<?php echo ($current_page === 'admin_dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                        <li><a href="admin_user_management.php" class="<?php echo ($current_page === 'admin_user_management.php') ? 'active' : ''; ?>"><i class="fas fa-user-check"></i> User Management</a></li>
                        <li><a href="appointments.php" class="<?php echo ($current_page === 'appointments.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Appointments</a></li>
                        <li><a href="system_settings.php" class="active"><i class="fas fa-cog"></i> System Settings</a></li>
                        <li><a href="reports_analytics.php" class="<?php echo ($current_page === 'reports_analytics.php') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Reports & Analytics</a></li>
                        <li><a href="data_management.php" class="<?php echo ($current_page === 'data_management.php') ? 'active' : ''; ?>"><i class="fas fa-database"></i> Data Management</a></li>
                        <li><a href="security_controls.php" class="<?php echo ($current_page === 'security_controls.php') ? 'active' : ''; ?>"><i class="fas fa-shield-alt"></i> Security Controls</a></li>
                        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </nav>
            </aside>
            <main class="admin-main-panel">
                <div class="main-panel-content-wrapper">
                    <h1 class="panel-page-title">System Settings</h1>
                    <?php if (!empty($feedback)): ?>
                        <div class="feedback-message <?php echo strpos($feedback, 'success') !== false ? 'alert-success' : 'alert-error'; ?>"><?php echo $feedback; ?></div>
                    <?php endif; ?>
                    <div class="settings-section">
                        <h2>Clinic Information</h2>
                        <form method="post">
                            <label>Clinic Name: <input type="text" name="clinic_name" class="form-control" value="<?php echo htmlspecialchars($clinic_name); ?>"></label><br>
                            <label>Address: <input type="text" name="clinic_address" class="form-control" value="<?php echo htmlspecialchars($clinic_address); ?>"></label><br>
                            <label>Phone: <input type="text" name="clinic_phone" class="form-control" value="<?php echo htmlspecialchars($clinic_phone); ?>"></label><br>
                            <label>Email: <input type="email" name="clinic_email" class="form-control" value="<?php echo htmlspecialchars($clinic_email); ?>"></label><br>
                            <button type="submit" name="save_clinic_info" class="btn btn-primary">Save Changes</button>
                        </form>
                    </div>
                    <div class="settings-section">
                        <h2>Working Hours</h2>
                        <form method="post">
                            <label>Monday-Friday: <input type="text" name="hours_weekdays" class="form-control" value="<?php echo htmlspecialchars($hours_weekdays); ?>"></label><br>
                            <label>Saturday: <input type="text" name="hours_saturday" class="form-control" value="<?php echo htmlspecialchars($hours_saturday); ?>"></label><br>
                            <label>Sunday: <input type="text" name="hours_sunday" class="form-control" value="<?php echo htmlspecialchars($hours_sunday); ?>"></label><br>
                            <button type="submit" name="save_working_hours" class="btn btn-primary">Save Changes</button>
                        </form>
                    </div>
                    <div class="settings-section">
                        <h2>Holidays & Closures</h2>
                        <form method="post">
                            <label>Add Holiday/Closure: <input type="date" name="holiday_date" class="form-control"></label>
                            <button type="submit" name="add_holiday" class="btn btn-secondary">Add</button>
                        </form>
                        <ul style="margin-top:10px;">
                            <li>2024-12-25 (Christmas Day)</li>
                            <li>2024-01-01 (New Year's Day)</li>
                        </ul>
                    </div>
                    <div class="settings-section">
                        <h2>Notification Settings</h2>
                        <form method="post">
                            <label class="notification-checkbox-label">
                                <span class="custom-checkbox">
                                    <input type="checkbox" name="notif_patients" checked>
                                    <span class="checkmark"></span>
                                </span>
                                <span class="checkbox-text">Email notifications to patients</span>
                            </label><br>
                            <label class="notification-checkbox-label">
                                <span class="custom-checkbox">
                                    <input type="checkbox" name="notif_staff" checked>
                                    <span class="checkmark"></span>
                                </span>
                                <span class="checkbox-text">Email notifications to staff</span>
                            </label><br>
                            <label class="notification-checkbox-label">
                                <span class="custom-checkbox">
                                    <input type="checkbox" name="notif_sms">
                                    <span class="checkmark"></span>
                                </span>
                                <span class="checkbox-text">SMS notifications (coming soon)</span>
                            </label><br>
                            <button type="submit" name="save_notifications" class="btn btn-primary">Save Changes</button>
                        </form>
                    </div>
                    <div class="settings-section">
                        <h2>Change Password</h2>
                        <form method="post">
                            <label>Current Password: <input type="password" name="current_password" class="form-control"></label><br>
                            <label>New Password: <input type="password" name="new_password" class="form-control"></label><br>
                            <label>Confirm New Password: <input type="password" name="confirm_password" class="form-control"></label><br>
                            <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var feedback = document.querySelector('.feedback-message');
        if (feedback) {
            setTimeout(function() {
                feedback.style.display = 'none';
            }, 3500);
        }
    });
    </script>
</body>
</html> 