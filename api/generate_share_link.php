<?php
// api/generate_share_link.php
require_once '../includes/db.php';
require_once '../includes/auth.php'; // For isLoggedIn, getUserId, CSRF

header('Content-Type: application/json');

// Ensure user is logged in
if (!isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Error: Unauthorized access.']);
    exit();
}

// Validate CSRF token
if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Error: Invalid CSRF token.']);
    exit();
}

$file_id = filter_input(INPUT_POST, 'file_id', FILTER_VALIDATE_INT);
$expires_at_str = filter_input(INPUT_POST, 'expires_at', FILTER_SANITIZE_STRING); // Optional: 'YYYY-MM-DD HH:MM:SS'

if (!$file_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Error: Invalid file ID.']);
    exit();
}

$mysqli = get_db_connection();
$user_id = getUserId();

// 1. Verify the user owns the file
$stmt = $mysqli->prepare('SELECT Id FROM files WHERE Id = ? AND Uploader_id = ?');
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error (file ownership check).']);
    exit();
}
$stmt->bind_param('ii', $file_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Error: File not found or you do not own this file.']);
    $stmt->close();
    exit();
}
$stmt->close();

// 2. Generate a unique token
$token = bin2hex(random_bytes(32)); // Generates a 64-character hex token

// 3. Determine expiration date
$expires_at = null;
if (!empty($expires_at_str)) {
    // Basic validation, enhance as needed
    $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $expires_at_str);
    if ($dateTime && $dateTime->getTimestamp() > time()) {
        $expires_at = $expires_at_str;
    }
}
// Default expiration (e.g., 7 days from now) if not provided or invalid
if ($expires_at === null) {
    $expires_at = date('Y-m-d H:i:s', strtotime('+7 days'));
}


// 4. Store the share link in the database
$stmt = $mysqli->prepare('INSERT INTO share_links (File_id, Token, Expires_at) VALUES (?, ?, ?)');
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error (insert share link).']);
    exit();
}
$stmt->bind_param('iss', $file_id, $token, $expires_at);
if ($stmt->execute()) {
    // Construct the full shareable URL
    // You might need to adjust this base URL depending on your server configuration
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
    $share_url = $base_url . '/Secure-file-sharing/download.php?token=' . $token; // Adjust /Secure-file-sharing/ if not in a subfolder

    echo json_encode(['success' => true, 'message' => 'Share link generated successfully.', 'share_url' => $share_url, 'token' => $token, 'expires_at' => $expires_at]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error generating share link.']);
}
$stmt->close();
$mysqli->close();
?>