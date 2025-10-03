
<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Clear any other cookies set by the application
// Add specific cookies that need to be cleared
setcookie('remember_me', '', time() - 3600, '/');

// Redirect to login page
header("Location: index.php");
exit();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out - Secure File Sharing</title>
    <style>
        body { font-family: sans-serif; margin: 0; background-color: #f4f4f4; }
        .container { max-width: 800px; margin: 40px auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px;}
        .msg { padding: 15px; margin-bottom: 20px; border-radius: 4px; font-weight: bold; }
        .info { background-color: #cce5ff; color: #004085; border: 1px solid #b8daff; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Logging Out</h2>
        </div>
        <div class="msg info">
            You are being logged out. If you are not redirected, <a href="index.php">click here</a>.
        </div>
    </div>
</body>
</html>