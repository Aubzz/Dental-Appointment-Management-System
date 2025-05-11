<?php
// UserModule/fetch_patient_notifications.php
require_once '../config.php'; // Includes session_start() and $conn

header('Content-Type: application/json');
$response = ['count' => 0, 'notifications' => [], 'error' => null];

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["user_id"]) || $_SESSION['role'] !== 'patient') {
    $response['error'] = 'Unauthorized';
    echo json_encode($response);
    exit;
}

$userId = (int)$_SESSION["user_id"];
$notifications_data = [];
$unread_count = 0;

// Fetch a few recent unread notifications for the dropdown, then a few recent read ones if space
$sql = "SELECT id, title, message, link, is_read, created_at, icon_class
        FROM notifications
        WHERE patient_id = ?
        ORDER BY is_read ASC, created_at DESC
        LIMIT 7"; // Fetch a bit more to try and fill the dropdown

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $userId);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            if ($row['is_read'] == 0) {
                $unread_count++;
            }
            $notifications_data[] = [
                'id' => $row['id'],
                'title' => htmlspecialchars($row['title'] ?? 'Notification'),
                'message' => htmlspecialchars(substr($row['message'] ?? 'New update.', 0, 100)) . (strlen($row['message'] ?? '') > 100 ? '...' : ''), // Truncate message
                'link' => !empty($row['link']) ? htmlspecialchars($row['link']) : '#',
                'is_read' => (int)$row['is_read'],
                'time_ago' => time_elapsed_string_patient($row['created_at']), // Use a specific helper
                'icon_class' => htmlspecialchars($row['icon_class'] ?? 'fas fa-info-circle')
            ];
        }
    } else {
        $response['error'] = 'Failed to fetch notifications.';
        error_log("Fetch Patient Notifications DB Error: " . $stmt->error);
    }
    $stmt->close();
} else {
    $response['error'] = 'Database prepare error for notifications.';
    error_log("Fetch Patient Notifications Prepare Error: " . $conn->error);
}

// Separate query for accurate total unread count for the badge
$sql_total_unread = "SELECT COUNT(*) as count FROM notifications WHERE patient_id = ? AND is_read = 0";
if ($stmt_total_unread = $conn->prepare($sql_total_unread)) {
    $stmt_total_unread->bind_param("i", $userId);
    if ($stmt_total_unread->execute()) {
        $result_total_unread = $stmt_total_unread->get_result();
        if($row_total_unread = $result_total_unread->fetch_assoc()){
            $unread_count = (int)$row_total_unread['count'];
        }
    }
    $stmt_total_unread->close();
}


$response['count'] = $unread_count;
$response['notifications'] = array_slice($notifications_data, 0, 5); // Send only top 5 for dropdown

echo json_encode($response);
$conn->close();

// Helper function (can be in a shared utility file)
function time_elapsed_string_patient($datetime, $full = false) {
    $now = new DateTime;
    try { $ago = new DateTime($datetime); } catch (Exception $e) { return $datetime; }
    $diff = $now->diff($ago);
    $diff->w = floor($diff->d / 7); $diff->d -= $diff->w * 7;
    $string = array('y' => 'year', 'm' => 'month', 'w' => 'week', 'd' => 'day', 'h' => 'hour', 'i' => 'minute', 's' => 'second');
    foreach ($string as $k => &$v) { if ($diff->$k) { $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : ''); } else { unset($string[$k]); } }
    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
?>