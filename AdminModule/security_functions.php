<?php
require_once '../config.php';

/**
 * Get security settings from database
 * @return array Array of security settings
 */
function getSecuritySettings() {
    global $conn;
    $settings = [];
    $result = $conn->query("SELECT setting_name, setting_value FROM security_settings");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_name']] = $row['setting_value'];
        }
    }
    return $settings;
}

/**
 * Validate password against security policy
 * @param string $password Password to validate
 * @return array ['valid' => bool, 'message' => string]
 */
function validatePassword($password) {
    $settings = getSecuritySettings();
    $min_length = (int)($settings['min_length'] ?? 8);
    $complexity = $settings['complexity'] ?? 'medium';
    
    if (strlen($password) < $min_length) {
        return [
            'valid' => false,
            'message' => "Password must be at least {$min_length} characters long."
        ];
    }
    
    switch ($complexity) {
        case 'high':
            if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{' . $min_length . ',}$/', $password)) {
                return [
                    'valid' => false,
                    'message' => 'Password must contain uppercase, lowercase, numbers, and special characters.'
                ];
            }
            break;
        case 'medium':
            if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{' . $min_length . ',}$/', $password)) {
                return [
                    'valid' => false,
                    'message' => 'Password must contain uppercase, lowercase, and numbers.'
                ];
            }
            break;
        case 'low':
            if (!preg_match('/^[A-Za-z]{' . $min_length . ',}$/', $password)) {
                return [
                    'valid' => false,
                    'message' => 'Password must contain only letters.'
                ];
            }
            break;
    }
    
    return ['valid' => true, 'message' => 'Password is valid.'];
}

/**
 * Check if user is locked out
 * @param int $admin_id Admin user ID
 * @return bool True if user is locked out
 */
function isUserLockedOut($admin_id) {
    global $conn;
    $settings = getSecuritySettings();
    $lockout_duration = (int)($settings['lockout_duration'] ?? 15);
    
    $stmt = $conn->prepare("SELECT COUNT(*) as failed_attempts 
                           FROM admin_login_history 
                           WHERE admin_id = ? 
                           AND status = 'failed' 
                           AND login_time > DATE_SUB(NOW(), INTERVAL ? MINUTE)");
    $stmt->bind_param("ii", $admin_id, $lockout_duration);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $max_attempts = (int)($settings['lockout_attempts'] ?? 5);
    return $row['failed_attempts'] >= $max_attempts;
}

/**
 * Record login attempt
 * @param int $admin_id Admin user ID
 * @param string $status 'success' or 'failed'
 * @return void
 */
function recordLoginAttempt($admin_id, $status) {
    global $conn;
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    $stmt = $conn->prepare("INSERT INTO admin_login_history (admin_id, ip_address, user_agent, status) 
                           VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $admin_id, $ip_address, $user_agent, $status);
    $stmt->execute();
}

/**
 * Check if password needs to be changed
 * @param int $admin_id Admin user ID
 * @return bool True if password needs to be changed
 */
function needsPasswordChange($admin_id) {
    global $conn;
    $settings = getSecuritySettings();
    
    if (($settings['require_change'] ?? '0') === '0') {
        return false;
    }
    
    $stmt = $conn->prepare("SELECT last_password_change FROM admins WHERE id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if (!$row['last_password_change']) {
        return true;
    }
    
    $last_change = new DateTime($row['last_password_change']);
    $now = new DateTime();
    $diff = $now->diff($last_change);
    
    return $diff->days >= 90;
}

/**
 * Update last password change timestamp
 * @param int $admin_id Admin user ID
 * @return void
 */
function updatePasswordChangeTimestamp($admin_id) {
    global $conn;
    $stmt = $conn->prepare("UPDATE admins SET last_password_change = NOW() WHERE id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
}

/**
 * Check if session has expired
 * @return bool True if session has expired
 */
function isSessionExpired() {
    $settings = getSecuritySettings();
    $timeout = (int)($settings['session_timeout'] ?? 30);
    
    if (!isset($_SESSION['last_activity'])) {
        return false;
    }
    
    $last_activity = new DateTime($_SESSION['last_activity']);
    $now = new DateTime();
    $diff = $now->diff($last_activity);
    
    return ($diff->i + ($diff->h * 60)) >= $timeout;
}

/**
 * Update session activity timestamp
 * @return void
 */
function updateSessionActivity() {
    $_SESSION['last_activity'] = date('Y-m-d H:i:s');
} 