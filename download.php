<?php
// download.php
require_once 'includes/db.php';

$mysqli = get_db_connection();
$upload_dir = 'uploads/';

$token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);
$file_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$token && !$file_id) {
    die("Error: No sharing token or file ID provided.");
}

/* =============================
   CASE 1: Logged-in user file download (?id=)
============================= */
if ($file_id) {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        die("Error: You must be logged in to download your own files.");
    }

    $stmt = $mysqli->prepare('SELECT Stored_name, Original_name, Mime_type, Size_bytes FROM files WHERE Id = ? AND Uploader_id = ?');
    $stmt->bind_param('ii', $file_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        die("Error: File not found or you do not have access.");
    }

    $file_data = $result->fetch_assoc();
    $stmt->close();

    $stored_filename = $file_data['Stored_name'];
    $original_filename = $file_data['Original_name'];
    $mime_type = $file_data['Mime_type'];
    $file_size = $file_data['Size_bytes'];
    $filepath = $upload_dir . $stored_filename;

    if (!file_exists($filepath) || !is_file($filepath)) {
        die("Error: File not found on server.");
    }

    // Increment download count
    $update = $mysqli->prepare("UPDATE files SET Download_count = Download_count + 1 WHERE Id = ?");
    $update->bind_param("i", $file_id);
    $update->execute();
    $update->close();

    // Serve the file
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mime_type);
    header('Content-Disposition: attachment; filename="' . $original_filename . '"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . $file_size);

    if (ob_get_level()) {
        ob_end_clean();
    }

    readfile($filepath);
    exit();
}

/* =============================
   CASE 2: Public share link (?token=)
============================= */
$stmt = $mysqli->prepare(
    'SELECT s.File_id, s.Expires_at, f.Stored_name, f.Original_name, f.Mime_type, f.Size_bytes
     FROM share_links s
     JOIN files f ON s.File_id = f.Id
     WHERE s.Token = ?'
);
$stmt->bind_param('s', $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Error: Invalid or expired sharing token.");
}

$share_data = $result->fetch_assoc();
$stmt->close();

$file_id = $share_data['File_id'];
if ($share_data['Expires_at'] && strtotime($share_data['Expires_at']) < time()) {
    $stmt = $mysqli->prepare('DELETE FROM share_links WHERE Token = ?');
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $stmt->close();
    die("Error: Sharing link has expired.");
}

$stored_filename = $share_data['Stored_name'];
$original_filename = $share_data['Original_name'];
$mime_type = $share_data['Mime_type'];
$file_size = $share_data['Size_bytes'];
$filepath = $upload_dir . $stored_filename;

if (!file_exists($filepath) || !is_file($filepath)) {
    die("Error: File not found on server.");
}

// Increment download count
$update = $mysqli->prepare("UPDATE files SET Download_count = Download_count + 1 WHERE Id = ?");
$update->bind_param("i", $file_id);
$update->execute();
$update->close();

// Serve the file
header('Content-Description: File Transfer');
header('Content-Type: ' . $mime_type);
header('Content-Disposition: attachment; filename="' . $original_filename . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . $file_size);

if (ob_get_level()) {
    ob_end_clean();
}

readfile($filepath);
exit();
?>
