<?php
// signin_process.php
require_once '../config.php'; // Includes session_start()

$email = $password = "";
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validate email
    if (empty(trim($_POST["email"]))) {
        $errors[] = "Please enter your email.";
    } else {
        $email = trim($_POST["email"]);
    }

    // Validate password
    if (empty(trim($_POST["password"]))) {
        $errors[] = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }

    // If no validation errors
    if (empty($errors)) {
        // Assuming your patients table also has firstName and lastName
        $sql = "SELECT id, firstName, lastName, email, password FROM patients WHERE email = ?";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_email);
            $param_email = $email;

            if ($stmt->execute()) {
                $stmt->store_result();

                if ($stmt->num_rows == 1) {
                    $stmt->bind_result($id, $firstName, $lastName, $db_email, $hashed_password); // Added $lastName
                    if ($stmt->fetch()) {
                        if (password_verify($password, $hashed_password)) {
                            // Password is correct, start a new session (already started in config.php)
                            
                            // Regenerate session ID for security after login
                            session_regenerate_id(true); 

                            $_SESSION["loggedin"] = true;
                            $_SESSION["user_id"] = $id;
                            $_SESSION["user_firstName"] = $firstName;
                            $_SESSION["user_lastName"] = $lastName; // Store lastName if available and needed
                            $_SESSION["user_email"] = $db_email;
                            $_SESSION["role"] = "patient"; // <<< --- THIS WAS THE MISSING PIECE ---

                            // Redirect user to a welcome/dashboard page
                            header("location: patient_dashboard.php");
                            exit(); // Important to exit after header
                        } else {
                            // Password is not valid
                            $errors[] = "The password you entered was not valid.";
                        }
                    }
                } else {
                    // Email doesn't exist
                    $errors[] = "No account found with that email.";
                }
            } else {
                $errors[] = "Oops! Something went wrong executing the query. Please try again later.";
                error_log("Signin Process DB Execute Error: " . $stmt->error); // Log DB error
            }
            $stmt->close();
        } else {
            $errors[] = "Oops! Something went wrong preparing the query. Please try again later.";
            error_log("Signin Process DB Prepare Error: " . $conn->error); // Log DB error
        }
    }
    // $conn->close(); // Generally not needed at the end of script if connection is persistent or script ends

    // If there were errors, store them in session and redirect back to signin
    if (!empty($errors)) {
        $_SESSION['signin_errors'] = $errors;
        $_SESSION['form_data_signin'] = $_POST; // To repopulate the email field
        header("location: patient_signin.php");
        exit(); // Important to exit after header
    }
} else {
    // If not a POST request, redirect to signin page
    header("location: patient_signin.php");
    exit(); // Important to exit after header
}
?>