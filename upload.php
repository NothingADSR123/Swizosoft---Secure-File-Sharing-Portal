<?php
// upload.php
require_once 'includes/db.php';
require_once 'includes/auth.php';

header('Content-Type: application/json');

// Ensure only logged-in users can upload
if (!isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Upload failed: Unauthorized access.']);
    exit();
}

// Validate CSRF token
if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Upload failed: Invalid CSRF token.']);
    exit();
}

if (!isset($_FILES['fileToUpload']) || $_FILES['fileToUpload']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Upload failed: No file selected or file error.']);
    exit();
}

$file = $_FILES['fileToUpload'];
$original_name = basename($file['name']); // Get original filename
$mime_type = mime_content_type($file['tmp_name']); // Get actual MIME type
$size_bytes = $file['size'];
$uploader_id = getUserId();

// --- Server-side validation (CRITICAL) ---
$allowed_ext = ['pdf', 'jpg', 'jpeg', 'png', 'docx'];
$max_size = 5 * 1024 * 1024; // 5MB

$ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

if (!in_array($ext, $allowed_ext)) {
    echo json_encode(['success' => false, 'message' => 'Upload failed: Invalid file type. Allowed: ' . implode(', ', $allowed_ext)]);
    exit();
}
if ($size_bytes > $max_size) {
    echo json_encode(['success' => false, 'message' => 'Upload failed: File is too large (Max 5MB).']);
    exit();
}
// You can also add MIME type validation here if needed, comparing $mime_type against expected types for $ext.

// Generate a secure unique filename for storage
// IMPORTANT: Store files outside the webroot if possible.
// For now, using the recommended 'uploads/' inside htdocs (with .htaccess protection).
$stored_name = bin2hex(random_bytes(16)) . '_' . time() . '.' . $ext;
$upload_dir = 'uploads/'; // Relative to your secure-file-sharing/
$target_filepath = $upload_dir . $stored_name;

// Ensure upload directory exists
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true); // Use 0755 permissions for directories
}

// Attempt to move the uploaded file
if (move_uploaded_file($file['tmp_name'], $target_filepath)) {
    $mysqli = get_db_connection();
    $stmt = $mysqli->prepare('INSERT INTO files (Original_name, Stored_name, Uploader_id, Mime_type, Size_bytes) VALUES (?, ?, ?, ?, ?)');
    if ($stmt) {
        $stmt->bind_param('ssisi', $original_name, $stored_name, $uploader_id, $mime_type, $size_bytes);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => "File '{$original_name}' uploaded successfully."]);
        } else {
            // If DB insert fails, delete the file to prevent orphaned files
            unlink($target_filepath);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Upload failed: Could not record file in database.']);
        }
        $stmt->close();
    } else {
        unlink($target_filepath);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Upload failed: Database error.']);
    }
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Upload failed: Could not move file to destination. Check directory permissions.']);
}
?>