<?php
require_once '../config.php';
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit;
}
$date_today = date('Y-m-d');
$sql = "SELECT a.id, a.appointment_date, a.appointment_time, a.status, a.service_type, a.notes, d.firstName AS dentist_first, d.lastName AS dentist_last, p.firstName AS patient_first, p.lastName AS patient_last FROM appointments a JOIN doctors d ON a.attending_dentist = d.id JOIN patients p ON a.patient_id = p.id WHERE a.appointment_date = ? ORDER BY a.appointment_time ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $date_today);
$stmt->execute();
$result = $stmt->get_result();
$appointments = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Today's Appointments - Admin</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .appointments-table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        .appointments-table th, .appointments-table td { padding: 12px 16px; border-bottom: 1px solid #e0e0e0; text-align: left; }
        .appointments-table th { background: #16a085; color: #fff; }
        .appointments-table tr:nth-child(even) { background: #f8f9fa; }
        .appointments-table tr:hover { background: #e0f7fa; }
        .status { font-weight: 600; padding: 4px 10px; border-radius: 6px; }
        .status.SCHEDULED { background: #e3fcec; color: #16a085; }
        .status.PENDING { background: #fff3cd; color: #b8860b; }
        .status.COMPLETED { background: #d4edda; color: #155724; }
        .status.CANCELLED { background: #f8d7da; color: #721c24; }

        .panel-page-title{
            font-size: 1.7rem;
            font-weight: 600;
            color: #16a085;
            margin-bottom: 20px;
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
                        <li><a href="system_settings.php" class="<?php echo ($current_page === 'system_settings.php') ? 'active' : ''; ?>"><i class="fas fa-cog"></i> System Settings</a></li>
                        <li><a href="reports_analytics.php" class="<?php echo ($current_page === 'reports_analytics.php') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Reports & Analytics</a></li>
                        <li><a href="data_management.php" class="<?php echo ($current_page === 'data_management.php') ? 'active' : ''; ?>"><i class="fas fa-database"></i> Data Management</a></li>
                        <li><a href="security_controls.php" class="<?php echo ($current_page === 'security_controls.php') ? 'active' : ''; ?>"><i class="fas fa-shield-alt"></i> Security Controls</a></li>
                        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </nav>
            </aside>
            <main class="admin-main-panel">
                <div class="main-panel-content-wrapper">
                    <h1 class="panel-page-title">Today's Appointments</h1>
                    <table class="appointments-table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Patient</th>
                                <th>Dentist</th>
                                <th>Service</th>
                                <th>Status</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($appointments)): ?>
                                <?php foreach ($appointments as $apt): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(date('h:i A', strtotime($apt['appointment_time']))); ?></td>
                                        <td><?php echo htmlspecialchars($apt['patient_first'] . ' ' . $apt['patient_last']); ?></td>
                                        <td><?php echo htmlspecialchars($apt['dentist_first'] . ' ' . $apt['dentist_last']); ?></td>
                                        <td><?php echo htmlspecialchars($apt['service_type']); ?></td>
                                        <td><span class="status <?php echo htmlspecialchars($apt['status']); ?>"><?php echo htmlspecialchars($apt['status']); ?></span></td>
                                        <td><?php echo htmlspecialchars($apt['notes']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" style="text-align:center; color:#8a6d3b; font-style:italic;">No appointments for today.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>
</body>
</html> 