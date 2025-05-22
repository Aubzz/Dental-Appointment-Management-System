<?php
require_once '../config.php';

if (!isset($_SESSION['loggedin']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit;
}
$current_page = basename($_SERVER['PHP_SELF']); // This will be 'admin_dashboard.php'
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Escosia Dental Clinic</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .action-row {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 0.5rem;
        }
        .btn-main, .btn-outline {
            padding: 0.7rem 1.5rem;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
            min-width: 150px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .btn-main {
            background: #16a085;
            color: #fff;
        }
        .btn-main:hover {
            background: #138d75;
        }
        .btn-outline {
            background: #eafaf1;
            color: #16a085;
            border: 1.5px solid #16a085;
        }
        .btn-outline:hover {
            background: #d1f2eb;
        }
        .custom-file-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .custom-file-input {
            opacity: 0;
            position: absolute;
            left: 0;
            top: 0;
            width: 150px;
            height: 44px;
            cursor: pointer;
            z-index: 2;
        }
        .custom-file-label {
            background: #eafaf1;
            color: #16a085;
            border: 1.5px solid #16a085;
            border-radius: 6px;
            padding: 0.7rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            min-width: 150px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: background 0.2s, color 0.2s;
        }
        .custom-file-label:hover {
            background: #d1f2eb;
        }
        .file-name-display {
            font-size: 0.97rem;
            color: #888;
            margin-left: 0.5rem;
            min-width: 120px;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        @media (max-width: 700px) {
            .action-row {
                flex-direction: column;
                align-items: stretch;
                gap: 0.7rem;
            }
            .custom-file-label, .btn-main, .btn-outline {
                width: 100%;
                min-width: unset;
            }
            .file-name-display {
                max-width: 100%;
            }
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
                        <li>
                            <a href="admin_dashboard.php" class="<?php echo ($current_page === 'admin_dashboard.php') ? 'active' : ''; ?>">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li>
                            <a href="admin_user_management.php" class="<?php echo ($current_page === 'admin_user_management.php') ? 'active' : ''; ?>">
                                <i class="fas fa-user-check"></i> User Management
                            </a>
                        </li>
                        <li><a href="appointments.php" class="<?php echo ($current_page === 'appointments.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Appointments</a></li>
                        <li><a href="system_settings.php" class="<?php echo ($current_page === 'security_settings.php') ? 'active' : ''; ?>"><i class="fas fa-cog"></i> System Settings</a></li>
                        <li><a href="reports_analysis.php" class="<?php echo ($current_page === 'reports_analysis.php') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Reports & Analytics</a></li>
                        <li><a href="data_management.php" class="<?php echo ($current_page === 'data_management.php') ? 'active' : ''; ?>"><i class="fas fa-database"></i> Data Management</a></li>
                        <li><a href="security_controls.php" class="<?php echo ($current_page === 'security_controls.php') ? 'active' : ''; ?>"><i class="fas fa-shield-alt"></i> Security Controls</a></li>
                        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </nav>
            </aside>

            <main class="admin-main-panel">
                <div class="main-panel-content-wrapper">
                    <h1 class="panel-page-title">Dashboard</h1>
                    <p class="panel-intro-text">Overview of clinic activity and management tools.</p>

                    <div class="admin-summary-cards">
                        <a href="admin_user_management.php" class="summary-card">
                            <div class="card-icon"><i class="fas fa-user-shield"></i></div>
                            <div class="card-content">
                                <h3>Verify Staff</h3>
                                <p>Approve new staff accounts.</p>
                            </div>
                        </a>
                        <a href="admin_todays_appointments.php" class="summary-card">
                            <div class="card-icon"><i class="fas fa-calendar-check"></i></div>
                            <div class="card-content">
                                <h3>Today's Appointments</h3>
                                <p>View and manage today's schedule.</p>
                            </div>
                        </a>
                        <a href="admin_user_management.php" class="summary-card">
                            <div class="card-icon"><i class="fas fa-users-cog"></i></div>
                            <div class="card-content">
                                <h3>User Roles</h3>
                                <p>Manage user permissions.</p>
                            </div>
                        </a>
                        <a href="admin_recent_billings.php" class="summary-card">
                            <div class="card-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                            <div class="card-content">
                                <h3>Recent Billings</h3>
                                <p>Overview of recent financial transactions.</p>
                            </div>
                        </a>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
    <script src="admin_dashboard.js"></script>
</html>