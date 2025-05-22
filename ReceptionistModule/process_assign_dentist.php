<?php
require_once '../config.php';

header('Content-Type: application/json');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

// Receptionist or admin only (adjust as needed)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

// Update the existing appointment to assign the doctor
$sql = "UPDATE appointments SET attending_dentist = ?, status = 'SCHEDULED' WHERE patient_id = ? AND appointment_date = ? AND appointment_time = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiss", $doctor_id, $patient_id, $appointment_date, $appointment_time);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    // Create a notification for the doctor
    $notification_sql = "INSERT INTO notifications (patient_id, title, message, link, is_read, created_at, icon_class)
                         VALUES (?, 'New Patient Assignment', ?, ?, 0, NOW(), 'fas fa-user-md')";
    $message = "New patient assigned: Patient ID $patient_id, Appointment on $appointment_date at $appointment_time.";
    $link = "doctor_dashboard.php"; // You can append #appointment-ID if needed
    $notif_stmt = $conn->prepare($notification_sql);
    $notif_stmt->bind_param("iss", $patient_id, $message, $link);
    $notif_stmt->execute();
    $notif_stmt->close();

    echo json_encode(['success' => true]);
    $stmt->close();
    $conn->close();
    exit;
} 
// If not successful, return error
$stmt->close();
$conn->close();
// echo json_encode(['success' => false, 'error' => 'No matching appointment found or database error.']);
echo json_encode(['success' => false]);
