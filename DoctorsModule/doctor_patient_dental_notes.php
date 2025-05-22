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

// --- Fetch Patient Info and Dental Notes (from medicalInfo) ---
$patient = null;
$decryptedDentalNotes = 'None';
if ($patient_id) {
    $sql_patient = "SELECT id, firstName, lastName, email, phoneNumber, gender, dob, medicalInfo FROM patients WHERE id = ?";
    if ($stmt = $conn->prepare($sql_patient)) {
        $stmt->bind_param("i", $patient_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows == 1) {
                $patient = $result->fetch_assoc();
                if (!empty($patient['medicalInfo'])) {
                    $decryptedDentalNotes = decrypt_data($patient['medicalInfo']);
                    if ($decryptedDentalNotes === false || $decryptedDentalNotes === null || trim($decryptedDentalNotes) === '' || $decryptedDentalNotes === '[Encrypted data - retrieval issue or no data]') {
                        $decryptedDentalNotes = 'None';
                    }
                }
            }
        }
        $stmt->close();
    }
}

// --- Handle Edit Dental Notes (main medicalInfo field) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_main_dental_notes'])) {
    $newDentalNotes = trim($_POST['main_dental_notes'] ?? '');
    $encryptedDentalNotes = !empty($newDentalNotes) ? encrypt_data($newDentalNotes) : null;
    $sql_update = "UPDATE patients SET medicalInfo=? WHERE id=?";
    if ($stmt = $conn->prepare($sql_update)) {
        $stmt->bind_param("si", $encryptedDentalNotes, $patient_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: doctor_patient_dental_notes.php?patient_id=$patient_id");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dental Notes</title>
    <link rel="stylesheet" href="doctor_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .dental-notes-container {
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
        .dental-notes-header {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: none;
            margin-bottom: 0;
            padding: 0;
        }
        .dental-notes-content {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            min-height: 160px;
            color: #555;
            width: 100%;
            margin-top: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
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
        @media (max-width: 900px) {
            .dental-notes-container {
                padding: 20px 10px;
            }
            .dental-notes-header {
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
            <div class="dental-notes-container">
                <div class="dental-notes-header">
                    <h2>Dental Notes</h2>
                    <button class="edit-btn" style="background-color: #A2D9BC; color: #0A744F; border: none; border-radius: 10px; padding: 8px 15px; font-size: 14px; cursor: pointer;" onclick="showMainDentalNotesEditForm()"><i class="fas fa-pencil-alt"></i> Edit</button>
                </div>
                <div class="patient-details">
                    <img src="../images/patient-avatar.png" alt="Patient Avatar">
                    <div class="patient-details-info">
                        <h3><?php echo htmlspecialchars($patient['firstName'] . ' ' . $patient['lastName']); ?></h3>
                        <p><?php echo htmlspecialchars($patient['phoneNumber']); ?></p>
                    </div>
                </div>
                <div class="dental-notes-content">
                    <span id="mainDentalNotesDisplay" style="font-style:italic; color:#888;"><?php echo nl2br(htmlspecialchars($decryptedDentalNotes)); ?></span>
                    <form id="mainDentalNotesEditForm" method="POST" style="display:none; margin-top:15px; width:100%;">
                        <input type="hidden" name="edit_main_dental_notes" value="1">
                        <textarea name="main_dental_notes" rows="4" style="width:100%;"><?php echo htmlspecialchars($decryptedDentalNotes); ?></textarea><br>
                        <button type="submit" class="edit-btn" style="background:#1976d2; color:#fff;">Save</button>
                        <button type="button" class="edit-btn" style="background:#aaa; color:#fff; margin-left:10px;" onclick="cancelMainDentalNotesEdit()">Cancel</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
    function showMainDentalNotesEditForm() {
        document.getElementById('mainDentalNotesDisplay').style.display = 'none';
        document.getElementById('mainDentalNotesEditForm').style.display = 'block';
    }
    function cancelMainDentalNotesEdit() {
        document.getElementById('mainDentalNotesEditForm').style.display = 'none';
        document.getElementById('mainDentalNotesDisplay').style.display = 'inline';
    }
    </script>
</body>
</html> 