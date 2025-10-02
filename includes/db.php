<?php
// includes/db.php

// !!! IMPORTANT !!!
// In a real application, database credentials should NOT be hardcoded here.
// Use environment variables or a configuration file located OUTSIDE the webroot.
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); // Your MySQL username
define('DB_PASSWORD', '');     // Your MySQL password (often empty for XAMPP root)
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