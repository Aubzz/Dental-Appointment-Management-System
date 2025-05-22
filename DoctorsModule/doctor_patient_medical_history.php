<?php
require_once '../config.php';

// --- Doctor Authentication ---
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'doctor') {
    header('Location: doctor_signin.html');
    exit;
}
$doctor_id = $_SESSION['user_id'] ?? null;
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;

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
$doctor_name = htmlspecialchars(($doctor_profile['firstName'] ?? '') . ' ' . ($doctor_profile['lastName'] ?? ''));
$doctor_specialty = htmlspecialchars($doctor_profile['specialty'] ?? 'Doctor');
$doctor_profile_picture = !empty($doctor_profile['profile_picture_path']) ? $doctor_profile['profile_picture_path'] : 'https://via.placeholder.com/32x32?text=User';

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

// --- Fetch Patient Info and Medical Info ---
$patient = null;
$decryptedMedicalInfo = 'None';
if ($patient_id) {
    $sql_patient = "SELECT id, firstName, lastName, email, phoneNumber, gender, dob, medicalInfo FROM patients WHERE id = ?";
if ($stmt = $conn->prepare($sql_patient)) {
    $stmt->bind_param("i", $patient_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows == 1) {
            $patient = $result->fetch_assoc();
                if (!empty($patient['medicalInfo'])) {
                    $decryptedMedicalInfo = decrypt_data($patient['medicalInfo']);
                    if ($decryptedMedicalInfo === false || $decryptedMedicalInfo === null || trim($decryptedMedicalInfo) === '' || $decryptedMedicalInfo === '[Encrypted data - retrieval issue or no data]') {
                        $decryptedMedicalInfo = 'None';
                    }
                }
        }
    }
    $stmt->close();
    }
}

