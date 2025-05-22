<?php
require_once '../config.php';
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit;
}
$current_page = basename($_SERVER['PHP_SELF']);
// Fetch stats
$total_appointments = $completed_appointments = $cancelled_appointments = $patient_count = 0;
$res = $conn->query("SELECT COUNT(*) as total FROM appointments");
if ($res && $row = $res->fetch_assoc()) $total_appointments = $row['total'];
$res = $conn->query("SELECT COUNT(*) as completed FROM appointments WHERE status='COMPLETED'");
if ($res && $row = $res->fetch_assoc()) $completed_appointments = $row['completed'];
$res = $conn->query("SELECT COUNT(*) as cancelled FROM appointments WHERE status='CANCELLED'");
if ($res && $row = $res->fetch_assoc()) $cancelled_appointments = $row['cancelled'];
$res = $conn->query("SELECT COUNT(*) as total FROM patients");
if ($res && $row = $res->fetch_assoc()) $patient_count = $row['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Escosia Dental Clinic</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
                        <li><a href="reports_analytics.php" class="active"><i class="fas fa-chart-line"></i> Reports & Analytics</a></li>
                        <li><a href="data_management.php" class="<?php echo ($current_page === 'data_management.php') ? 'active' : ''; ?>"><i class="fas fa-database"></i> Data Management</a></li>
                        <li><a href="security_controls.php" class="<?php echo ($current_page === 'security_controls.php') ? 'active' : ''; ?>"><i class="fas fa-shield-alt"></i> Security Controls</a></li>
                        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </nav>
            </aside>
            <main class="admin-main-panel">
                <div class="main-panel-content-wrapper">
                    <h1 class="panel-page-title">Reports & Analytics</h1>
                    <div class="admin-summary-cards">
                        <div class="summary-card"><div class="card-icon"><i class="fas fa-calendar-check"></i></div><div class="card-content"><h3>Total Appointments</h3><p><?php echo $total_appointments; ?></p></div></div>
                        <div class="summary-card"><div class="card-icon"><i class="fas fa-check-circle"></i></div><div class="card-content"><h3>Completed</h3><p><?php echo $completed_appointments; ?></p></div></div>
                        <div class="summary-card"><div class="card-icon"><i class="fas fa-times-circle"></i></div><div class="card-content"><h3>Cancelled</h3><p><?php echo $cancelled_appointments; ?></p></div></div>
                        <div class="summary-card"><div class="card-icon"><i class="fas fa-users"></i></div><div class="card-content"><h3>Total Patients</h3><p><?php echo $patient_count; ?></p></div></div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html> 