<?php
// index.php
require_once 'includes/db.php';
require_once 'includes/auth.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$login_error = '';

// Handle POST request for login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Validate CSRF token
    if (!validateCsrfToken($csrf_token)) {
        $login_error = "Invalid CSRF token. Please try again.";
    } elseif (empty($email) || empty($password)) {
        $login_error = "Please enter both email and password.";
    } else {
        $mysqli = get_db_connection();
        $stmt = $mysqli->prepare('SELECT Id, Name, Password_hash, Failed_logins, Is_locked, Last_failed FROM users WHERE Email = ?');
        
        if ($stmt) {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();

                // Implement login lockout check first
                if ($user['Is_locked'] == 1 && (strtotime($user['Last_failed']) + 900) > time()) { // 900 seconds = 15 minutes
                    $login_error = "Account is locked. Please try again after 15 minutes or contact support.";
                } elseif (password_verify($password, $user['Password_hash'])) {
                    // Password is correct, reset failed login attempts
                    $update_stmt = $mysqli->prepare('UPDATE users SET Failed_logins = 0, Last_failed = NULL, Is_locked = 0 WHERE Id = ?');
                    if ($update_stmt) {
                        $update_stmt->bind_param('i', $user['Id']);
                        $update_stmt->execute();
                        $update_stmt->close();
                    }

                    // Successful login
                    session_regenerate_id(true); // Prevent session fixation
                    $_SESSION['user_id'] = $user['Id'];
                    $_SESSION['user_name'] = $user['Name'];
                    $_SESSION['login_time'] = time(); // For inactivity timeout later
                    generateCsrfToken(); // Generate new CSRF token for subsequent forms

                    // Redirect to dashboard or originally requested page
                    $redirect_to = $_GET['redirect'] ?? 'dashboard.php';
                    header("Location: " . $redirect_to);
                    exit();

                } else {
                    // Password incorrect, increment failed login attempts
                    $login_error = "Invalid email or password.";
                    
                    $failed_logins = $user['Failed_logins'] + 1;
                    $is_locked = ($failed_logins >= 3) ? 1 : 0; // Lock after 3 attempts
                    $last_failed_time = date('Y-m-d H:i:s');

                    $update_stmt = $mysqli->prepare('UPDATE users SET Failed_logins = ?, Last_failed = ?, Is_locked = ? WHERE Id = ?');
                    if ($update_stmt) {
                        $update_stmt->bind_param('isii', $failed_logins, $last_failed_time, $is_locked, $user['Id']);
                        $update_stmt->execute();
                        $update_stmt->close();
                    }
                }
            } else {
                // User not found, but to avoid enumeration attacks, use generic message
                $login_error = "Invalid email or password.";
                 // Optionally, if email doesn't exist, we could still increment failed logins for a dummy user or just let it pass
                 // For now, we'll just show the generic message.
            }
            $stmt->close();
        } else {
            $login_error = "Database error. Please try again.";
        }
    }
}

// Generate CSRF token for the login form
$csrf_token = generateCsrfToken();

// The rest of this file would typically be the HTML for the login form.
// Intern 1 will provide the HTML, you're the one processing the POST.
// For now, a very basic placeholder:
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Secure File Sharing</title>
    <style>
        body { font-family: sans-serif; margin: 0; background-color: #f4f4f4; }
        .container { max-width: 800px; margin: 40px auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px;}
        input[type="text"], input[type="password"], input[type="email"] { padding: 10px; margin: 5px 0 15px 0; display: inline-block; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; width: 100%; max-width: 300px; }
        button { padding: 10px 15px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; transition: background-color 0.3s; }
        button:hover { background-color: #0056b3; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 15px; margin-bottom: 20px; border-radius: 4px; font-weight: bold; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        .register-link { margin-top: 20px; text-align: center; }
        .register-link a { color: #007bff; text-decoration: none; }
        .register-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Login to Swizoshare</h2>
        </div>
        <?php if (!empty($login_error)): ?>
            <div class="error"><?php echo htmlspecialchars($login_error); ?></div>
        <?php endif; ?>
        <form action="index.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($email ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Login</button>
        </form>
        <div class="register-link">
            <p>Don't have an account? <a href="register.php">Register here</a></p>
        </div>
    </div>
</body>
</html>