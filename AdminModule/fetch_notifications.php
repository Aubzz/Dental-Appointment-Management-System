<?php
// AdminModule/fetch_notifications.php
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized', 'count' => 0, 'notifications' => []]);
    exit;
}

$notifications = [];

// Pending doctors
$sql_doctors = "SELECT id, firstName, lastName, specialty, created_at FROM doctors WHERE is_active = 0 ORDER BY created_at DESC LIMIT 5";
$result_doctors = $conn->query($sql_doctors);
if ($result_doctors) {
    while ($row = $result_doctors->fetch_assoc()) {
        $notifications[] = [
            'type' => 'doctor',
            'title' => 'Pending Doctor Account',
            'message' => $row['firstName'] . ' ' . $row['lastName'] . ' (' . $row['specialty'] . ') registered.',
            'time_ago' => time_elapsed_string($row['created_at']),
            'link' => 'admin_user_management.php#doctor-' . $row['id']
        ];
    }
}

// Pending receptionists
$sql_receptionists = "SELECT id, firstName, lastName, created_at FROM receptionists WHERE is_verified = 0 ORDER BY created_at DESC LIMIT 5";
$result_receptionists = $conn->query($sql_receptionists);
if ($result_receptionists) {
    while ($row = $result_receptionists->fetch_assoc()) {
        $notifications[] = [
            'type' => 'receptionist',
            'title' => 'Pending Receptionist Account',
            'message' => $row['firstName'] . ' ' . $row['lastName'] . ' registered.',
            'time_ago' => time_elapsed_string($row['created_at']),
            'link' => 'admin_user_management.php#receptionist-' . $row['id']
        ];
    }
}

// New appointment requests
$sql_appointments = "
    SELECT a.id, p.firstName, p.lastName, a.service_type, a.created_at
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    WHERE a.status = 'Pending'
    ORDER BY a.created_at DESC
    LIMIT 5
";
$result_appointments = $conn->query($sql_appointments);
if ($result_appointments) {
    while ($row = $result_appointments->fetch_assoc()) {
        $notifications[] = [
            'type' => 'appointment',
            'title' => 'New Appointment Request',
            'message' => $row['firstName'] . ' ' . $row['lastName'] . ' requested ' . $row['service_type'],
            'time_ago' => time_elapsed_string($row['created_at']),
            'link' => 'admin_appointments.php#appointment-' . $row['id']
        ];
    }
}

// Helper function to format time elapsed
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year', 'm' => 'month', 'w' => 'week', 'd' => 'day',
        'h' => 'hour', 'i' => 'minute', 's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }
    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

echo json_encode([
    'count' => count($notifications),
    'notifications' => $notifications
]);

$conn->close();
?>
