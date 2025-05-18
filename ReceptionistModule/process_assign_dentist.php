<?php
require_once '../config.php';

header('Content-Type: application/json');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

// Receptionist or admin only (adjust as needed)
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['receptionist', 'admin'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
    exit;
}

$patient_id = $_POST['patient_id'] ?? null;
$doctor_id = $_POST['doctor_id'] ?? null;
$appointment_date = $_POST['appointment_date'] ?? null;
$appointment_time = $_POST['appointment_time'] ?? null;
$service_type = $_POST['service_type'] ?? 'General Checkup';

if (!$patient_id || !$doctor_id || !$appointment_date || !$appointment_time) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields.']);
    exit;
}

// 1. Create appointment (or update patient record if you have a different logic)
$sql = "INSERT INTO appointments (patient_id, attending_dentist, appointment_date, appointment_time, service_type, status, created_at)
        VALUES (?, ?, ?, ?, ?, 'SCHEDULED', NOW())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iisss", $patient_id, $doctor_id, $appointment_date, $appointment_time, $service_type);

if ($stmt->execute()) {
    // 2. Create a notification for the doctor
    $notification_sql = "INSERT INTO notifications (patient_id, title, message, link, is_read, created_at, icon_class)
                         VALUES (?, 'New Patient Assignment', ?, ?, 0, NOW(), 'fas fa-user-md')";
    $message = "New patient assigned: Patient ID $patient_id, Appointment on $appointment_date at $appointment_time.";
    $link = "doctor_dashboard.php#appointment-" . $stmt->insert_id;
    $notif_stmt = $conn->prepare($notification_sql);
    $notif_stmt->bind_param("iss", $patient_id, $message, $link);
    $notif_stmt->execute();
    $notif_stmt->close();

    echo json_encode(['success' => true, 'message' => 'Doctor assigned and notified successfully.']);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
}
$stmt->close();

// Close the database connection at the end
$conn->close();
?>
