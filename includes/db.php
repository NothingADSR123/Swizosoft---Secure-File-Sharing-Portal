<?php
// includes/db.php
// before session_start()
session_set_cookie_params([
  'lifetime' => 0,
  'path'     => '/',
  'domain'   => '',        // set to your domain in prod
  'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
  'httponly' => true,
  'samesite' => 'Lax'      // or 'Strict' if your flows allow it
]);
session_start();
// !!! IMPORTANT !!!
// In a real application, database credentials should NOT be hardcoded here.
// Use environment variables or a configuration file located OUTSIDE the webroot.
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); // Your MySQL username
define('DB_PASSWORD', '');     // Your MySQL password (often empty for XAMPP root)
// define('DB_USERNAME', 'swizo_user');      // your new least-privilege user
// define('DB_PASSWORD', 'StrongPassword123!'); // or whatever you set in MySQL
define('DB_NAME', 'swizoshare');

// Attempt to establish a MySQLi connection
$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if($mysqli === false){
    die("ERROR: Could not connect to database. " . $mysqli->connect_error);
}

// Set character set to utf8mb4 for proper emoji and special character handling
$mysqli->set_charset("utf8mb4");

// Function to get the database connection
function get_db_connection() {
    global $mysqli;
    return $mysqli;
}

// Note: The connection will automatically close when the script finishes.
// For long-running applications or specific needs, explicit closing might be required.
// For a typical PHP script, this is usually fine.
?>