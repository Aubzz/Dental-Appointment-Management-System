<?php
require_once '../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit;
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="appointments_export_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper Excel encoding
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV Headers
$headers = array(
    'Appointment ID',
    'Patient Name',
    'Dentist Name',
    'Service',
    'Date',
    'Time',
    'Status',
    'Notes',
    'Created At'
);

// Write headers
fputcsv($output, $headers);

// Fetch appointments data with patient and dentist names
$query = "SELECT a.id as appointment_id, 
          CONCAT(p.firstName, ' ', p.lastName) as patient_name,
          CONCAT(d.firstName, ' ', d.lastName) as dentist_name,
          a.service_type, a.appointment_date, a.appointment_time, a.status, a.notes, a.created_at
          FROM appointments a
          LEFT JOIN patients p ON a.patient_id = p.id
          LEFT JOIN doctors d ON a.attending_dentist = d.id
          ORDER BY a.appointment_date DESC, a.appointment_time DESC";
$result = mysqli_query($conn, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Prepare row data
        $data = array(
            $row['appointment_id'],
            $row['patient_name'],
            $row['dentist_name'],
            $row['service_type'],
            $row['appointment_date'],
            $row['appointment_time'],
            $row['status'],
            $row['notes'],
            $row['created_at']
        );
        
        // Write row to CSV
        fputcsv($output, $data);
    }
}

// Close the output stream
fclose($output);
exit; 