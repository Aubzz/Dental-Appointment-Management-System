<?php
// receptionist_signin_process.php
require_once '../config.php';

header('Content-Type: application/json');
$response = ['success' => false, 'error' => 'An unknown error occurred.'];


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $receptionist_id_input = trim($_POST['receptionist_id'] ?? ''); // CHANGED variable and POST key
    $password = $_POST['password'] ?? '';

    if (empty($receptionist_id_input) || empty($password)) {
        $response['error'] = "Receptionist ID and password are required."; // CHANGED message
        echo json_encode($response);
        exit;
    }

    // Query the 'receptionists' table using the database column `employeeId`
    // If you renamed the DB column to `receptionistId`, change it here too.
    $sql = "SELECT id, firstName, email, password, is_verified FROM receptionists WHERE employeeId = ?"; // Using DB column 'employeeId'

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $receptionist_id_input);

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();

                if (password_verify($password, $user['password'])) {
                    if (isset($user['is_verified']) && $user['is_verified'] != 1) {
                        $response['error'] = "Your account is pending admin verification. Please try again later.";
                    } else {
                        $_SESSION["loggedin"] = true;
                        $_SESSION["user_id"] = $user['id'];
                        $_SESSION["user_firstName"] = $user['firstName'];
                        $_SESSION["role"] = 'receptionist'; // Implicitly receptionist for this process
                        $_SESSION["user_email"] = $user['email'];

                        $response['success'] = true;
                        $response['message'] = 'Sign in successful!';
                        $response['redirect_url'] = 'receptionist_dashboard.php';
                    }
                } else {
                    $response['error'] = "Invalid Receptionist ID or password."; // CHANGED message
                }
            } else {
                $response['error'] = "No account found with that Receptionist ID."; // CHANGED message
            }
        } else {
            $response['error'] = "Database query failed. Please try again.";
            error_log("Receptionist sign-in execute error: " . $stmt->error);
        }
        $stmt->close();
    } else {
        $response['error'] = "Database error. Please try again.";
        error_log("Receptionist sign-in prepare error: " . $conn->error);
    }
    $conn->close();
} else {
    $response['error'] = "Invalid request method.";
}

echo json_encode($response);
exit;
?>