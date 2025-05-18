<?php
require_once '../config.php';

// --- Doctor Authentication ---
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'doctor') {
    header('Location: doctor_signin.html');
    exit;
}
$doctor_id = $_SESSION['user_id'] ?? null;

// --- Fetch Doctor Profile Data ---
$doctor_profile = null;
if ($doctor_id) {
    $sql_profile = "SELECT id, firstName, lastName, email, phoneNumber, specialty, experience_years, consultation_fee, profile_picture_path, bio FROM doctors WHERE id = ?";
    if ($stmt_profile = $conn->prepare($sql_profile)) {
        $stmt_profile->bind_param("i", $doctor_id);
        if ($stmt_profile->execute()) {
            $result_profile = $stmt_profile->get_result();
            if ($result_profile->num_rows == 1) {
                $doctor_profile = $result_profile->fetch_assoc();
            }
        }
        $stmt_profile->close();
    }
}
$doctor_name_display = htmlspecialchars($doctor_profile['firstName'] ?? $_SESSION['user_firstName'] ?? 'Doctor');
$doctor_lastname_display = htmlspecialchars($doctor_profile['lastName'] ?? $_SESSION['user_lastName'] ?? '');
$doctor_email_display = htmlspecialchars($doctor_profile['email'] ?? ($_SESSION['user_email'] ?? 'N/A'));
$doctor_phone_display = htmlspecialchars($doctor_profile['phoneNumber'] ?? 'N/A');
$doctor_specialty = htmlspecialchars($doctor_profile['specialty'] ?? '');
$doctor_experience = htmlspecialchars($doctor_profile['experience_years'] ?? '');
$doctor_fee = htmlspecialchars($doctor_profile['consultation_fee'] ?? '');
$doctor_bio = htmlspecialchars($doctor_profile['bio'] ?? '');
$doctor_profile_picture = !empty($doctor_profile['profile_picture_path']) ? $doctor_profile['profile_picture_path'] : '../images/doctor-ashford.png';

// --- Fetch Notifications (e.g., new appointments) ---
$notifications = [];
$sql_notifications = "
    SELECT 
        a.id,
        a.appointment_date,
        a.appointment_time,
        a.status,
        a.created_at,
        p.firstName AS patient_firstName,
        p.lastName AS patient_lastName
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    WHERE a.attending_dentist = ?
      AND a.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
      AND (a.status = 'SCHEDULED' OR a.status = 'CONFIRMED')
    ORDER BY a.created_at DESC
    LIMIT 10
";
if ($stmt = $conn->prepare($sql_notifications)) {
    $stmt->bind_param("i", $doctor_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
    }
    $stmt->close();
}

// --- Fetch Appointments Assigned to This Doctor ---
$appointments = [];
$sql = "
    SELECT 
        a.id as appointment_id,
        p.firstName as patient_firstName,
        p.lastName as patient_lastName,
        a.appointment_date,
        a.appointment_time,
        a.service_type,
        a.status,
        a.notes
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    WHERE a.attending_dentist = ?
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $doctor_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $appointments[] = $row;
        }
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Appointments</title>
    <link rel="stylesheet" href="doctor_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .appointments-table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
            border: none;
        }
        
        .appointments-table th {
            background-color: #006a4e;
            color: white;
            padding: 12px 15px;
            text-align: center;
            font-weight: 600;
            border: none;
        }
        
        .appointments-table td {
            padding: 15px;
            text-align: center;
            border-bottom: 1px solid #ddd;
            border-left: none;
            border-right: none;
        }
        
        .appointments-table tbody tr:nth-child(even) {
            background-color:rgb(229, 247, 238);
        }
        
        .appointments-table tr:hover {
            background-color: #f5f5f5;
        }

        .main-content h2 {
            padding: 0 25px;
            margin-bottom: 20px;
            margin-top: 40px;
            color: #006a4e;
            font-size: 24px;
        }

        .table-container {
            padding: 0 20px;
        }

        .appointment-number {
            /* font-weight: 600; */
            color: #000;
        }
      
    </style>
