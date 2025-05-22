<?php
require_once '../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit;
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="patients_export_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper Excel encoding
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV Headers
$headers = array(
    'Patient ID',
    'First Name',
    'Last Name',
    'Email',
    'Phone',
    'Date of Birth',
    'Gender',
    'Medical Info'
);

// Write headers
fputcsv($output, $headers);

// Fetch patients data
$query = "SELECT id, firstName, lastName, email, phoneNumber, dob, gender, medicalInfo FROM patients ORDER BY lastName, firstName";
$result = mysqli_query($conn, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Decrypt medical info if needed
        $medicalInfo = !empty($row['medicalInfo']) ? decrypt_data($row['medicalInfo']) : 'None';
        
        // Prepare row data
        $data = array(
            $row['id'],
            $row['firstName'],
            $row['lastName'],
            $row['email'],
            $row['phoneNumber'],
            $row['dob'],
            $row['gender'],
            $medicalInfo
        );
        
        // Write row to CSV
        fputcsv($output, $data);
    }
}

// Close the output stream
fclose($output);
exit; 