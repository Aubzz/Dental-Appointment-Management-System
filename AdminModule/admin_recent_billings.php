<?php
require_once '../config.php';
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit;
}
// Try to fetch from a billings or payments table
$billings = [];
$table_exists = $conn->query("SHOW TABLES LIKE 'billings'")->num_rows > 0;
if ($table_exists) {
    $result = $conn->query("SELECT id, patient_id, amount, status, billing_date FROM billings ORDER BY billing_date DESC LIMIT 20");
    if ($result) $billings = $result->fetch_all(MYSQLI_ASSOC);
} else {
    // No billings table exists, so no records are shown
    $billings = [];
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Recent Billings - Admin</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .table-responsive-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin-top: 15px;
            border: 1px solid var(--admin-light-border-color, #e0e0e0);
            border-radius: 6px;
            background-color: var(--admin-white, #fff);
        }

        .data-table.verification-table {
            font-size: 0.9em;
            min-width: 700px;
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }

        .data-table.verification-table thead th {
            background-color: var(--admin-very-light-green-bg, #f0f7f6);
            color: var(--admin-dark-green, #004d40);
            font-weight: 600;
            white-space: nowrap;
            padding: 10px 12px;
            text-align: left;
            border-bottom: 2px solid var(--admin-primary-green, #16a085);
        }

        .data-table.verification-table thead th:first-child {
            border-top-left-radius: 5px;
        }

        .data-table.verification-table thead th:last-child {
            border-top-right-radius: 5px;
        }

        .data-table.verification-table tbody td {
            padding: 9px 12px;
            vertical-align: middle;
            border-bottom: 1px solid var(--admin-light-border-color, #e0e0e0);
            color: var(--admin-text-muted, #555);
        }

        .data-table.verification-table tbody tr:last-child td {
            border-bottom: none;
        }

        .data-table.verification-table tbody tr:hover td {
            background-color: var(--admin-card-hover-bg, #e9f5f3);
        }

        .status.PAID {
            color: var(--admin-status-completed-text, #4caf50);
            font-weight: 500;
            background-color: var(--admin-status-completed-bg, #e8f5e9);
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.85em;
        }

        .status.PENDING {
            color: #b8860b;
            font-weight: 500;
            background-color: #fff3e0;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.85em;
        }

        .panel-page-title {
            font-size: 1.7rem;
            font-weight: 600;
            color: #16a085;
            margin-bottom: 20px;
        }

        .no-data {
            text-align: center;
            color: #666;
            padding: 20px;
            font-style: italic;
        }

        /* Scrollbar styling */
        .table-responsive-wrapper::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .table-responsive-wrapper::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .table-responsive-wrapper::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        .table-responsive-wrapper::-webkit-scrollbar-thumb:hover {
            background: #555;
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
                    <h1 class="panel-page-title">Recent Billings</h1>
                    <div class="table-responsive-wrapper">
                        <table class="data-table verification-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Patient</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($billings)): ?>
                                    <?php foreach ($billings as $bill): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($bill['id']); ?></td>
                                            <td><?php echo isset($bill['patient_name']) ? htmlspecialchars($bill['patient_name']) : htmlspecialchars($bill['patient_id']); ?></td>
                                            <td><?php echo htmlspecialchars($bill['amount']); ?></td>
                                            <td class="status <?php echo htmlspecialchars($bill['status']); ?>"><?php echo htmlspecialchars($bill['status']); ?></td>
                                            <td><?php echo htmlspecialchars($bill['billing_date']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" style="text-align:center; color:#8a6d3b; font-style:italic;">No billing records found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html> 