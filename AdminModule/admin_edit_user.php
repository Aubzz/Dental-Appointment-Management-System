<?php
require_once '../config.php';

// Admin Check
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit;
}

$action = $_GET['action'] ?? 'add'; // 'add' or 'edit'
$user_type = $_GET['type'] ?? 'patient';
$user_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;

$page_title = ($action === 'edit' ? "Edit " : "Add New ") . ucfirst($user_type);
$form_action_url = "admin_process_user.php";

$user_data = [
    'firstName' => '', 'lastName' => '', 'email' => '',
    'phoneNumber' => '', 'employeeId' => '', 'specialization' => '',
    'dob' => '', 'is_verified' => 1 // Default to active for new staff, or 0 if verification is separate
]; // Default empty values
$table_name = "";
$id_column_for_query = "id";

switch ($user_type) {
    case 'receptionist':
        $table_name = "receptionists";
        break;
    case 'doctor':
        $table_name = "doctors";
        break;
    case 'patient':
    default:
        $table_name = "patients";
        $user_type = 'patient';
        $user_data['is_verified'] = 1; // Patients usually active by default
        break;
}

if ($action === 'edit' && $user_id && !empty($table_name)) {
    $sql = "SELECT * FROM $table_name WHERE $id_column_for_query = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows === 1) {
                $user_data = $result->fetch_assoc();
            } else {
                $_SESSION['admin_message'] = ['type' => 'error', 'text' => ucfirst($user_type) . " not found."];
                header("Location: admin_manage_users.php?type=$user_type");
                exit;
            }
        } else {
            error_log("Error fetching user for edit: " . $stmt->error);
            $_SESSION['admin_message'] = ['type' => 'error', 'text' => "Error fetching user data."];
        }
        $stmt->close();
    } else {
        error_log("Error preparing statement for edit user: " . $conn->error);
        $_SESSION['admin_message'] = ['type' => 'error', 'text' => "Database error."];
    }
} elseif ($action === 'edit' && !$user_id) {
    $_SESSION['admin_message'] = ['type' => 'error', 'text' => "Invalid user ID for edit."];
    header("Location: admin_manage_users.php?type=$user_type");
    exit;
}

// Retrieve errors and form data from session for repopulation if validation failed
$form_errors = $_SESSION['form_errors'] ?? [];
$form_input = $_SESSION['form_input'] ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_input']);

// Override user_data with session data if available (after a failed POST)
if (!empty($form_input)) {
    $user_data = array_merge($user_data, $form_input);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title); ?> - Admin Panel</title>
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="admin-page-wrapper">
         <header class="admin-header">
            <div class="admin-logo">
                 <i class="fas fa-tooth logo-icon-header"></i><h1>Admin Panel</h1>
            </div>
            <div class="admin-user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>!</span>
                <a href="../logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </header>
        <main class="admin-main-content">
            <nav class="breadcrumbs">
                <a href="admin_dashboard.php">Dashboard</a> »
                <a href="admin_manage_users.php?type=<?php echo $user_type; ?>">Manage <?php echo ucfirst($user_type); ?>s</a> »
                <?php echo htmlspecialchars($page_title); ?>
            </nav>
            <h2><?php echo htmlspecialchars($page_title); ?></h2>

            <?php if (!empty($form_errors)): ?>
                <div class="admin-message error">
                    <p>Please correct the following errors:</p>
                    <ul>
                        <?php foreach ($form_errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
             <?php // Display general session message if set from other operations
            if (isset($_SESSION['admin_message']) && empty($form_errors)) {
                echo "<div class='admin-message " . htmlspecialchars($_SESSION['admin_message']['type']) . "'>" . htmlspecialchars($_SESSION['admin_message']['text']) . "</div>";
                unset($_SESSION['admin_message']);
            }
            ?>


            <form action="<?php echo $form_action_url; ?>" method="POST" class="admin-form">
                <input type="hidden" name="action" value="<?php echo $action; ?>">
                <input type="hidden" name="user_type" value="<?php echo $user_type; ?>">
                <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_data['id'] ?? ''); ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="firstName">First Name:</label>
                    <input type="text" id="firstName" name="firstName" value="<?php echo htmlspecialchars($user_data['firstName']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="lastName">Last Name:</label>
                    <input type="text" id="lastName" name="lastName" value="<?php echo htmlspecialchars($user_data['lastName']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                </div>

                <?php if ($user_type === 'patient' || $user_type === 'receptionist' || $user_type === 'doctor'): ?>
                <div class="form-group">
                    <label for="phoneNumber">Phone Number:</label>
                    <input type="tel" id="phoneNumber" name="phoneNumber" value="<?php echo htmlspecialchars($user_data['phoneNumber'] ?? ''); ?>" pattern="^\d{11}$" title="Phone number must be 11 digits.">
                </div>
                <?php endif; ?>

                <?php if ($user_type === 'receptionist' || $user_type === 'doctor'): ?>
                <div class="form-group">
                    <label for="employeeId"><?php echo ucfirst($user_type); ?> ID:</label>
                    <input type="text" id="employeeId" name="employeeId" value="<?php echo htmlspecialchars($user_data['employeeId'] ?? ''); ?>" required 
                           pattern="<?php echo ($user_type === 'receptionist' ? '^REP-\d{4}$' : '^DOC-\d{4}$'); ?>" 
                           title="Format: <?php echo ($user_type === 'receptionist' ? 'REP-XXXX' : 'DOC-XXXX'); ?>">
                </div>
                <?php endif; ?>

                <?php if ($user_type === 'doctor'): ?>
                <div class="form-group">
                    <label for="specialization">Specialization:</label>
                    <input type="text" id="specialization" name="specialization" value="<?php echo htmlspecialchars($user_data['specialization'] ?? ''); ?>">
                </div>
                <?php endif; ?>
                
                <?php if ($user_type === 'patient'): ?>
                <div class="form-group">
                    <label for="dob">Date of Birth:</label>
                    <input type="date" id="dob" name="dob" value="<?php echo htmlspecialchars($user_data['dob'] ?? ''); ?>">
                </div>
                <?php endif; ?>


                <div class="form-group">
                    <label for="password">Password: <?php if ($action === 'edit') echo "(Leave blank to keep current)"; ?></label>
                    <input type="password" id="password" name="password" <?php if ($action === 'add') echo "required"; ?> autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label for="confirmPassword">Confirm Password:</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" <?php if ($action === 'add') echo "required"; ?> autocomplete="new-password">
                </div>

                <?php if ($user_type !== 'patient'): // Staff accounts can be active or inactive/pending ?>
                <div class="form-group">
                    <label for="is_verified">Account Status:</label>
                    <select id="is_verified" name="is_verified">
                        <option value="1" <?php echo (isset($user_data['is_verified']) && $user_data['is_verified'] == 1) ? 'selected' : ''; ?>>Active / Verified</option>
                        <option value="0" <?php echo (isset($user_data['is_verified']) && $user_data['is_verified'] == 0) ? 'selected' : ''; ?>>Inactive / Pending Verification</option>
                    </select>
                </div>
                <?php endif; ?>


                <div class="form-actions">
                    <button type="submit" class="btn-submit"><?php echo ($action === 'edit' ? "Update " : "Create ") . ucfirst($user_type); ?></button>
                    <a href="admin_manage_users.php?type=<?php echo $user_type; ?>" class="btn-cancel">Cancel</a>
                </div>
            </form>
        </main>
         <footer class="admin-footer">
            <p>© <?php echo date("Y"); ?> Escosia Dental Clinic - Admin Panel</p>
        </footer>
    </div>
</body>
</html>