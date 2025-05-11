<?php
// AdminModule/admin_process_user.php
require_once '../config.php';

// Admin Check
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'Unauthorized access.'];
    header('Location: admin_login.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? null;
    $user_type = $_POST['user_type'] ?? null;
    $user_id = isset($_POST['user_id']) ? filter_var($_POST['user_id'], FILTER_VALIDATE_INT) : null;

    $table_name = "";
    $redirect_url = "admin_manage_users.php";
    $id_column_db = "id"; // The actual PK column name in DB

    switch ($user_type) {
        case 'receptionist':
            $table_name = "receptionists";
            $redirect_url .= "?type=receptionist";
            break;
        case 'doctor':
            $table_name = "doctors";
            $redirect_url .= "?type=doctor";
            break;
        case 'patient':
            $table_name = "patients";
            $redirect_url .= "?type=patient";
            break;
        default:
            $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'Invalid user type.'];
            header("Location: admin_dashboard.php"); // Fallback redirect
            exit;
    }

    // --- DELETE ACTION ---
    if ($action === 'delete' && $user_id) {
        $sql = "DELETE FROM $table_name WHERE $id_column_db = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $user_id);
            if ($stmt->execute()) {
                $_SESSION['admin_message'] = ['type' => 'success', 'text' => ucfirst($user_type) . " deleted successfully."];
            } else {
                $_SESSION['admin_message'] = ['type' => 'error', 'text' => "Error deleting " . $user_type . ": " . $stmt->error];
                error_log("Error deleting $user_type (ID: $user_id): " . $stmt->error);
            }
            $stmt->close();
        } else {
            $_SESSION['admin_message'] = ['type' => 'error', 'text' => "Database error preparing delete statement."];
            error_log("Error preparing delete statement for $user_type: " . $conn->error);
        }
        header("Location: $redirect_url");
        exit;
    }

    // --- ADD or EDIT ACTION ---
    // Common fields
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? ''; // Only for add or if changing
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    $is_verified = isset($_POST['is_verified']) ? (int)$_POST['is_verified'] : ($user_type === 'patient' ? 1 : 0); // Default for staff is pending

    // Type-specific fields
    $phoneNumber = trim($_POST['phoneNumber'] ?? '');
    $employeeId = trim($_POST['employeeId'] ?? ''); // For receptionist/doctor
    $specialization = trim($_POST['specialization'] ?? ''); // For doctor
    $dob = trim($_POST['dob'] ?? ''); // For patient

    $errors = [];
    $_SESSION['form_input'] = $_POST; // Store for repopulation

    // Server-side validation (add more as needed)
    if (empty($firstName)) $errors[] = "First Name is required.";
    if (empty($lastName)) $errors[] = "Last Name is required.";
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    if ($action === 'add' && empty($password)) $errors[] = "Password is required for new users.";
    if (!empty($password) && strlen($password) < 8) $errors[] = "Password must be at least 8 characters.";
    // Add more password complexity checks here
    if (!empty($password) && $password !== $confirmPassword) $errors[] = "Passwords do not match.";

    if ($user_type === 'receptionist' || $user_type === 'doctor') {
        if (empty($employeeId)) $errors[] = ucfirst($user_type) . " ID is required.";
        // Add format validation for employeeId based on user_type
        $id_pattern = ($user_type === 'receptionist') ? '/^REP-\d{4}$/' : '/^DOC-\d{4}$/';
        if (!empty($employeeId) && !preg_match($id_pattern, $employeeId)) {
             $errors[] = ucfirst($user_type) . " ID format is invalid. Expected " . (($user_type === 'receptionist') ? 'REP-XXXX' : 'DOC-XXXX');
        }
    }
    // Add more specific validations based on user_type

    // Check for duplicate email (excluding current user if editing)
    $sql_email_check = "SELECT $id_column_db FROM $table_name WHERE email = ?";
    $params_email_check = [$email];
    if ($action === 'edit') {
        $sql_email_check .= " AND $id_column_db != ?";
        $params_email_check[] = $user_id;
    }
    if ($stmt_email = $conn->prepare($sql_email_check)) {
        $types_email = str_repeat('s', count($params_email_check) - ($action === 'edit' ? 1 : 0)) . ($action === 'edit' ? 'i' : '');
        if (count($params_email_check) === 1) $stmt_email->bind_param("s", $params_email_check[0]);
        else $stmt_email->bind_param($types_email, ...$params_email_check);

        if ($stmt_email->execute()) {
            $stmt_email->store_result();
            if ($stmt_email->num_rows > 0) $errors[] = "Email address already in use.";
        }
        $stmt_email->close();
    }

    // Check for duplicate employeeId (for staff, excluding current user if editing)
    if (($user_type === 'receptionist' || $user_type === 'doctor') && !empty($employeeId)) {
        $sql_empid_check = "SELECT $id_column_db FROM $table_name WHERE employeeId = ?";
        $params_empid_check = [$employeeId];
        if ($action === 'edit') {
            $sql_empid_check .= " AND $id_column_db != ?";
            $params_empid_check[] = $user_id;
        }
        if ($stmt_empid = $conn->prepare($sql_empid_check)) {
            $types_empid = str_repeat('s', count($params_empid_check) - ($action === 'edit' ? 1 : 0)) . ($action === 'edit' ? 'i' : '');
             if (count($params_empid_check) === 1) $stmt_empid->bind_param("s", $params_empid_check[0]);
             else $stmt_empid->bind_param($types_empid, ...$params_empid_check);

            if ($stmt_empid->execute()) {
                $stmt_empid->store_result();
                if ($stmt_empid->num_rows > 0) $errors[] = ucfirst($user_type) . " ID already in use.";
            }
            $stmt_empid->close();
        }
    }


    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        header("Location: admin_edit_user.php?action=$action&type=$user_type" . ($user_id ? "&id=$user_id" : ""));
        exit;
    }

    // Proceed with INSERT or UPDATE
    $hashedPassword = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null;

    if ($action === 'add') {
        $sql = ""; $params = []; $types = "";
        if ($user_type === 'receptionist') {
            $sql = "INSERT INTO receptionists (firstName, lastName, email, password, phoneNumber, employeeId, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $params = [$firstName, $lastName, $email, $hashedPassword, $phoneNumber, $employeeId, $is_verified];
            $types = "ssssssi";
        } elseif ($user_type === 'doctor') {
            $sql = "INSERT INTO doctors (firstName, lastName, email, password, phoneNumber, employeeId, specialization, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [$firstName, $lastName, $email, $hashedPassword, $phoneNumber, $employeeId, $specialization, $is_verified];
            $types = "sssssssi";
        } elseif ($user_type === 'patient') {
            $sql = "INSERT INTO patients (firstName, lastName, email, password, phoneNumber, dob, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?)"; // Assuming is_verified for patients
            $params = [$firstName, $lastName, $email, $hashedPassword, $phoneNumber, $dob, $is_verified];
            $types = "ssssssi";
        }
        // ... more types

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param($types, ...$params);
            if ($stmt->execute()) {
                $_SESSION['admin_message'] = ['type' => 'success', 'text' => ucfirst($user_type) . " added successfully."];
                unset($_SESSION['form_input']);
            } else {
                $_SESSION['admin_message'] = ['type' => 'error', 'text' => "Error adding " . $user_type . ": " . $stmt->error];
                error_log("Error adding $user_type: " . $stmt->error . " SQL: $sql");
                header("Location: admin_edit_user.php?action=$action&type=$user_type"); // Go back to form with errors
                exit;
            }
            $stmt->close();
        } else {
             $_SESSION['admin_message'] = ['type' => 'error', 'text' => "Database error preparing add statement."];
             error_log("Error preparing add statement for $user_type: " . $conn->error);
        }

    } elseif ($action === 'edit' && $user_id) {
        $sql_parts = []; $params = []; $types = "";

        $sql_parts[] = "firstName = ?"; $params[] = $firstName; $types .= "s";
        $sql_parts[] = "lastName = ?"; $params[] = $lastName; $types .= "s";
        $sql_parts[] = "email = ?"; $params[] = $email; $types .= "s";
        if (!empty($hashedPassword)) {
            $sql_parts[] = "password = ?"; $params[] = $hashedPassword; $types .= "s";
        }
        $sql_parts[] = "phoneNumber = ?"; $params[] = $phoneNumber; $types .= "s";
        $sql_parts[] = "is_verified = ?"; $params[] = $is_verified; $types .= "i";

        if ($user_type === 'receptionist' || $user_type === 'doctor') {
            $sql_parts[] = "employeeId = ?"; $params[] = $employeeId; $types .= "s";
        }
        if ($user_type === 'doctor') {
            $sql_parts[] = "specialization = ?"; $params[] = $specialization; $types .= "s";
        }
        if ($user_type === 'patient') {
            $sql_parts[] = "dob = ?"; $params[] = $dob; $types .= "s";
        }
        // ... more type specific fields

        $params[] = $user_id; // For WHERE clause
        $types .= "i";
        $sql = "UPDATE $table_name SET " . implode(", ", $sql_parts) . " WHERE $id_column_db = ?";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param($types, ...$params);
            if ($stmt->execute()) {
                $_SESSION['admin_message'] = ['type' => 'success', 'text' => ucfirst($user_type) . " updated successfully."];
                unset($_SESSION['form_input']);
            } else {
                $_SESSION['admin_message'] = ['type' => 'error', 'text' => "Error updating " . $user_type . ": " . $stmt->error];
                error_log("Error updating $user_type (ID: $user_id): " . $stmt->error . " SQL: $sql");
                 header("Location: admin_edit_user.php?action=$action&type=$user_type&id=$user_id"); // Go back to form with errors
                exit;
            }
            $stmt->close();
        } else {
             $_SESSION['admin_message'] = ['type' => 'error', 'text' => "Database error preparing update statement."];
             error_log("Error preparing update statement for $user_type: " . $conn->error);
        }
    }

    header("Location: $redirect_url");
    exit;

} else {
    $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'Invalid request method.'];
    header("Location: admin_dashboard.php");
    exit;
}
?>