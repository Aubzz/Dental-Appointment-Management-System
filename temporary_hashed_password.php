<?php
// AdminModule/generate_admin_hash.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$plainPassword = 'adminpassword123'; // Use the EXACT password you intend to log in with
$hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

echo "<h3>Admin Credential Generation</h3>";
echo "<strong>Username:</strong> admin<br>";
echo "<strong>Plain Password (for testing):</strong> " . htmlspecialchars($plainPassword) . "<br>";
echo "<strong>Generated Hashed Password (COPY THIS ENTIRE STRING):</strong><br>";
echo "<textarea rows='3' cols='70' readonly>" . htmlspecialchars($hashedPassword) . "</textarea><br><br>";

// Optional: Verify immediately
if (password_verify($plainPassword, $hashedPassword)) {
    echo "<strong style='color:green;'>Verification SUCCESSFUL with the just-generated hash.</strong><br>";
} else {
    echo "<strong style='color:red;'>Verification FAILED with the just-generated hash. (This should NOT happen!)</strong><br>";
}

echo "<p><strong>Instructions:</strong></p>";
echo "<ol>";
echo "<li>Carefully copy the ENTIRE 'Generated Hashed Password' string from the textarea above.</li>";
echo "<li>Go to phpMyAdmin, select your 'dams_db' database, then the 'admins' table.</li>";
echo "<li>Find the row where 'username' is 'admin'. If it doesn't exist, insert a new row with 'username' = 'admin' and 'email' = 'admin@example.com' (or your desired admin email).</li>";
echo "<li>Click 'Edit' for that row.</li>";
echo "<li>Paste the copied hash into the 'password_hash' field for that admin user. Ensure no extra spaces before or after.</li>";
echo "<li>Make sure the 'username' column for this row is exactly 'admin' (all lowercase, no spaces).</li>";
echo "<li>Save the changes in phpMyAdmin.</li>";
echo "<li>Attempt to log in using 'admin' and 'adminpassword123' on your admin login page.</li>";
echo "</ol>";

if ($result->num_rows == 1) {
    $admin_user = $result->fetch_assoc();
    echo "<pre>";
    echo "Input password: " . htmlspecialchars($admin_password_input) . "\n";
    echo "Hash from DB: " . htmlspecialchars($admin_user['password_hash']) . "\n";
    echo "password_verify: " . (password_verify($admin_password_input, $admin_user['password_hash']) ? "true" : "false") . "\n";
    echo "</pre>";
    exit; // Uncomment to stop here and see the output
}
?>