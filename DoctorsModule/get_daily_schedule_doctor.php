<?php
require_once '../config.php';
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'doctor') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$doctor_id = $_SESSION['user_id'] ?? null;
$date = $_GET['date'] ?? null;

if (!$doctor_id || !$date) {
    echo json_encode(['error' => 'Missing doctor or date.']);
    exit;
}

$sql = "SELECT 
            a.appointment_time AS time,
            a.service_type,
            CONCAT(p.firstName, ' ', p.lastName) AS patient_name
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        WHERE a.attending_dentist = ? AND a.appointment_date = ?
        ORDER BY a.appointment_time ASC";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("is", $doctor_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $appointments = [];
    while ($row = $result->fetch_assoc()) {
        $row['time_formatted'] = date("h:i A", strtotime($row['time']));
        $appointments[] = $row;
    }
    echo json_encode($appointments);
    $stmt->close();
} else {
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
}
$conn->close();
?>
