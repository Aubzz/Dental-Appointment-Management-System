<?php
require_once '../config.php'; // For session and DB connection

// Admin Check
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit;
}

$user_type = $_GET['type'] ?? 'patient'; // Default to patient, can be 'receptionist', 'doctor'
$page_title = "Manage " . ucfirst($user_type) . "s";
$table_name = "";
$columns_to_select = "id, firstName, lastName, email, is_verified, created_at"; // Common columns
$id_column_name = "id"; // Default ID column

switch ($user_type) {
    case 'receptionist':
        $table_name = "receptionists";
        $columns_to_select .= ", employeeId, phoneNumber"; // Receptionist specific
        $id_column_name = "employeeId"; // Assuming this is the displayed ID
        break;
    case 'doctor':
        $table_name = "doctors";
        $columns_to_select .= ", employeeId, specialization, phoneNumber"; // Doctor specific
        $id_column_name = "employeeId";
        break;
    case 'patient':
    default:
        $table_name = "patients"; // Assuming you have a patients table
        $columns_to_select .= ", phoneNumber, dob"; // Patient specific
        $user_type = 'patient'; // Ensure default
        break;
}

$users = [];
if (!empty($table_name)) {
    $sql = "SELECT $columns_to_select FROM $table_name ORDER BY lastName, firstName";
    if ($stmt = $conn->prepare($sql)) {
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $users = $result->fetch_all(MYSQLI_ASSOC);
        } else {
            error_log("Error fetching users ($user_type): " . $stmt->error);
            $_SESSION['admin_message'] = ['type' => 'error', 'text' => "Error fetching users."];
        }
        $stmt->close();
    } else {
        error_log("Error preparing statement for users ($user_type): " . $conn->error);
        $_SESSION['admin_message'] = ['type' => 'error', 'text' => "Database error."];
    }
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
                <a href="admin_dashboard.php">Dashboard</a> » <?php echo htmlspecialchars($page_title); ?>
            </nav>
            <h2><?php echo htmlspecialchars($page_title); ?></h2>

            <?php
            if (isset($_SESSION['admin_message'])) {
                echo "<div class='admin-message " . htmlspecialchars($_SESSION['admin_message']['type']) . "'>" . htmlspecialchars($_SESSION['admin_message']['text']) . "</div>";
                unset($_SESSION['admin_message']);
            }
            ?>

            <div class="actions-bar">
                <a href="admin_edit_user.php?action=add&type=<?php echo $user_type; ?>" class="btn-add-new"><i class="fas fa-plus-circle"></i> Add New <?php echo ucfirst($user_type); ?></a>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <?php if ($user_type === 'receptionist' || $user_type === 'doctor'): ?>
                            <th><?php echo ucfirst($user_type); ?> ID</th>
                        <?php endif; ?>
                        <?php if ($user_type === 'doctor'): ?>
                            <th>Specialization</th>
                        <?php endif; ?>
                        <th>Status</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['firstName'] . ' ' . $user['lastName']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <?php if ($user_type === 'receptionist' || $user_type === 'doctor'): ?>
                                <td><?php echo htmlspecialchars($user[$id_column_name] ?? 'N/A'); ?></td>
                            <?php endif; ?>
                            <?php if ($user_type === 'doctor' && isset($user['specialization'])): ?>
                                <td><?php echo htmlspecialchars($user['specialization']); ?></td>
                            <?php endif; ?>
                            <td><?php echo ($user['is_verified'] == 1) ? '<span class="status-active">Active</span>' : '<span class="status-pending">Pending/Inactive</span>'; ?></td>
                            <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($user['created_at']))); ?></td>
                            <td class="actions-cell">
                                <a href="admin_edit_user.php?action=edit&type=<?php echo $user_type; ?>&id=<?php echo $user['id']; ?>" class="btn-action edit"><i class="fas fa-edit"></i> Edit</a>
                                <form action="admin_process_user.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this <?php echo $user_type; ?>? This cannot be undone.');">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="user_type" value="<?php echo $user_type; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="btn-action delete"><i class="fas fa-trash-alt"></i> Delete</button>
                                </form>
                                <?php if ($user_type !== 'patient' && $user['is_verified'] == 0): ?>
                                     <form action="admin_process_verification.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="account_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="account_type" value="<?php echo $user_type; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn-action approve"><i class="fas fa-check-circle"></i> Approve</button>
                                    </form>
                                <?php elseif ($user_type !== 'patient' && $user['is_verified'] == 1): ?>
                                    <!-- Option to deactivate -->
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7">No <?php echo $user_type; ?>s found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
        <footer class="admin-footer">
            <p>© <?php echo date("Y"); ?> Escosia Dental Clinic - Admin Panel</p>
        </footer>
    </div>
</body>
</html>