<?php
require_once '../config.php';
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit;
}

// Create security_settings table if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS security_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_name VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
$conn->query($create_table_sql);

// Create admin_login_history table if it doesn't exist
$create_history_sql = "CREATE TABLE IF NOT EXISTS admin_login_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    status ENUM('success', 'failed') NOT NULL,
    FOREIGN KEY (admin_id) REFERENCES admins(id)
)";
$conn->query($create_history_sql);

// Handle form submissions
$feedback = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['password_policy'])) {
        $min_length = (int)$_POST['minLength'];
        $complexity = $_POST['complexity'];
        $require_change = isset($_POST['require_change']) ? '1' : '0';
        
        // Update password policy settings
        $settings = [
            'min_length' => $min_length,
            'complexity' => $complexity,
            'require_change' => $require_change
        ];
        
        foreach ($settings as $name => $value) {
            $stmt = $conn->prepare("INSERT INTO security_settings (setting_name, setting_value) 
                                  VALUES (?, ?) 
                                  ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->bind_param("sss", $name, $value, $value);
            $stmt->execute();
        }
        
        $feedback = '<div class="alert alert-success">Password policy updated successfully.</div>';
    }
    
    if (isset($_POST['lockout_settings'])) {
        $attempts = (int)$_POST['lockoutAttempts'];
        $duration = (int)$_POST['lockoutDuration'];
        
        // Update lockout settings
        $settings = [
            'lockout_attempts' => $attempts,
            'lockout_duration' => $duration
        ];
        
        foreach ($settings as $name => $value) {
            $stmt = $conn->prepare("INSERT INTO security_settings (setting_name, setting_value) 
                                  VALUES (?, ?) 
                                  ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->bind_param("sss", $name, $value, $value);
            $stmt->execute();
        }
        
        $feedback = '<div class="alert alert-success">Lockout settings updated successfully.</div>';
    }
    
    if (isset($_POST['session_timeout'])) {
        $timeout = (int)$_POST['sessionTimeout'];
        
        // Update session timeout
        $stmt = $conn->prepare("INSERT INTO security_settings (setting_name, setting_value) 
                              VALUES ('session_timeout', ?) 
                              ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->bind_param("ss", $timeout, $timeout);
        $stmt->execute();
        
        $feedback = '<div class="alert alert-success">Session timeout updated successfully.</div>';
    }
}

// Fetch current settings
$settings = [];
$result = $conn->query("SELECT setting_name, setting_value FROM security_settings");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_name']] = $row['setting_value'];
    }
}

// Fetch recent admin logins
$recent_logins = [];
$login_query = "SELECT h.*, a.username 
               FROM admin_login_history h 
               JOIN admins a ON h.admin_id = a.id 
               ORDER BY h.login_time DESC 
               LIMIT 10";
