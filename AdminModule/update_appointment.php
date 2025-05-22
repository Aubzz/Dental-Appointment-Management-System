<?php
require_once '../config.php';
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}
$appointment_id = $_POST['appointment_id'] ?? null;
$notes = $_POST['notes'] ?? '';
$status = $_POST['status'] ?? '';
if (!$appointment_id || !$status) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields.']);
    exit;
}
$sql = "UPDATE appointments SET notes = ?, status = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ssi', $notes, $status, $appointment_id);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error.']);
}
$stmt->close();
$conn->close(); 