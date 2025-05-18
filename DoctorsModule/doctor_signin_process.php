<?php
require_once '../config.php';
header('Content-Type: application/json');
$response = ['success' => false, 'error' => 'An unknown error occurred.'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $response['error'] = "Email and password are required.";
        echo json_encode($response); exit;
    }

    $sql = "SELECT id, firstName, lastName, email, password, is_verified, is_active FROM doctors WHERE email = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    if ($user['is_verified'] != 1) {
                        $response['error'] = "Your account is pending admin verification.";
                    } elseif ($user['is_active'] != 1) {
                        $response['error'] = "Your account is not active.";
                    } else {
                        $_SESSION["loggedin"] = true;
                        $_SESSION["user_id"] = $user['id'];
                        $_SESSION["user_firstName"] = $user['firstName'];
                        $_SESSION["user_lastName"] = $user['lastName'];
                        $_SESSION["role"] = 'doctor';
                        $_SESSION["user_email"] = $user['email'];
                        $response['success'] = true;
                        $response['message'] = 'Sign in successful!';
                        $response['redirect_url'] = 'doctor_dashboard.php';
                    }
                } else {
                    $response['error'] = "Invalid email or password.";
                }
            } else {
                $response['error'] = "No account found with that email.";
            }
        }
        $stmt->close();
    }
    $conn->close();
}
echo json_encode($response);
exit;