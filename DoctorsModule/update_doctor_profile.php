<?php
require_once '../config.php';

header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'doctor') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$doctor_id = $_SESSION['user_id'] ?? null;
if (!$doctor_id) {
    echo json_encode(['success' => false, 'error' => 'No doctor ID found.']);
    exit;
}

// Get POST data
$firstName = trim($_POST['firstName'] ?? '');
$lastName = trim($_POST['lastName'] ?? '');
$email = trim($_POST['email'] ?? '');
$phoneNumber = trim($_POST['phoneNumber'] ?? '');
$experience_years = trim($_POST['experience_years'] ?? '');
$consultation_fee = trim($_POST['consultation_fee'] ?? '');
$bio = trim($_POST['bio'] ?? '');

$errors = [];
if (empty($firstName)) $errors[] = "First name is required.";
if (empty($lastName)) $errors[] = "Last name is required.";
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
if (!empty($phoneNumber) && !preg_match('/^\d{11}$/', $phoneNumber)) $errors[] = "Phone number must be 11 digits.";
if ($experience_years !== '' && (!is_numeric($experience_years) || $experience_years < 0)) $errors[] = "Years of experience must be a non-negative number.";
if ($consultation_fee !== '' && (!is_numeric($consultation_fee) || $consultation_fee < 0)) $errors[] = "Consultation fee must be a non-negative number.";

if ($errors) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// Update doctor profile
$sql = "UPDATE doctors SET firstName=?, lastName=?, email=?, phoneNumber=?, experience_years=?, consultation_fee=?, bio=? WHERE id=?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param(
        "sssssdsi",
        $firstName,
        $lastName,
        $email,
        $phoneNumber,
        $experience_years,
        $consultation_fee,
        $bio,
        $doctor_id
    );
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
}
$conn->close();
?>
