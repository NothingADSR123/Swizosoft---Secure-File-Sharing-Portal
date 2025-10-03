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
    <link rel="stylesheet" href="assets/css/style.css"> <!-- Assuming Intern 1 creates this -->
</head>
<body>
    <div class="container">
        <h2>Login to Swizoshare</h2>
        <?php if (!empty($login_error)): ?>
            <p style="color: red;"><?php echo htmlspecialchars($login_error); ?></p>
        <?php endif; ?>
        <form action="index.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <div>
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($email ?? ''); ?>">
            </div>
            <div>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Login</button>
        </form>
        <p>Don't have an account? <a href="register.php">Register here</a></p>
    </div>
</body>
</html>