// --- Handle Edit Medical Info (main medicalInfo field) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_main_medical_info'])) {
    $newMedicalInfo = trim($_POST['main_medical_info'] ?? '');
    $encryptedMedicalInfo = !empty($newMedicalInfo) ? encrypt_data($newMedicalInfo) : null;
    $sql_update = "UPDATE patients SET medicalInfo=? WHERE id=?";
        if ($stmt = $conn->prepare($sql_update)) {
        $stmt->bind_param("si", $encryptedMedicalInfo, $patient_id);
            $stmt->execute();
            $stmt->close();
    }
    header("Location: doctor_patient_medical_history.php?patient_id=$patient_id");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical History</title>
    <link rel="stylesheet" href="doctor_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .notes-card {
            background: #fff;
            border-radius: 12px;
            padding: 30px 40px;
            margin: 30px 35px;
            box-shadow: 0 2px 8px rgba(25, 118, 210, 0.08);
            max-width: 1500px;
            width: 100%;
        }
        .notes-header {
            font-size: 22px;
            font-weight: 600;
            color: #0A744F;
            margin-bottom: 20px;
        }
        .note-content {
            font-size: 16px;
            color: #333;
        }
        .medical-history-container {
            background: #D3F2E0;
            border-radius: 12px;
            padding: 30px 40px;
            margin: 30px 35px;
            box-shadow: 0 2px 8px rgba(25, 118, 210, 0.08);
            max-width: 1500px;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 0;
            min-height: 220px;
        }
        .medical-history-header {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: none;
            margin-bottom: 0;
            padding: 0;
        }
        .medical-history-content {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            min-height: 160px;
            color: #555;
            font-style: italic;
            width: 100%;
            margin-top: 20px;
            display: flex;
            align-items: center;
        }
        .patient-details {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: flex-start;
            min-width: 220px;
            max-width: 100%;
            margin-right: 0;
            gap: 20px;
            width: 100%;
            margin-bottom: 20px;
            margin-top: 20px;
        }
        .patient-details img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 0;
        }
        .patient-details-info {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .patient-details-info h3 {
            color: #0A744F;
            margin: 0 0 5px;
            font-size: 20px;
            font-weight: 600;
            text-align: left;
        }
        .patient-details-info p {
            margin: 0;
            font-size: 14px;
            text-align: left;
        }
        .edit-btn {
            background: #16a085;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 8px 18px;
            cursor: pointer;
            float: right;
            margin-bottom: 10px;
        }
        .edit-btn i { margin-right: 6px; }
        .record-card {
            background: #fff;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.07);
        }
        .edit-form {
            background: #fff;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.07);
        }
        @media (max-width: 900px) {
            .medical-history-container {
                padding: 20px 10px;
            }
            .medical-history-header {
                padding: 0 10px;
            }
            .patient-details {
                flex-direction: column;
                gap: 10px;
            }
            .patient-details-info h3, .patient-details-info p {
                text-align: center;
            }
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
            <div class="medical-history-container">
                <div class="medical-history-header">
                    <h2>Medical History</h2>
                    <button class="edit-btn" style="background-color: #A2D9BC; color: #0A744F; border: none; border-radius: 10px; padding: 8px 15px; font-size: 14px; cursor: pointer;" onclick="showMainMedicalInfoEditForm()"><i class="fas fa-pencil-alt"></i> Edit</button>
                                </div>
                <div class="patient-details">
                    <img src="../images/patient-avatar.png" alt="Patient Avatar">
                    <div class="patient-details-info">
                        <h3><?php echo htmlspecialchars($patient['firstName'] . ' ' . $patient['lastName']); ?></h3>
                        <p><?php echo htmlspecialchars($patient['phoneNumber']); ?></p>
                    </div>
                </div>
                <div class="medical-history-content">
                    <span id="mainMedicalInfoDisplay" style="font-style:italic;"><?php echo nl2br(htmlspecialchars($decryptedMedicalInfo)); ?></span>
                    <form id="mainMedicalInfoEditForm" method="POST" style="display:none; margin-top:15px; width:100%;">
                        <input type="hidden" name="edit_main_medical_info" value="1">
                        <textarea name="main_medical_info" rows="4" style="width:100%;"><?php echo htmlspecialchars($decryptedMedicalInfo); ?></textarea><br>
                        <button type="submit" class="edit-btn" style="background:#1976d2; color:#fff;">Save</button>
                        <button type="button" class="edit-btn" style="background:#aaa; color:#fff; margin-left:10px;" onclick="cancelMainMedicalInfoEdit()">Cancel</button>
                    </form>
            </div>
            </div>
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
    });

    // Show edit/add form for medical history
    function showEditForm(historyId, description, date) {
        var formHtml = `
            <form class="edit-form" method="POST" style="margin-bottom:20px;">
                <input type="hidden" name="history_id" value="`+historyId+`">
                <input type="hidden" name="edit_medical_history" value="1">
                <label>Date: <input type="date" name="date" value="`+date+`" required></label><br>
                <label>Description:<br>
                    <textarea name="description" rows="3" required style="width:100%;">`+description.replace(/'/g, "&#39;") +`</textarea>
                </label><br>
                <button type="submit" style="background:#1976d2; color:#fff; border:none; border-radius:5px; padding:8px 16px; margin-top:10px;">Save</button>
                <button type="button" onclick="document.getElementById('editMedicalHistoryFormContainer').style.display='none';" style="margin-left:10px;">Cancel</button>
            </form>
        `;
        document.getElementById('editMedicalHistoryFormContainer').innerHTML = formHtml;
        document.getElementById('editMedicalHistoryFormContainer').style.display = 'block';
        window.scrollTo({ top: document.getElementById('editMedicalHistoryFormContainer').offsetTop - 100, behavior: 'smooth' });
    }

    function showMainMedicalInfoEditForm() {
        document.getElementById('mainMedicalInfoDisplay').style.display = 'none';
        document.getElementById('mainMedicalInfoEditForm').style.display = 'block';
    }
    function cancelMainMedicalInfoEdit() {
        document.getElementById('mainMedicalInfoEditForm').style.display = 'none';
        document.getElementById('mainMedicalInfoDisplay').style.display = 'inline';
    }
    </script>
</body>
</html>
