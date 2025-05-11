<?php
// ReceptionistModule/update_receptionist_profile.php

// Add for debugging backend issues
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config.php'; // Includes session_start() and $conn

header('Content-Type: application/json');
$response = ['success' => false, 'message' => '', 'errors' => [], 'updated_data' => null];

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'receptionist') {
    $response['message'] = 'Unauthorized access.';
    echo json_encode($response);
    exit;
}

$receptionist_id = $_SESSION['user_id'] ?? null;

if (!$receptionist_id) {
    $response['message'] = 'User session error.';
    echo json_encode($response);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstName = trim(filter_input(INPUT_POST, 'firstName', FILTER_SANITIZE_STRING));
    $lastName = trim(filter_input(INPUT_POST, 'lastName', FILTER_SANITIZE_STRING));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $phoneNumber = trim(filter_input(INPUT_POST, 'phoneNumber', FILTER_SANITIZE_STRING));

    // Backend Logging: Check received data
    error_log("[Update Profile] Received POST data for receptionist ID {$receptionist_id}: " . print_r($_POST, true));


    if (empty($firstName)) { $response['errors']['firstName'] = "First name is required."; }
    if (empty($lastName)) { $response['errors']['lastName'] = "Last name is required."; }
    if (empty($email)) { 
        $response['errors']['email'] = "Email is required."; 
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['errors']['email'] = "Invalid email format.";
    }
    if (!empty($phoneNumber) && !preg_match('/^[0-9\s\-\+\(\)]+$/', $phoneNumber)) {
         $response['errors']['phoneNumber'] = "Invalid phone number format.";
    }

    if (empty($response['errors']['email'])) {
        $sql_check_email = "SELECT id FROM receptionists WHERE email = ? AND id != ?";
        if ($stmt_check = $conn->prepare($sql_check_email)) {
            $stmt_check->bind_param("si", $email, $receptionist_id);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                $response['errors']['email'] = "Email address is already in use by another account.";
            }
            $stmt_check->close();
        } else {
            $response['message'] = "Database error during email check.";
            error_log("[Update Profile] DB Error (email check): " . $conn->error);
            echo json_encode($response); exit;
        }
    }

    if (empty($response['errors']) && empty($response['message'])) {
        $sql_update = "UPDATE receptionists SET firstName = ?, lastName = ?, email = ?, phoneNumber = ? WHERE id = ?";

        error_log("[Update Profile] SQL Update Query: " . $sql_update); // Log the query

        if ($stmt_update = $conn->prepare($sql_update)) {
            $stmt_update->bind_param("ssssi", $firstName, $lastName, $email, $phoneNumber, $receptionist_id);
            if ($stmt_update->execute()) {
                error_log("[Update Profile] Execute successful. Affected rows: " . $stmt_update->affected_rows);
                if ($stmt_update->affected_rows > 0) {
                    $response['success'] = true;
                    $response['message'] = 'Profile updated successfully!';
                    
                    $_SESSION['user_firstName'] = $firstName;
                    $_SESSION['user_lastName'] = $lastName;
                    // $_SESSION['user_email'] = $email; // Update if you store and use email widely from session

                    $response['updated_data'] = [
                        'firstName' => $firstName,
                        'lastName' => $lastName,
                        'email' => $email,
                        'phoneNumber' => $phoneNumber ?? '' // Ensure phoneNumber is always set
                    ];
                    $_SESSION['form_message'] = ['type' => 'success', 'text' => 'Profile updated successfully!'];
                } else {
                    // If no rows affected, it means the data submitted was the same as what's already in the DB, or ID not found
                    if ($stmt_update->errno) { // Check for actual execution error
                        $response['message'] = 'Error updating profile: ' . $stmt_update->error;
                        $_SESSION['form_message'] = ['type' => 'error', 'text' => 'Profile update failed due to a database error.'];
                        error_log("[Update Profile] DB Error (update receptionist execute): " . $stmt_update->error);
                    } else {
                        $response['message'] = 'No changes were made to the profile.';
                        $_SESSION['form_message'] = ['type' => 'info', 'text' => 'No changes were made to the profile.'];
                        $response['success'] = true; // Still considered success if no data changed but no error
                        // Send back original data if no changes or ensure updated_data reflects this
                         $response['updated_data'] = [
                            'firstName' => $firstName, // send back what was submitted
                            'lastName' => $lastName,
                            'email' => $email,
                            'phoneNumber' => $phoneNumber ?? ''
                        ];
                    }
                }
            } else {
                $response['message'] = 'Error executing profile update: ' . $stmt_update->error;
                $_SESSION['form_message'] = ['type' => 'error', 'text' => 'Error executing profile update.'];
                error_log("[Update Profile] DB Error (update receptionist execute failed): " . $stmt_update->error);
            }
            $stmt_update->close();
        } else {
            $response['message'] = 'Database error preparing update: ' . $conn->error;
            $_SESSION['form_message'] = ['type' => 'error', 'text' => 'Database error during profile update preparation.'];
            error_log("[Update Profile] DB Prepare Error (update receptionist): " . $conn->error);
        }
    } else {
        if (empty($response['message'])) {
             $response['message'] = "Please correct the form errors."; // More generic for multiple errors
        }
        // $_SESSION['form_message'] is not set here for validation errors; let JS show them.
    }
} else {
    $response['message'] = 'Invalid request method.';
}

if ($conn) $conn->close();
echo json_encode($response);
?>