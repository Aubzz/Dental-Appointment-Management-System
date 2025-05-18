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
    $sql_profile = "SELECT firstName, lastName, specialty, profile_picture_path FROM doctors WHERE id = ?";
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

// Set display variables
$doctor_name_display = htmlspecialchars($doctor_profile['firstName'] ?? '');
$doctor_lastname_display = htmlspecialchars($doctor_profile['lastName'] ?? '');
$doctor_specialty = htmlspecialchars($doctor_profile['specialty'] ?? 'Doctor');
$doctor_profile_picture = !empty($doctor_profile['profile_picture_path']) ? $doctor_profile['profile_picture_path'] : 'https://via.placeholder.com/32x32?text=User';

// --- Fetch Notifications (e.g., new appointments) ---
$notifications = [];
$notification_count = 0;
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
        $notification_count = count($notifications);
    }
    $stmt->close();
}

// --- Fetch Patients Assigned to This Doctor (or all patients if you want) ---
$patients = [];
$sql_patients = "
    SELECT 
        p.id,
        p.firstName,
        p.lastName,
        p.phoneNumber
    FROM patients p
    JOIN appointments a ON a.patient_id = p.id
    WHERE a.attending_dentist = ?
    GROUP BY p.id
    ORDER BY p.lastName, p.firstName
";
if ($stmt = $conn->prepare($sql_patients)) {
    $stmt->bind_param("i", $doctor_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $patients[] = $row;
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
    <title>Doctor Patient Management</title>
    <link rel="stylesheet" href="doctor_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .patient-list-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .search-input {
            padding: 8px 16px;
            border-radius: 25px;
            border: 1px solid #ccc;
            font-size: 1em;
            width: 250px;
        }
        .patient-record {
            background-color: white;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
            cursor: pointer; 
            transition: box-shadow 0.2s;
        }
        .patient-record:hover {
            box-shadow: 0 2px 8px rgba(25, 118, 210, 0.08);
        }
        .patient-record img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        .patient-record-details {
            flex: 1;
        }
        .patient-record-details h3 {
            font-size: 16px;
            color: #0A744F;
            margin: 0 0 5px;
        }
        .patient-record-details p {
            font-size: 12px;
            color: #777;
            margin: 0;
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
                    <li><a href="doctor_appointment.php" class="nav-link"><i class="fas fa-calendar-alt nav-icon"></i> Appointments</a></li>
                    <li><a href="doctor_patient_management.php" class="nav-link active  "><i class="fas fa-clipboard-list nav-icon"></i> Patient Management</a></li>
                    <li><a href="../logout.php" class="nav-link"><i class="fas fa-sign-out-alt nav-icon"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>
        <div class="main-content">
            <header class="top-header">
                <div class="header-spacer"></div>
                <div class="user-info">
                    <div class="notification-wrapper">
                        <i class="fas fa-bell notification-icon" id="notificationBell" style="font-size: 22px; cursor: pointer; position: relative;">
                            <?php if ($notification_count > 0): ?>
                                <span class="notification-badge" id="notificationBadge" style="position: absolute; top: -6px; right: -6px; background: #f44336; color: #fff; border-radius: 50%; font-size: 12px; padding: 2px 6px;">
                                    <?php echo $notification_count; ?>
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
                    <img src="<?php echo htmlspecialchars($doctor_profile_picture); ?>" alt="User Avatar" class="user-avatar" id="profileCardAvatar">
                    <div class="user-details">
                        <span class="user-name" id="profileCardName"><?php echo $doctor_name_display . ' ' . $doctor_lastname_display; ?></span>
                        <span class="user-role"><?php echo $doctor_specialty; ?></span>
                    </div>
                </div>
            </header>
     <div class="main-content">
        <div class="patient-list-header">
            <h2>Patient List</h2>
        </div>
        <div class="patient-search-bar">
            <input type="text" class="search-input" id="searchInput" placeholder="Search by name">
        </div>
        <!-- <div class="patient-count">
            <?php echo count($patients); ?> assigned patient record(s)
        </div> -->
        <div id="patientList">
            <?php if (!empty($patients)): ?>
                <?php foreach ($patients as $patient): ?>
                    <a href="doctor_patient_details.php?patient_id=<?php echo $patient['id']; ?>" class="patient-record" style="text-decoration:none; color:inherit;">
                        <img src="../images/patient-avatar.png" alt="Patient Avatar" class="user-avatar">
                        <div class="patient-record-details">
                            <h3><?php echo htmlspecialchars($patient['firstName'] . ' ' . $patient['lastName']); ?></h3>
                            <p>Phone: <?php echo htmlspecialchars($patient['phoneNumber']); ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-patients-found">No patients found.</p>
            <?php endif; ?>
        </div>
    </div>
    <script>
    // Notification dropdown toggle
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

        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const patientList = document.getElementById('patientList');
        if (searchInput && patientList) {
            searchInput.addEventListener('input', function() {
                const filter = searchInput.value.toLowerCase();
                const records = patientList.querySelectorAll('.patient-record');
                records.forEach(function(record) {
                    const name = record.querySelector('h3').textContent.toLowerCase();
                    const phone = record.querySelector('p').textContent.toLowerCase();
                    if (name.includes(filter) || phone.includes(filter)) {
                        record.style.display = '';
                    } else {
                        record.style.display = 'none';
                    }
                });
            });
        }
    });
    </script>
</body>
</html>