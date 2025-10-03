<?php
// api/remove_file.php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Ensure only logged-in users can remove files
if (!isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Error: Unauthorized access to delete files.']);
    exit();
}

// Validate CSRF token
if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Error: Invalid CSRF token.']);
    exit();
}

$file_id = filter_input(INPUT_POST, 'file_id', FILTER_VALIDATE_INT);

if (!$file_id) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Error: Invalid file ID specified for removal.']);
    exit();
}

$mysqli = get_db_connection();
$user_id = getUserId();
$upload_dir = '../uploads/'; // <--- THIS IS THE CRUCIAL CHANGE

// First, get the stored filename from the database, ensuring it belongs to the current user
$stmt = $mysqli->prepare('SELECT Stored_name, Original_name FROM files WHERE Id = ? AND Uploader_id = ?');
if ($stmt) {
    $stmt->bind_param('ii', $file_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $file_data = $result->fetch_assoc();
        $stored_filename = $file_data['Stored_name'];
        $original_filename = $file_data['Original_name'];
        $filepath = $upload_dir . $stored_filename;
        $stmt->close(); // Close this statement before starting another

        // Attempt to delete the physical file
        if (file_exists($filepath) && is_file($filepath)) {
            if (unlink($filepath)) {
                // If file deleted successfully, remove its record from the database
                $delete_stmt = $mysqli->prepare('DELETE FROM files WHERE Id = ?');
                if ($delete_stmt) {
                    $delete_stmt->bind_param('i', $file_id);
                    if ($delete_stmt->execute()) {
                        echo json_encode(['success' => true, 'message' => "File '{$original_filename}' successfully removed."]);
                    } else {
                        // Log this: File was deleted, but DB record failed. Manual cleanup needed.
                        http_response_code(500);
                        echo json_encode(['success' => false, 'message' => "Error: File deleted from server, but DB record failed. Contact support."]);
                    }
                    $delete_stmt->close();
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Error: Database delete statement failed.']);
                }
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => "Error: Failed to remove file '{$original_filename}' from server. Check folder permissions."]);
            }
        } else {
            // File not found on disk but in DB. Remove from DB.
            $delete_stmt = $mysqli->prepare('DELETE FROM files WHERE Id = ?');
            if ($delete_stmt) {
                $delete_stmt->bind_param('i', $file_id);
                $delete_stmt->execute();
                $delete_stmt->close();
            }
            // This message is a bit ambiguous, changed for clarity.
            echo json_encode(['success' => false, 'message' => 'Warning: File record found, but physical file was not on server disk. Record removed from database.']);
        }
    } else {
        http_response_code(403); // Forbidden, file either doesn't exist or doesn't belong to user
        echo json_encode(['success' => false, 'message' => 'Error: File not found or access denied.']);
    }
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: Database query failed.']);
}