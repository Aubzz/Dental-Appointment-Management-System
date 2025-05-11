<?php
// signup_process.php
require_once '../config.php'; // Includes functions like encrypt_data, $conn, and starts session

// Initialize variables
$firstName = $lastName = $email = $password = $confirmPassword = $phoneNumber = $dob = $gender = "";
$medicalInfoPlain = "";
$encryptedMedicalInfo = null;
$errors = [];
$form_data_to_return = []; // To store data for repopulating form on error

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Store original POST data for potential repopulation
    $form_data_to_return = $_POST;

    // --- Validation ---

    // Validate first name
    if (empty(trim($_POST["firstName"]))) {
        $errors[] = "Please enter your first name.";
    } else {
        $firstName = trim($_POST["firstName"]);
    }

    // Validate last name
    if (empty(trim($_POST["lastName"]))) {
        $errors[] = "Please enter your last name.";
    } else {
        $lastName = trim($_POST["lastName"]);
    }

    // Validate email
    $email_input = trim($_POST["email"]);
    if (empty($email_input)) {
        $errors[] = "Please enter your email address.";
    } elseif (!filter_var($email_input, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } else {
        // Check if email already exists
        $sql_check_email = "SELECT id FROM patients WHERE email = ?";
        if ($stmt_check_email = $conn->prepare($sql_check_email)) {
            $stmt_check_email->bind_param("s", $param_email_check);
            $param_email_check = $email_input;
            if ($stmt_check_email->execute()) {
                $stmt_check_email->store_result();
                if ($stmt_check_email->num_rows == 1) {
                    $errors[] = "This email is already registered.";
                } else {
                    $email = $email_input; // Assign valid, unique email
                }
            } else {
                $errors[] = "Oops! Something went wrong validating email. Please try again later.";
                error_log("Email validation execute error: " . $stmt_check_email->error);
            }
            $stmt_check_email->close();
        } else {
            $errors[] = "Oops! Something went wrong preparing email validation. Please try again later.";
            error_log("Email validation prepare error: " . $conn->error);
        }
    }

    // Enhanced Password Validation
    $password_input = $_POST["password"]; // No trim on password
    if (empty($password_input)) {
        $errors[] = "Please create a password.";
    } else {
        // Check requirements (length, uppercase, lowercase, number, special char)
        if (strlen($password_input) < 8) { $errors[] = "Password must be at least 8 characters long."; }
        if (!preg_match('/[A-Z]/', $password_input)) { $errors[] = "Password must contain at least one uppercase letter."; }
        if (!preg_match('/[a-z]/', $password_input)) { $errors[] = "Password must contain at least one lowercase letter."; }
        if (!preg_match('/[0-9]/', $password_input)) { $errors[] = "Password must contain at least one number."; }
        if (!preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $password_input)) { $errors[] = "Password must contain at least one special character."; }
    }

    // Validate confirm password
    if (empty($_POST["confirmPassword"])) {
        $errors[] = "Please confirm your password.";
    } else {
        $confirmPassword = $_POST["confirmPassword"]; // No trim
        if (!empty($password_input) && ($password_input != $confirmPassword)) {
            $errors[] = "Passwords do not match.";
        }
    }

    // Validate Phone Number (Server-side)
    $phoneNumber_input = trim($_POST["phoneNumber"]);
    if (empty($phoneNumber_input)) {
        $errors[] = "Please enter your phone number.";
    } elseif (!preg_match('/^\d{11}$/', $phoneNumber_input)) {
        $errors[] = "Phone number must be exactly 11 digits and contain only numbers.";
    } else {
        $phoneNumber = $phoneNumber_input; // Assign if valid
    }

    // Validate Date of Birth
    if (empty(trim($_POST["dob"]))) {
        $errors[] = "Please enter your date of birth.";
    } else {
        $dob = trim($_POST["dob"]);
        // Optional: Add more validation for date format or age range if needed
    }

    // *** VALIDATE GENDER ***
    $gender_input = trim($_POST["gender"] ?? ''); // Retrieve gender
    $allowed_genders = ['male', 'female', 'other', 'prefer_not_say'];
    if (empty($gender_input)) {
        $errors[] = "Please select your gender.";
    } elseif (!in_array($gender_input, $allowed_genders)) {
        $errors[] = "Invalid gender selected.";
        error_log("Invalid gender value received during signup: " . htmlspecialchars($gender_input));
    } else {
        $gender = $gender_input; // Assign valid gender
    }
    // *** END GENDER VALIDATION ***

    // Process Medical Info (Encryption)
    $medicalInfoPlain = trim($_POST["medicalInfo"]);
    $encryptedMedicalInfo = null; // Default to null
    if (!empty($medicalInfoPlain)) {
        $encryptedMedicalInfo = encrypt_data($medicalInfoPlain); // Assumes encrypt_data is in config.php
        if ($encryptedMedicalInfo === false) {
            error_log("CRITICAL: Medical info encryption failed for new user with email: " . htmlspecialchars($email_input));
            // Decide if this is a fatal error stopping signup, or just log and proceed without medical info
            $errors[] = "An internal error occurred while processing your information. Please contact support.";
            // $encryptedMedicalInfo = null; // Ensure it's null if encryption failed
        }
    }

    // --- Database Insertion ---
    // Check for errors before attempting to insert
    if (empty($errors)) {
        // Assign password only if validation passed
        $password = $password_input;
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Prepare an insert statement
        // *** ADDED 'gender' column and '?' placeholder ***
        $sql_insert_patient = "INSERT INTO patients (firstName, lastName, email, password, phoneNumber, dob, medicalInfo, gender) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        if ($stmt_insert_patient = $conn->prepare($sql_insert_patient)) {
            // Bind variables to the prepared statement as parameters
            // *** ADDED 's' for gender type and $param_gender_db variable ***
            $stmt_insert_patient->bind_param("ssssssss",
                                             $param_firstName,
                                             $param_lastName,
                                             $param_email_db,
                                             $param_password_db,
                                             $param_phoneNumber_db,
                                             $param_dob_db,
                                             $param_medicalInfo_db,
                                             $param_gender_db); // Added gender parameter

            // Set parameters
            $param_firstName = $firstName;
            $param_lastName = $lastName;
            $param_email_db = $email;
            $param_password_db = $hashed_password;
            $param_phoneNumber_db = $phoneNumber;
            $param_dob_db = $dob;
            $param_medicalInfo_db = $encryptedMedicalInfo; // Use encrypted value (or null)
            // *** ASSIGN GENDER PARAMETER ***
            $param_gender_db = $gender; // Assign the validated gender value

            // Attempt to execute the prepared statement
            if ($stmt_insert_patient->execute()) {
                // Redirect to login page with success message
                $_SESSION['success_message'] = "Account created successfully! Please sign in.";
                header("location: patient_signin.php");
                exit(); // Important to exit after redirect
            } else {
                // If execution fails
                $errors[] = "Something went wrong with account creation. Please try again later.";
                error_log("Patient insert execute error: " . $stmt_insert_patient->error);
            }
            // Close statement
            $stmt_insert_patient->close();
        } else {
            // If preparation fails
            $errors[] = "Something went wrong preparing account creation. Please try again later.";
            error_log("Patient insert prepare error: " . $conn->error);
        }
    } // End if(empty($errors))

    // --- Handle Errors ---
    // If there were any errors (validation or DB insertion), redirect back to signup form
    if (!empty($errors)) {
        $_SESSION['signup_errors'] = $errors;
        // Repopulate form data, ensuring password is not sent back
        unset($form_data_to_return['password'], $form_data_to_return['confirmPassword']);
        // Add the plain medical info back if needed
        $form_data_to_return['medicalInfo'] = $medicalInfoPlain;
        $_SESSION['form_data'] = $form_data_to_return;
        header("location: patient_signup.php");
        exit(); // Important to exit after redirect
    }

} else {
    // If not a POST request, redirect to signup page
    header("location: patient_signup.php");
    exit();
}

// Close connection if it's still open (might be closed already in case of early exit)
if (isset($conn) && $conn instanceof mysqli && $conn->thread_id) {
    $conn->close();
}
?>