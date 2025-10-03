<?php
// register.php
require_once 'includes/db.php';
require_once 'includes/auth.php'; // Needed for CSRF token generation

// If already logged in, redirect to dashboard (logged-in users shouldn't register again)
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$registration_error = '';
$registration_success = '';
$name = '';
$email = '';

// Handle POST request for registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Validate CSRF token
    if (!validateCsrfToken($csrf_token)) {
        $registration_error = "Invalid CSRF token. Please try again.";
    } elseif (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $registration_error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $registration_error = "Invalid email format.";
    } elseif ($password !== $confirm_password) {
        $registration_error = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $registration_error = "Password must be at least 8 characters long.";
    } elseif (!preg_match('/[0-9]/', $password) || !preg_match('/[^a-zA-Z0-9]/', $password)) {
        $registration_error = "Password must contain at least one number and one special character.";
    } else {
        $mysqli = get_db_connection();
        
        // Check if email already exists using a prepared statement
        $stmt = $mysqli->prepare('SELECT Id FROM users WHERE Email = ?');
        if ($stmt) {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $registration_error = "This email is already registered.";
            }
            $stmt->close();
        } else {
            $registration_error = "Database error. Please try again.";
        }

        // If no errors so far, proceed with registration
        if (empty($registration_error)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $mysqli->prepare('INSERT INTO users (Name, Email, Password_hash) VALUES (?, ?, ?)');
            if ($stmt) {
                $stmt->bind_param('sss', $name, $email, $hashed_password);
                if ($stmt->execute()) {
                    $registration_success = "Registration successful! You can now <a href='index.php'>login</a>.";
                    // Clear form fields on success
                    $name = '';
                    $email = '';
                } else {
                    $registration_error = "Something went wrong. Please try again later. " . $stmt->error;
                }
                $stmt->close();
            } else {
                $registration_error = "Database error. Please try again.";
            }
        }
    }
}

// Generate CSRF token for the registration form
$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Secure File Sharing</title>
    <style>
        body { font-family: sans-serif; margin: 0; background-color: #f4f4f4; }
        .container { max-width: 800px; margin: 40px auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px;}
        input[type="text"], input[type="password"], input[type="email"] { padding: 10px; margin: 5px 0 15px 0; display: inline-block; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; width: 100%; max-width: 300px; }
        button { padding: 10px 15px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; transition: background-color 0.3s; }
        button:hover { background-color: #0056b3; }
        .msg { padding: 15px; margin-bottom: 20px; border-radius: 4px; font-weight: bold; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        .login-link { margin-top: 20px; text-align: center; }
        .login-link a { color: #007bff; text-decoration: none; }
        .login-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Register for Swizoshare</h2>
        </div>
        
        <?php if (!empty($registration_error)): ?>
            <div class="msg error"><?php echo htmlspecialchars($registration_error); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($registration_success)): ?>
            <div class="msg success"><?php echo $registration_success; ?></div>
        <?php endif; ?>
        
        <form action="register.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($name); ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($email); ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit">Register</button>
        </form>
        
        <div class="login-link">
            <p>Already have an account? <a href="index.php">Login here</a></p>
        </div>
    </div>
</body>
</html>