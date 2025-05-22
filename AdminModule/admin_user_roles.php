<?php
require_once '../config.php';
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit;
}
$admins = $conn->query("SELECT id, username FROM admins")->fetch_all(MYSQLI_ASSOC);
$doctors = $conn->query("SELECT id, firstName, lastName, email FROM doctors")->fetch_all(MYSQLI_ASSOC);
$receptionists = $conn->query("SELECT id, firstName, lastName, email FROM receptionists")->fetch_all(MYSQLI_ASSOC);
$patients = $conn->query("SELECT id, firstName, lastName, email FROM patients")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Roles - Admin</title>
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .role-table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
        .role-table th, .role-table td { padding: 12px 16px; border-bottom: 1px solid #e0e0e0; text-align: left; }
        .role-table th { background: #16a085; color: #fff; }
        .role-table tr:nth-child(even) { background: #f8f9fa; }
        .role-table tr:hover { background: #e0f7fa; }
        h2 { color: #16a085; margin-top: 40px; }
        
    </style>
</head>
<body>
    <div class="admin-page-wrapper">
        <header class="admin-header">
            <h1>User Roles</h1>
        </header>
        <main class="admin-main-content">
            <h2>Admins</h2>
            <table class="role-table">
                <thead><tr><th>ID</th><th>Username</th></tr></thead>
                <tbody>
                    <?php foreach ($admins as $admin): ?>
                        <tr><td><?php echo htmlspecialchars($admin['id']); ?></td><td><?php echo htmlspecialchars($admin['username']); ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <h2>Doctors</h2>
            <table class="role-table">
                <thead><tr><th>ID</th><th>Name</th><th>Email</th></tr></thead>
                <tbody>
                    <?php foreach ($doctors as $doc): ?>
                        <tr><td><?php echo htmlspecialchars($doc['id']); ?></td><td><?php echo htmlspecialchars($doc['firstName'] . ' ' . $doc['lastName']); ?></td><td><?php echo htmlspecialchars($doc['email']); ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <h2>Receptionists</h2>
            <table class="role-table">
                <thead><tr><th>ID</th><th>Name</th><th>Email</th></tr></thead>
                <tbody>
                    <?php foreach ($receptionists as $rec): ?>
                        <tr><td><?php echo htmlspecialchars($rec['id']); ?></td><td><?php echo htmlspecialchars($rec['firstName'] . ' ' . $rec['lastName']); ?></td><td><?php echo htmlspecialchars($rec['email']); ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <h2>Patients</h2>
            <table class="role-table">
                <thead><tr><th>ID</th><th>Name</th><th>Email</th></tr></thead>
                <tbody>
                    <?php foreach ($patients as $pat): ?>
                        <tr><td><?php echo htmlspecialchars($pat['id']); ?></td><td><?php echo htmlspecialchars($pat['firstName'] . ' ' . $pat['lastName']); ?></td><td><?php echo htmlspecialchars($pat['email']); ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    </div>
</body>
</html> 