<?php
require_once '../config.php';
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}
$appointment_id = $_POST['appointment_id'] ?? null;
if (!$appointment_id) {
    echo json_encode(['success' => false, 'error' => 'Missing appointment ID.']);
    exit;
}
$sql = "DELETE FROM appointments WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $appointment_id);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error.']);
}
$stmt->close();
$conn->close(); 