</head>
<body class="doctor-layout-page">
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo-container">
                <img src="../images/tooth.png" alt="Escosia Dental Clinic Logo" class="logo-image">
                <h1>Escosia Dental Clinic</h1>
            </div>
            <nav class="main-nav">
                <ul>
                    <li><a href="doctor_dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt nav-icon"></i> Dashboard</a></li>
                    <li><a href="doctor_appointment.php" class="nav-link active"><i class="fas fa-calendar-alt nav-icon"></i> Appointments</a></li>
                    <li><a href="doctor_patient_management.php" class="nav-link"><i class="fas fa-clipboard-list nav-icon"></i> Patient Management</a></li>
                    <li><a href="../logout.php" class="nav-link"><i class="fas fa-sign-out-alt nav-icon"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>
    <div class="main-content">
        <header class="top-header">
            <div class="header-spacer"></div>
            <div class="user-info">
                <div class="notification-wrapper"">
                    <i class="fas fa-bell notification-icon" id="notificationBell" style="font-size: 22px; cursor: pointer; position: relative;">
                        <?php if (count($notifications) > 0): ?>
                            <span class="notification-badge" id="notificationBadge" style="position: absolute; top: -6px; right: -6px; background: #f44336; color: #fff; border-radius: 50%; font-size: 12px; padding: 2px 6px;">
                                <?php echo count($notifications); ?>
                            </span>
                        <?php endif; ?>
                    </i>
                    <div class="notifications-dropdown" id="notificationsDropdown">
                        <div class="notification-header">Notifications</div>
                        <div class="notification-list" id="notificationList">
                            <?php if (count($notifications) === 0): ?>
                                <p class="no-notifications">No new notifications.</p>
                            <?php else: ?>
                                <?php foreach ($notifications as $n): ?>
                                    <div class="notification-item">
                                        <span class="notification-item-message">
                                            <strong>New Appointment:</strong>
                                            <?php echo htmlspecialchars($n['patient_firstName'] . ' ' . $n['patient_lastName']); ?>
                                            on <?php echo htmlspecialchars($n['appointment_date']); ?> at <?php echo htmlspecialchars($n['appointment_time']); ?>
                                        </span><br>
                                        <span class="notification-item-time">Status: <?php echo htmlspecialchars($n['status']); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="notification-footer">
                            <a href="doctor_appointment.php?filter=pending">View All Request</a>
                        </div>
                    </div>
                </div>
                <img src="<?php echo htmlspecialchars($doctor_profile_picture); ?>" alt="User Avatar" class="user-avatar" id="doctorProfileAvatarDisplay">
                <div class="user-details">
                    <span class="user-name" id="doctorFullNameDisplay"><?php echo $doctor_name_display . ' ' . $doctor_lastname_display; ?></span>
                    <span class="user-role"><?php echo $doctor_specialty ? htmlspecialchars($doctor_specialty) : 'Doctor'; ?></span>
                </div>
            </div>
        </header>
        <h2>My Appointments</h2>
        <div class="table-container">
            <table class="appointments-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Patient Name</th>
                        <th>Appointment Date</th>
                        <th>Appointment Time</th>
                        <th>Service</th>
                        <th>Status</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($appointments)): ?>
                        <?php foreach ($appointments as $index => $apt): ?>
                            <tr>
                                <td class="appointment-number"><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($apt['patient_firstName'] . ' ' . $apt['patient_lastName']); ?></td>
                                <td><?php echo htmlspecialchars(date("m.d.Y", strtotime($apt['appointment_date']))); ?></td>
                                <td><?php echo htmlspecialchars(date("h:i A", strtotime($apt['appointment_time']))); ?></td>
                                <td><?php echo htmlspecialchars($apt['service_type']); ?></td>
                                <td><?php echo htmlspecialchars($apt['status']); ?></td>
                                <td><?php echo htmlspecialchars($apt['notes']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">No appointments found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const bell = document.getElementById('notificationBell');
        const dropdown = document.getElementById('notificationsDropdown');
        if (bell && dropdown) {
            bell.addEventListener('click', function(e) {
                dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
                e.stopPropagation();
            });
            document.addEventListener('click', function(e) {
                if (!dropdown.contains(e.target) && e.target !== bell) {
                    dropdown.style.display = "none";
                }
            });
        }
    });
    </script>
</body>
</html>