$login_result = $conn->query($login_query);
if ($login_result) {
    while ($row = $login_result->fetch_assoc()) {
        $recent_logins[] = $row;
    }
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Controls - Escosia Dental Clinic</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .card-section {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            padding: 2rem 2.5rem 1.5rem 2.5rem;
            margin-bottom: 2rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        .card-section h3 {
            color: #16a085;
            margin-bottom: 1.2rem;
            font-size: 1.25rem;
            font-weight: 600;
        }
        .form-row {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            margin-bottom: 1.2rem;
        }
        .form-group {
            flex: 1 1 220px;
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            font-weight: 500;
            margin-bottom: 0.4rem;
            color: #138d75;
        }
        .form-group input[type="number"],
        .form-group input[type="text"],
        .form-group select {
            padding: 0.6rem 0.9rem;
            border: 1.5px solid #b2dfdb;
            border-radius: 6px;
            font-size: 1rem;
            background: #f8f9fa;
            margin-bottom: 0.2rem;
        }
        .form-group input[type="checkbox"] {
            margin-right: 0.5rem;
        }
        .btn-main {
            background: #16a085;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 0.7rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-main:hover {
            background: #138d75;
        }
        .recent-logins-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.2rem;
        }
        .recent-logins-table th, .recent-logins-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }
        .recent-logins-table th {
            background: #f8f9fa;
            color: #138d75;
            font-weight: 600;
        }
        .recent-logins-table tbody tr:hover {
            background: #f1f1f1;
        }
        @media (max-width: 600px) {
            .card-section {
                padding: 1.2rem 0.7rem 1rem 0.7rem;
            }
            .form-row {
                flex-direction: column;
                gap: 0.7rem;
            }
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
    </style>
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
                        <li><a href="system_settings.php" class="<?php echo ($current_page === 'system_settings.php') ? 'active' : ''; ?>"><i class="fas fa-cog"></i> System Settings</a></li>
                        <li><a href="reports_analytics.php" class="<?php echo ($current_page === 'reports_analytics.php') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Reports & Analytics</a></li>
                        <li><a href="data_management.php" class="<?php echo ($current_page === 'data_management.php') ? 'active' : ''; ?>"><i class="fas fa-database"></i> Data Management</a></li>
                        <li><a href="security_controls.php" class="active"><i class="fas fa-shield-alt"></i> Security Controls</a></li>
                        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </nav>
            </aside>
            <main class="admin-main-panel">
                <div class="main-panel-content-wrapper">
                    <h1 class="panel-page-title">Security Controls</h1>
                    <p class="panel-intro-text">Manage security settings and monitor recent admin activity.</p>

                    <?php if (!empty($feedback)): ?>
                        <div class="feedback-message <?php echo strpos(
$feedback, 'success') !== false ? 'alert-success' : 'alert-error'; ?>"><?php echo $feedback; ?></div>
                    <?php endif; ?>

                    <div class="card-section">
                        <h3>Password Policy</h3>
                        <form method="POST" action="">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="minLength">Minimum Length</label>
                                    <input type="number" id="minLength" name="minLength" min="6" max="32" 
                                           value="<?php echo htmlspecialchars($settings['min_length'] ?? '8'); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="complexity">Complexity</label>
                                    <select id="complexity" name="complexity">
                                        <option value="low" <?php echo ($settings['complexity'] ?? '') === 'low' ? 'selected' : ''; ?>>Low (letters only)</option>
                                        <option value="medium" <?php echo ($settings['complexity'] ?? '') === 'medium' ? 'selected' : ''; ?>>Medium (letters & numbers)</option>
                                        <option value="high" <?php echo ($settings['complexity'] ?? '') === 'high' ? 'selected' : ''; ?>>High (letters, numbers, symbols)</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label><input type="checkbox" name="require_change" <?php echo ($settings['require_change'] ?? '') === '1' ? 'checked' : ''; ?>> Require password change every 90 days</label>
                                </div>
                            </div>
                            <button class="btn-main" type="submit" name="password_policy"><i class="fas fa-save"></i> Save Policy</button>
                        </form>
                    </div>

                    <div class="card-section">
                        <h3>Account Lockout</h3>
                        <form method="POST" action="">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="lockoutAttempts">Failed Attempts Before Lockout</label>
                                    <input type="number" id="lockoutAttempts" name="lockoutAttempts" min="3" max="10" 
                                           value="<?php echo htmlspecialchars($settings['lockout_attempts'] ?? '5'); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="lockoutDuration">Lockout Duration (minutes)</label>
                                    <input type="number" id="lockoutDuration" name="lockoutDuration" min="1" max="60" 
                                           value="<?php echo htmlspecialchars($settings['lockout_duration'] ?? '15'); ?>">
                                </div>
                            </div>
                            <button class="btn-main" type="submit" name="lockout_settings"><i class="fas fa-save"></i> Save Lockout Settings</button>
                        </form>
                    </div>

                    <div class="card-section">
                        <h3>Session Timeout</h3>
                        <form method="POST" action="">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="sessionTimeout">Session Timeout (minutes)</label>
                                    <input type="number" id="sessionTimeout" name="sessionTimeout" min="5" max="120" 
                                           value="<?php echo htmlspecialchars($settings['session_timeout'] ?? '30'); ?>">
                                </div>
                            </div>
                            <button class="btn-main" type="submit" name="session_timeout"><i class="fas fa-save"></i> Save Timeout</button>
                        </form>
                    </div>

                    <div class="card-section">
                        <h3>Recent Admin Logins</h3>
                        <table class="recent-logins-table">
                            <thead>
                                <tr>
                                    <th>Admin</th>
                                    <th>Login Time</th>
                                    <th>IP Address</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_logins as $login): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($login['username']); ?></td>
                                    <td><?php echo date('M d, Y H:i:s', strtotime($login['login_time'])); ?></td>
                                    <td><?php echo htmlspecialchars($login['ip_address']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $login['status']; ?>">
                                            <?php echo ucfirst($login['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="card-section" style="text-align:center;">
                        <button class="btn-main" style="background:#c62828;"><i class="fas fa-sign-out-alt"></i> Force Logout All Users</button>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html> 