<?php
// api/list_files.php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Ensure only logged-in users can list files
if (!isLoggedIn()) {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$mysqli = get_db_connection();
$user_id = getUserId();

// Fetch files uploaded by the current user from the database
// We only fetch what's needed for the display and actions
$stmt = $mysqli->prepare('SELECT Id, Original_name, Size_bytes, Upload_time FROM files WHERE Uploader_id = ? ORDER BY Upload_time DESC');
if ($stmt) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $files = [];
    while ($row = $result->fetch_assoc()) {
        $files[] = [
            'id' => $row['Id'],
            'original_name' => htmlspecialchars($row['Original_name']), // Escape for display
            'size_bytes' => round($row['Size_bytes'] / 1024, 2) . ' KB', // Format size
            'upload_time' => date('Y-m-d H:i:s', strtotime($row['Upload_time'])) // Format date
        ];
    }
    $stmt->close();
    echo json_encode($files);

} else {
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Database query failed.']);
}
?>