<?php
// AdminModule/admin_process_verification.php
require_once '../config.php';

// Basic Admin Check
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'Unauthorized access.'];
    header('Location: admin_login.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $account_id = filter_input(INPUT_POST, 'account_id', FILTER_VALIDATE_INT);
    $account_type = filter_input(INPUT_POST, 'account_type', FILTER_SANITIZE_STRING);
    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);

    if (!$account_id || !$account_type || !$action) {
        $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'Invalid request parameters.'];
        header('Location: admin_user_management.php');
        exit;
    }

    $table_name = "";
    $status_column = ""; // Column name for verification/activation status
    $pending_status_value = 0; // Value indicating a pending state
    $approved_status_value = 1; // Value indicating an approved/active state

    if ($account_type === 'receptionist') {
        $table_name = "receptionists";
        $status_column = "is_verified";
    } elseif ($account_type === 'doctor') {
        $table_name = "doctors";
        $status_column = "is_active"; // Doctors use is_active
    } else {
        $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'Invalid account type specified.'];
        header('Location: admin_user_management.php');
        exit;
    }

    $sql = "";
    $success_message = "";
    $error_message_action = "";

    if ($action === 'approve') {
        $sql = "UPDATE $table_name SET $status_column = ? WHERE id = ? AND $status_column = ?";
        $success_message = ucfirst($account_type) . " account (ID: $account_id) has been approved/activated.";
        $error_message_action = "approving/activating";
    } elseif ($action === 'reject') {
        // For simplicity, we'll delete rejected pending accounts.
        // You could also mark them (e.g., set is_verified = 2 or is_active = 2).
        $sql = "DELETE FROM $table_name WHERE id = ? AND $status_column = ?";
        $success_message = ucfirst($account_type) . " account (ID: $account_id) has been rejected and removed.";
        $error_message_action = "rejecting";
    } else {
        $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'Invalid action specified.'];
        header('Location: admin_user_management.php');
        exit;
    }

    if (!empty($sql)) {
        if ($stmt = $conn->prepare($sql)) {
            if ($action === 'approve') {
                $stmt->bind_param("iii", $approved_status_value, $account_id, $pending_status_value);
            } elseif ($action === 'reject') { // For DELETE
                $stmt->bind_param("ii", $account_id, $pending_status_value);
            }
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $_SESSION['admin_message'] = ['type' => 'success', 'text' => $success_message];
                    // TODO: Implement email notification if desired
                } else {
                     $_SESSION['admin_message'] = ['type' => 'warning', 'text' => "No " . $account_type . " account found to update/delete for ID: $account_id, or it was already processed (status was not $pending_status_value)."];
                }
            } else {
                $_SESSION['admin_message'] = ['type' => 'error', 'text' => "Error $error_message_action account: " . $stmt->error];
                error_log("Admin verification - $error_message_action error: " . $stmt->error . " for ID: $account_id, Type: $account_type, SQL: " . $sql);
            }
            $stmt->close();
        } else {
            $_SESSION['admin_message'] = ['type' => 'error', 'text' => "Error preparing statement: " . $conn->error];
            error_log("Admin verification - prepare error: " . $conn->error . " SQL: " . $sql);
        }
    }
    
    if (isset($conn) && $conn instanceof mysqli && $conn->thread_id) {
        $conn->close();
    }
    header('Location: admin_user_management.php');
    exit;
} else {
    $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'Invalid request method.'];
    header('Location: admin_user_management.php');
    exit;
}
?>