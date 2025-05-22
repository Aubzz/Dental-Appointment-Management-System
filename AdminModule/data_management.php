<?php
require_once '../config.php';
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit;
}
$current_page = basename($_SERVER['PHP_SELF']);
$feedback = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['backup_now'])) {
        // Real backup using mysqldump
        $db_host = DB_SERVER;
        $db_user = DB_USERNAME;
        $db_pass = DB_PASSWORD;
        $db_name = DB_NAME;
        $backup_file = sys_get_temp_dir() . '/clinic_backup_' . date('Ymd_His') . '.sql';
        $mysqldump = "mysqldump --user={$db_user} --password={$db_pass} --host={$db_host} {$db_name} > {$backup_file}";
        system($mysqldump, $retval);
        if (file_exists($backup_file)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=\'clinic_backup_' . date('Ymd_His') . '.sql\'');
            readfile($backup_file);
            unlink($backup_file);
            exit;
        } else {
            $feedback = '<div class="feedback-message alert-error">Backup failed. Please check server permissions.</div>';
        }
    } elseif (isset($_POST['restore_backup'])) {
        // Real restore using mysql
        if (isset($_FILES['restore_file']) && $_FILES['restore_file']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['restore_file']['tmp_name'];
            $file_name = $_FILES['restore_file']['name'];
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            if ($ext === 'sql') {
                $db_host = DB_SERVER;
                $db_user = DB_USERNAME;
                $db_pass = DB_PASSWORD;
                $db_name = DB_NAME;
                $mysql = "mysql --user={$db_user} --password={$db_pass} --host={$db_host} {$db_name} < {$file_tmp}";
                system($mysql, $retval);
                if ($retval === 0) {
                    $feedback = '<div class="feedback-message alert-success">Database restored successfully.</div>';
                } else {
                    $feedback = '<div class="feedback-message alert-error">Restore failed. Please check the SQL file and server permissions.</div>';
                }
            } else {
                $feedback = '<div class="feedback-message alert-error">Invalid file type. Please upload a .sql file.</div>';
            }
        } else {
            $feedback = '<div class="feedback-message alert-error">No file uploaded or upload error.</div>';
        }
    } elseif (isset($_POST['import_data'])) {
        $feedback = '<div class="form-feedback-message success">Data imported (simulated).</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Management - Escosia Dental Clinic</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .settings-section { background: #fff; border-radius: 16px; box-shadow: 0 2px 12px rgba(22,160,133,0.07); padding: 32px 36px 28px 36px; margin-bottom: 32px; max-width: 700px; margin-left: auto; margin-right: auto; }
        .settings-section h2 { color: #16a085; font-size: 1.3em; font-weight: 700; margin-bottom: 18px; letter-spacing: 0.5px; }
        .settings-section form label { display: block; margin-bottom: 12px; font-weight: 500; color: #2c3e50; }
        .settings-section input[type="file"] { margin-top: 8px; margin-bottom: 8px; }
        .settings-section button.btn {
            padding: 10px 28px;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 600;
            border: none;
            margin-top: 10px;
            margin-right: 10px;
            background: linear-gradient(90deg, #16a085 60%, #1abc9c 100%);
            color: #fff;
            box-shadow: 0 2px 8px rgba(22,160,133,0.08);
            cursor: pointer;
            opacity: 1;
            min-width: 150px;
            height: 40px;
        }
        .settings-section button.btn-secondary { background: #e0f2f1; color: #16a085; border: 1.5px solid #16a085; box-shadow: none; }
        .form-feedback-message { padding: 15px; margin-bottom: 20px; border-radius: 5px; font-size: 0.95em; text-align: center; }
        .form-feedback-message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .form-feedback-message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .custom-file-input-wrapper {
            position: relative;
            display: inline-block;
            width: 250px;
            margin-top: 10px;
        }
        .custom-file-input {
            opacity: 0;
            width: 100%;
            height: 40px;
            position: absolute;
            left: 0;
            top: 0;
            cursor: pointer;
            z-index: 2;
        }
        .custom-file-label {
            display: inline-block;
            background: #e0f2f1;
            color: #16a085;
            border: 1.5px solid #16a085;
            border-radius: 8px;
            padding: 10px 18px;
            font-size: 1em;
            font-weight: 600;
            text-align: center;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
            z-index: 1;
            position: relative;
            min-width: 160px;
            height: 40px;
            line-height: 20px;
        }
        .custom-file-label.selected {
            background: #d4f9e5;
            color: #0A744F;
        }
        .custom-file-filename {
            display: block;
            margin-top: 6px;
            color: #388e5c;
            font-size: 0.98em;
            font-style: italic;
        }
        .card-section {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            padding: 2rem 2.5rem 1.5rem 2.5rem;
            margin-bottom: 2rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        .card-section h3 {
            color: #16a085;
            margin-bottom: 1.2rem;
            font-size: 1.25rem;
            font-weight: 600;
        }
        .backup-restore-row {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }
        .btn-main, .btn-outline {
            padding: 0.7rem 1.5rem;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
            min-width: 150px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            /* margin-top: 20px; */
        }
        .btn-main {
            background: #16a085;
            color: #fff;
            margin-bottom: 10px;
            text-decoration: none;
        }
        .btn-main:hover {
            background: #138d75;
        }
        .btn-outline {
            background: #eafaf1;
            color: #16a085;
            border: 1.5px solid #16a085;
        }
        .btn-outline:hover {
            background: #d1f2eb;
        }
        .custom-file-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .custom-file-input {
            opacity: 0;
            position: absolute;
            left: 0;
            top: 0;
            width: 150px;
            height: 44px;
            cursor: pointer;
            z-index: 2;
        }
        .custom-file-label {
            background: #eafaf1;
            color: #16a085;
            border: 1.5px solid #16a085;
            border-radius: 6px;
            padding: 0.7rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            min-width: 180px;
            width: 180px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: background 0.2s, color 0.2s;
        }
        .custom-file-label:hover {
            background: #d1f2eb;
        }
        .restore-btn-align, .import-btn-align {
            margin-left: 40px;
        }
        .file-name-display {
            font-size: 0.97rem;
            color: #888;
            margin-left: 0.5rem;
            margin-right: 0.5rem;
            margin-bottom: 17px;
            min-width: 120px;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        @media (max-width: 600px) {
            .card-section {
                padding: 1.2rem 0.7rem 1rem 0.7rem;
            }
            .backup-restore-row {
                flex-direction: column;
                align-items: stretch;
                gap: 0.7rem;
            }
            .custom-file-label, .btn-main, .btn-outline {
                width: 100%;
                min-width: unset;
            }
            .file-name-display {
                max-width: 100%;
            }
        }
        .action-row {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 0.5rem;
        }
        .file-row {
            display: flex;
            justify-content: center;
            margin-top: 0.7rem;
            margin-bottom: 0.5rem;
        }
        @media (max-width: 700px) {
            .action-row, .file-row {
                flex-direction: column;
                align-items: stretch;
                gap: 0.7rem;
            }
            .custom-file-label, .btn-main, .btn-outline {
                width: 100%;
                min-width: unset;
            }
            .file-name-display {
                max-width: 100%;
            }
        }
        .feedback-message {
            margin: 0 auto 1.5rem auto;
            max-width: 700px;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            font-size: 1.08rem;
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            text-align: center;
            z-index: 100;
        }
        .feedback-message.alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1.5px solid #b2dfdb;
        }
        .feedback-message.alert-error {
            background: #ffebee;
            color: #c62828;
            border: 1.5px solid #ffcdd2;
        }
    </style>
</head>
<body class="admin-layout-page">
    <div class="admin-page-wrapper">
        <header class="admin-top-header">
            <div class="header-logo-title">
                <img src="../images/tooth.png" alt="Clinic Logo" class="logo-icon">
                <span class="clinic-name">Escosia Dental Clinic</span>
            </div>
            <div class="header-user-actions">
                <a href="#" class="notification-bell-link"><i class="fas fa-bell"></i><span class="notification-dot"></span></a>
                <div class="user-profile-info">
                    <span class="user-name"><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Admin User'; ?></span>
                    <span class="user-role-display">Admin</span>
                </div>
            </div>
        </header>
        <div class="admin-body-content">
            <aside class="admin-sidebar">
                <nav class="sidebar-nav">
                    <ul>
                        <li><a href="admin_dashboard.php" class="<?php echo ($current_page === 'admin_dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                        <li><a href="admin_user_management.php" class="<?php echo ($current_page === 'admin_user_management.php') ? 'active' : ''; ?>"><i class="fas fa-user-check"></i> User Management</a></li>
                        <li><a href="appointments.php" class="<?php echo ($current_page === 'appointments.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Appointments</a></li>
                        <li><a href="system_settings.php" class="<?php echo ($current_page === 'system_settings.php') ? 'active' : ''; ?>"><i class="fas fa-cog"></i> System Settings</a></li>
                        <li><a href="reports_analytics.php" class="<?php echo ($current_page === 'reports_analytics.php') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Reports & Analytics</a></li>
                        <li><a href="data_management.php" class="active"><i class="fas fa-database"></i> Data Management</a></li>
                        <li><a href="security_controls.php" class="<?php echo ($current_page === 'security_controls.php') ? 'active' : ''; ?>"><i class="fas fa-shield-alt"></i> Security Controls</a></li>
                        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </nav>
            </aside>
            <main class="admin-main-panel">
                <div class="main-panel-content-wrapper">
                    <h1 class="panel-page-title">Data Management</h1>
                    <?php if (!empty($feedback)): ?>
                        <div class="feedback-message <?php echo strpos($feedback, 'success') !== false ? 'alert-success' : 'alert-error'; ?>"><?php echo $feedback; ?></div>
                    <?php endif; ?>
                    <div class="settings-section">
                        <h2>Backup Database</h2>
                        <form method="post">
                            <div class="action-row">
                                <button type="submit" name="backup_now" class="btn-main"><i class="fas fa-download"></i> Backup Now</button>
                            </div>
                        </form>
                    </div>
                    <div class="settings-section">
                        <h2>Restore Database</h2>
                        <form method="post" enctype="multipart/form-data">
                            <div class="action-row">
                                <div class="custom-file-input-wrapper">
                                    <input type="file" name="restore_file" id="restoreFileInput" class="custom-file-input" required />
                                    <label for="restoreFileInput" class="custom-file-label" id="restoreFileLabel"><i class="fas fa-upload"></i> Choose a file</label>
                                    <span class="file-name-display" id="restoreFileNameDisplay">No file chosen</span>
                                </div>
                                <button type="submit" name="restore_backup" class="btn-main restore-btn-align"><i class="fas fa-undo"></i> Restore Backup</button>
                            </div>
                        </form>
                    </div>
                    <div class="settings-section">
                        <h2>Export Data</h2>
                        <div style="display:flex; gap:10px; flex-wrap:wrap;">
                            <a href="export_patients.php" class="btn-main"><i class="fas fa-users"></i> Export Patients (CSV)</a>
                            <a href="export_appointments.php" class="btn-main"><i class="fas fa-calendar-alt"></i> Export Appointments (CSV)</a>
                        </div>
                    </div>
                    <div class="settings-section">
                        <h2>Import Data</h2>
                        <form method="post" enctype="multipart/form-data">
                            <div class="action-row">
                                <div class="custom-file-input-wrapper">
                                    <input type="file" name="import_file" id="importFileInput" class="custom-file-input" required>
                                    <label for="importFileInput" class="custom-file-label" id="importFileLabel"><i class="fas fa-upload"></i> Choose a file</label>
                                    <span class="file-name-display" id="importFileName">No file chosen</span>
                                </div>
                                <button type="submit" name="import_data" class="btn-main import-btn-align"><i class="fas fa-file-import"></i> Import</button>
                            </div>
                        </form>
                    </div>
                    <div class="settings-section">
                        <h2>Data Retention Policy</h2>
                        <div style="color:#555; font-size:1em;">
                            <p>All patient and appointment data is retained for a minimum of 5 years as per clinic policy and local regulations. Data can be deleted upon request or after the retention period expires, subject to admin approval.</p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const backupFileInput = document.getElementById('backupFileInput');
        const fileNameDisplay = document.getElementById('fileNameDisplay');
        if (backupFileInput) {
            backupFileInput.addEventListener('change', function() {
                fileNameDisplay.textContent = this.files[0] ? this.files[0].name : 'No file chosen';
            });
        }
        const restoreFileInput = document.getElementById('restoreFileInput');
        const restoreFileNameDisplay = document.getElementById('restoreFileNameDisplay');
        const restoreFileLabel = document.getElementById('restoreFileLabel');
        if (restoreFileInput) {
            restoreFileInput.addEventListener('change', function() {
                restoreFileNameDisplay.textContent = this.files[0] ? this.files[0].name : 'No file chosen';
                if (this.files[0]) {
                    restoreFileLabel.classList.add('selected');
                } else {
                    restoreFileLabel.classList.remove('selected');
                }
            });
        }
        const importFileInput = document.getElementById('importFileInput');
        const importFileName = document.getElementById('importFileName');
        const importFileLabel = document.getElementById('importFileLabel');
        if (importFileInput) {
            importFileInput.addEventListener('change', function() {
                importFileName.textContent = this.files[0] ? this.files[0].name : 'No file chosen';
                if (this.files[0]) {
                    importFileLabel.classList.add('selected');
                } else {
                    importFileLabel.classList.remove('selected');
                }
            });
        }
        var feedback = document.querySelector('.feedback-message');
        if (feedback) {
            setTimeout(function() {
                feedback.style.display = 'none';
            }, 3500);
        }
    });
    </script>
</body>
</html> 