<?php
// download.php
require_once 'includes/db.php'; // Adjust path as needed

$mysqli = get_db_connection();
$upload_dir = 'uploads/'; // Relative to download.php, this is correct for your structure

$token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);

if (!$token) {
    die("Error: No sharing token provided.");
}

// 1. Validate the token and get file_id
$stmt = $mysqli->prepare(
    'SELECT s.File_id, s.Expires_at, f.Stored_name, f.Original_name, f.Mime_type, f.Size_bytes
     FROM share_links s
     JOIN files f ON s.File_id = f.Id
     WHERE s.Token = ?'
);

if (!$stmt) {
    die("Error: Database statement failed during token lookup.");
}
$stmt->bind_param('s', $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Error: Invalid or expired sharing token.");
}

$share_data = $result->fetch_assoc();
$stmt->close();
if ($share_data['Expires_at'] && strtotime($share_data['Expires_at']) < time()) {
    // Optionally delete expired token from DB
    $stmt = $mysqli->prepare('DELETE FROM share_links WHERE Token = ?');
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $stmt->close();

    die("Error: Sharing link has expired.");
}

// 3. Prepare file for download
$stored_filename = $share_data['Stored_name'];
$original_filename = $share_data['Original_name'];
$mime_type = $share_data['Mime_type'];
$file_size = $share_data['Size_bytes'];
$filepath = $upload_dir . $stored_filename;

if (!file_exists($filepath) || !is_file($filepath)) {
    die("Error: File not found on server.");
}

// 4. Update download count (optional)
// You might want to update the 'Download_count' in the 'files' table here.
// Example: $mysqli->prepare('UPDATE files SET Download_count = Download_count + 1 WHERE Id = ?')->bind_param('i', $share_data['File_id'])->execute();

// 5. Serve the file
header('Content-Description: File Transfer');
header('Content-Type: ' . $mime_type);
header('Content-Disposition: attachment; filename="' . $original_filename . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . $file_size);

// Clear output buffer
if (ob_get_level()) {
    ob_end_clean();
}

readfile($filepath);
exit();
?>