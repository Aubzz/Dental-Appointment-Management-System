<?php
require_once '../config.php';

// Basic Admin Check
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit;
}

// Determine active page for sidebar styling
$current_page = basename($_SERVER['PHP_SELF']); // This will be 'admin_verify_accounts.php'

// Fetch pending receptionists
$pending_receptionists = [];
$sql_pending_receptionists = "SELECT id, firstName, lastName, email, employeeId, created_at FROM receptionists WHERE is_verified = 0 ORDER BY created_at DESC";
if ($stmt_receptionists = $conn->prepare($sql_pending_receptionists)) {
    if ($stmt_receptionists->execute()) {
        $result_receptionists = $stmt_receptionists->get_result();
        $pending_receptionists = $result_receptionists->fetch_all(MYSQLI_ASSOC);
    } else { error_log("Error fetching pending receptionists: " . $stmt_receptionists->error); }
    $stmt_receptionists->close();
} else { error_log("Error preparing statement for pending receptionists: " . $conn->error); }

// Fetch pending doctors
$pending_doctors = [];
$sql_pending_doctors = "SELECT id, firstName, lastName, email, phoneNumber, specialty, created_at, is_active FROM doctors WHERE is_active = 0 ORDER BY created_at DESC";
if ($stmt_doctors = $conn->prepare($sql_pending_doctors)) {
    if ($stmt_doctors->execute()) {
        $result_doctors = $stmt_doctors->get_result();
        $pending_doctors = $result_doctors->fetch_all(MYSQLI_ASSOC);
    } else { error_log("Error fetching pending doctors: " . $stmt_doctors->error); }
    $stmt_doctors->close();
} else { error_log("Error preparing statement for pending doctors: " . $conn->error); }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="admin_user_management.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
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
                        <li>
                            <a href="admin_dashboard.php" class="<?php echo ($current_page === 'admin_dashboard.php') ? 'active' : ''; ?>">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li>
                            <a href="admin_v.php" class="<?php echo ($current_page === 'admin_user_management.php') ? 'active' : ''; ?>">
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
                    <h1 class="panel-page-title">User Account Verification</h1>
                    <p class="panel-intro-text">Review and manage pending staff account registrations.</p>

                    <?php
                    if (isset($_SESSION['admin_message'])) {
                        $msg_type = htmlspecialchars($_SESSION['admin_message']['type']);
                        $msg_text = htmlspecialchars($_SESSION['admin_message']['text']);
                        $msg_class = '';
                        if ($msg_type === 'success') $msg_class = 'success-message-admin';
                        if ($msg_type === 'error') $msg_class = 'error-message-admin';
                        if ($msg_type === 'warning') $msg_class = 'warning-message-admin';
                        echo "<div class='admin-message {$msg_class}'>{$msg_text}</div>";
                        unset($_SESSION['admin_message']);
                    }
                    ?>

                    <div class="notification-wrapper">
                        <i class="fas fa-bell notification-icon" id="notificationBell">
                            <span class="notification-badge" id="notificationBadge"></span>
                        </i>
                        <div class="notifications-dropdown" id="notificationsDropdown">
                            <div class="notification-header">Notifications</div>
                            <div class="notification-list" id="notificationList">
                                <p class="no-notifications">No new notifications.</p>
                            </div>
                            <div class="notification-footer">
                                <a href="admin_user_management.php">View All</a>
                            </div>
                        </div>
                    </div>

                    <section class="user-verification-section card">
                        <h3>Pending Receptionist Accounts</h3>
                        <div class="table-responsive-wrapper">
                            <table class="data-table verification-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Receptionist ID</th>
                                        <th>Signed Up</th>
                                        <th class="actions-column">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($pending_receptionists)): ?>
                                        <?php foreach ($pending_receptionists as $receptionist): ?>
                                        <tr>
                                            <td data-label="ID"><?php echo htmlspecialchars($receptionist['id']); ?></td>
                                            <td data-label="Name"><?php echo htmlspecialchars($receptionist['firstName'] . ' ' . $receptionist['lastName']); ?></td>
                                            <td data-label="Email"><?php echo htmlspecialchars($receptionist['email']); ?></td>
                                            <td data-label="Receptionist ID"><?php echo htmlspecialchars($receptionist['employeeId']); ?></td>
                                            <td data-label="Signed Up"><?php echo htmlspecialchars(date('M d, Y H:i', strtotime($receptionist['created_at']))); ?></td>
                                            <td data-label="Actions" class="action-buttons-cell">
                                                <form action="admin_process_verification.php" method="POST" style="display:inline-block;">
                                                    <input type="hidden" name="account_id" value="<?php echo $receptionist['id']; ?>">
                                                    <input type="hidden" name="account_type" value="receptionist">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="btn btn-action btn-approve"><i class="fas fa-check"></i> Approve</button>
                                                </form>
                                                <form action="admin_process_verification.php" method="POST" style="display:inline-block;">
                                                    <input type="hidden" name="account_id" value="<?php echo $receptionist['id']; ?>">
                                                    <input type="hidden" name="account_type" value="receptionist">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="btn btn-action btn-reject" onclick="return confirm('Are you sure you want to reject this receptionist account? This will delete the record.');"><i class="fas fa-times"></i> Reject</button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="6" class="no-data-message">No pending receptionist accounts.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="user-verification-section card" style="margin-top: 30px;">
                        <h3>Pending Doctor Accounts</h3>
                        <div class="table-responsive-wrapper">
                            <table class="data-table verification-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Specialty</th>
                                        <th>Signed Up</th>
                                        <th>Status</th>
                                        <th class="actions-column">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($pending_doctors)): ?>
                                        <?php foreach ($pending_doctors as $doctor): ?>
                                        <tr>
                                            <td data-label="ID"><?php echo htmlspecialchars($doctor['id']); ?></td>
                                            <td data-label="Name"><?php echo htmlspecialchars($doctor['firstName'] . ' ' . $doctor['lastName']); ?></td>
                                            <td data-label="Email"><?php echo htmlspecialchars($doctor['email']); ?></td>
                                            <td data-label="Phone"><?php echo htmlspecialchars($doctor['phoneNumber']); ?></td>
                                            <td data-label="Specialty"><?php echo htmlspecialchars($doctor['specialty']); ?></td>
                                            <td data-label="Signed Up"><?php echo htmlspecialchars(date('M d, Y H:i', strtotime($doctor['created_at']))); ?></td>
                                            <td data-label="Status" class="status-cell <?php echo ($doctor['is_active'] == 0) ? 'status-pending' : 'status-active'; ?>"><?php echo ($doctor['is_active'] == 0) ? 'Pending Activation' : 'Active'; ?></td>
                                            <td data-label="Actions" class="action-buttons-cell">
                                                <?php if ($doctor['is_active'] == 0): ?>
                                                <form action="admin_process_verification.php" method="POST" style="display:inline-block;">
                                                    <input type="hidden" name="account_id" value="<?php echo $doctor['id']; ?>">
                                                    <input type="hidden" name="account_type" value="doctor">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="btn btn-action btn-approve"><i class="fas fa-check-circle"></i> Activate</button>
                                                </form>
                                                <?php endif; ?>
                                                <form action="admin_process_verification.php" method="POST" style="display:inline-block;">
                                                    <input type="hidden" name="account_id" value="<?php echo $doctor['id']; ?>">
                                                    <input type="hidden" name="account_type" value="doctor">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="btn btn-action btn-reject" onclick="return confirm('Are you sure you want to reject this doctor application? This will delete the record if pending.');"><i class="fas fa-trash-alt"></i> Reject</button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="8" class="no-data-message">No pending doctor accounts for activation.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </main>
        </div>
    </div>
</body>
<script src="admin_dashboard.js"></script>
</html>