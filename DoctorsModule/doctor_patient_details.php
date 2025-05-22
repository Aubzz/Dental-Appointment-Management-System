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
$doctor_name_display = htmlspecialchars($doctor_profile['firstName'] ?? '');
$doctor_lastname_display = htmlspecialchars($doctor_profile['lastName'] ?? '');
$doctor_specialty = htmlspecialchars($doctor_profile['specialty'] ?? 'Doctor');
$doctor_profile_picture = !empty($doctor_profile['profile_picture_path']) ? $doctor_profile['profile_picture_path'] : 'https://via.placeholder.com/32x32?text=User';

// --- Fetch Notifications ---
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

// --- Fetch Patient Details ---
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;
$patient = null;

// Handle patient update POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['patient_id'])) {
    $update_sql = "UPDATE patients SET firstName=?, lastName=?, phoneNumber=?, email=?, gender=?, dob=? WHERE id=?";
    if ($stmt = $conn->prepare($update_sql)) {
        $stmt->bind_param(
            "ssssssi",
            $_POST['firstName'],
            $_POST['lastName'],
            $_POST['phoneNumber'],
            $_POST['email'],
            $_POST['gender'],
            $_POST['dob'],
            $_POST['patient_id']
        );
        $stmt->execute();
        $stmt->close();
        // Refresh patient data after update
        header("Location: doctor_patient_details.php?patient_id=" . intval($_POST['patient_id']));
        exit;
    }
}
if ($patient_id) {
    $sql_patient = "SELECT id, firstName, lastName, email, phoneNumber, gender, dob FROM patients WHERE id = ?";
    if ($stmt = $conn->prepare($sql_patient)) {
        $stmt->bind_param("i", $patient_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows == 1) {
                $patient = $result->fetch_assoc();
            }
        }
        $stmt->close();
    }
}

// Fetch encrypted medical info
$decryptedMedicalInfo = 'None';
$sql = "SELECT medicalInfo FROM patients WHERE id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $patient_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if (!empty($row['medicalInfo'])) {
                $decryptedMedicalInfo = decrypt_data($row['medicalInfo']);
                if ($decryptedMedicalInfo === false || $decryptedMedicalInfo === null || trim($decryptedMedicalInfo) === '' || $decryptedMedicalInfo === '[Encrypted data - retrieval issue or no data]') {
                    $decryptedMedicalInfo = 'None';
                }
            }
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
    <title>Patient Details</title>
    <link rel="stylesheet" href="doctor_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .patient-details-card {
            background: #fff;
            border-radius: 12px;
            padding: 30px 40px;
            margin: 30px 35px;
            box-shadow: 0 2px 8px rgba(25, 118, 210, 0.08);
            max-width: 1500px;
            width: 100%;
        }
        .patient-details-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }
        .patient-details-header img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
        }
        .patient-details-header .patient-name {
            font-size: 22px;
            font-weight: 600;
            color: #0A744F;
        }
        .patient-details-info {
            margin-bottom: 20px;
        }
        .patient-details-info p {
            font-size: 15px;
            color: #333;
            margin: 8px 0;
        }
        .patient-details-options {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }
        .patient-details-options a {
            background: #e8f8f5;
            border-radius: 8px;
            padding: 15px 25px;
            text-decoration: none;
            color: #0A744F;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
        }
        .patient-details-options a:hover {
            background: #d4edda;
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
                    <li><a href="doctor_patient_management.php" class="nav-link active"><i class="fas fa-clipboard-list nav-icon"></i> Patient Management</a></li>
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
            <div class="patient-details-card">
                <?php if ($patient): ?>
                    <div class="patient-details-header">
                        <img src="../images/patient-avatar.png" alt="Patient Avatar">
                        <div class="patient-name"><?php echo htmlspecialchars($patient['firstName'] . ' ' . $patient['lastName']); ?></div>
                        <button id="editPatientBtn" style="margin-left:auto; background:none; border:none; cursor:pointer; font-size:20px; color:#0A744F; position:relative; top:-6px;" title="Edit Patient">
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                    </div>
                    <form id="editPatientForm" method="POST" style="display:none; margin-bottom:20px;">
                        <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">
                        <label>First Name: <input type="text" name="firstName" value="<?php echo htmlspecialchars($patient['firstName']); ?>" required></label><br>
                        <label>Last Name: <input type="text" name="lastName" value="<?php echo htmlspecialchars($patient['lastName']); ?>" required></label><br>
                        <label>Phone Number: <input type="text" name="phoneNumber" value="<?php echo htmlspecialchars($patient['phoneNumber']); ?>" required></label><br>
                        <label>Email: <input type="email" name="email" value="<?php echo htmlspecialchars($patient['email']); ?>" required></label><br>
                        <label>Gender: <input type="text" name="gender" value="<?php echo htmlspecialchars($patient['gender']); ?>"></label><br>
                        <label>Birthdate: <input type="date" name="dob" value="<?php echo htmlspecialchars($patient['dob']); ?>"></label><br>
                        <button type="submit" style="background:#1976d2; color:#fff; border:none; border-radius:5px; padding:8px 16px; margin-top:10px;">Save</button>
                        <button type="button" id="cancelEditBtn" style="margin-left:10px;">Cancel</button>
                    </form>
                    <div class="patient-details-info">
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($patient['email']); ?></p>
                        <p><strong>Gender:</strong> <?php
                            $gender = trim($patient['gender']);
                            echo $gender ? ucfirst(strtolower($gender)) : 'Not specified';
                        ?></p>
                        <p><strong>Birthdate:</strong> <?php echo htmlspecialchars($patient['dob']); ?></p>
                        <p><strong>Contact Number:</strong> <?php echo htmlspecialchars($patient['phoneNumber']); ?></p>
                    </div>
                    <div class="patient-details-options">
                        <a href="doctor_patient_medical_history.php?patient_id=<?php echo $patient_id; ?>"><i class="fas fa-notes-medical"></i> Medical History</a>
                        <a href="doctor_patient_dental_notes.php?patient_id=<?php echo $patient_id; ?>"><i class="fas fa-sticky-note"></i> Dental Notes</a>
                    </div>
                <?php else: ?>
                    <p>Patient not found.</p>
                <?php endif; ?>
            </div>
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
        // Edit button functionality
        const editBtn = document.getElementById('editPatientBtn');
        const editForm = document.getElementById('editPatientForm');
        const cancelEditBtn = document.getElementById('cancelEditBtn');
        const detailsInfo = document.querySelector('.patient-details-info');
        const detailsOptions = document.querySelector('.patient-details-options');
        if (editBtn && editForm) {
            editBtn.addEventListener('click', function() {
                editForm.style.display = 'block';
                editBtn.style.display = 'none';
                if(detailsInfo) detailsInfo.style.display = 'none';
                if(detailsOptions) detailsOptions.style.display = 'none';
            });
        }
        if (cancelEditBtn && editBtn && editForm) {
            cancelEditBtn.addEventListener('click', function() {
                editForm.style.display = 'none';
                editBtn.style.display = 'inline-block';
                if(detailsInfo) detailsInfo.style.display = '';
                if(detailsOptions) detailsOptions.style.display = '';
            });
        }
    });
    </script>
</body>
</html> 