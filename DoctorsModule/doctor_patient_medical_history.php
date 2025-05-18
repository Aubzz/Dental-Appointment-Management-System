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

// --- Fetch Patient Info ---
$patient = null;
$sql_patient = "SELECT * FROM patients WHERE id = ?";
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

// --- Handle Edit/Add Medical History ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_medical_history'])) {
    $history_id = isset($_POST['history_id']) ? intval($_POST['history_id']) : 0;
    $date = $_POST['date'] ?? date('Y-m-d');
    $description = trim($_POST['description'] ?? '');

    if ($history_id > 0) {
        // Update existing
        $sql_update = "UPDATE medical_history SET date=?, description=? WHERE id=? AND patient_id=?";
        if ($stmt = $conn->prepare($sql_update)) {
            $stmt->bind_param("ssii", $date, $description, $history_id, $patient_id);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        // Insert new
        $sql_insert = "INSERT INTO medical_history (patient_id, date, description) VALUES (?, ?, ?)";
        if ($stmt = $conn->prepare($sql_insert)) {
            $stmt->bind_param("iss", $patient_id, $date, $description);
            $stmt->execute();
            $stmt->close();
        }
    }
    header("Location: doctor_patient_medical_history.php?patient_id=$patient_id");
    exit;
}

// --- Fetch Medical History ---
$medical_history = [];
$sql_medical = "SELECT * FROM medical_history WHERE patient_id = ? ORDER BY date DESC";
if ($stmt = $conn->prepare($sql_medical)) {
    $stmt->bind_param("i", $patient_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $medical_history[] = $row;
        }
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Medical History</title>
    <link rel="stylesheet" href="doctor_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .medical-history-container {
            background: #D3F2E0;
            border-radius: 15px;
            padding: 30px;
            margin: 30px auto;
            max-width: 700px;
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
        .patient-details-header h2 {
            margin: 0;
            color: #0A744F;
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
    </style>
</head>
<body>
    <!-- Header with notifications and profile -->
    <div class="dashboard-header" style="background: #d3f2e0; display: flex; align-items: center; padding: 12px 24px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div class="notification-wrapper" style="position: relative;">
                <i class="fas fa-bell notification-icon" id="notificationBell" style="font-size: 22px; cursor: pointer; position: relative;">
                    <?php if (count($notifications) > 0): ?>
                        <span class="notification-badge" id="notificationBadge" style="position: absolute; top: -6px; right: -6px; background: #f44336; color: #fff; border-radius: 50%; font-size: 12px; padding: 2px 6px;">
                            <?php echo count($notifications); ?>
                        </span>
                    <?php endif; ?>
                </i>
                <div class="notifications-dropdown" id="notificationsDropdown" style="display:none; position: absolute; left: 0; top: 30px; background: #fff; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); width: 320px; z-index: 100;">
                    <div class="notification-header" style="font-weight: bold; padding: 10px; border-bottom: 1px solid #eee;">Notifications</div>
                    <div class="notification-list" id="notificationList">
                        <?php if (count($notifications) === 0): ?>
                            <p class="no-notifications" style="padding: 15px; color: #888; text-align: center;">No new notifications.</p>
                        <?php else: ?>
                            <?php foreach ($notifications as $n): ?>
                                <div class="notification-item" style="padding: 10px; border-bottom: 1px solid #f0f0f0;">
                                    <p>
                                        <strong>New Appointment:</strong>
                                        <?php echo htmlspecialchars($n['patient_firstName'] . ' ' . $n['patient_lastName']); ?>
                                        on <?php echo htmlspecialchars($n['appointment_date']); ?> at <?php echo htmlspecialchars($n['appointment_time']); ?>
                                    </p>
                                    <small>Status: <?php echo htmlspecialchars($n['status']); ?></small>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <img src="<?php echo htmlspecialchars($doctor_profile_picture); ?>" alt="User" style="width:32px; height:32px; border-radius:50%; object-fit:cover;">
            <div style="display: flex; flex-direction: column;">
                <span style="font-weight: bold; font-size: 1.1em;"><?php echo $doctor_name; ?></span>
                <span style="color: #888; font-size: 0.95em;"><?php echo $doctor_specialty; ?></span>
            </div>
        </div>
    </div>
    <div class="medical-history-container">
        <?php if ($patient): ?>
            <div class="patient-details-header">
                <img src="<?php echo htmlspecialchars($patient['profile_picture_path'] ?? 'https://via.placeholder.com/80x80?text=User'); ?>" alt="Profile">
                <div>
                    <h2><?php echo htmlspecialchars($patient['firstName'] . ' ' . $patient['lastName']); ?></h2>
                    <p><?php echo htmlspecialchars($patient['phoneNumber']); ?></p>
                </div>
            </div>
            <button class="edit-btn" onclick="showEditForm(0, '', '<?php echo date('Y-m-d'); ?>')"><i class="fas fa-plus"></i> Add Medical History</button>
            <div id="medicalHistoryList">
                <?php if (!empty($medical_history)): ?>
                    <?php foreach ($medical_history as $mh): ?>
                        <div class="record-card">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <div>
                                    <p><strong>Date:</strong> <?php echo htmlspecialchars($mh['date']); ?></p>
                                    <p><?php echo htmlspecialchars($mh['description']); ?></p>
                                </div>
                                <button class="edit-btn" style="background:#e0c341; color:#333; padding:4px 12px; font-size:0.95em;" onclick="showEditForm(<?php echo $mh['id']; ?>, '<?php echo htmlspecialchars(addslashes($mh['description'])); ?>', '<?php echo $mh['date']; ?>'); event.stopPropagation();"><i class="fas fa-pencil-alt"></i> Edit</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No medical history found.</p>
                <?php endif; ?>
            </div>
            <div id="editMedicalHistoryFormContainer" style="display:none;"></div>
        <?php else: ?>
            <p>Patient not found.</p>
        <?php endif; ?>
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
    </script>
</body>
</html>
