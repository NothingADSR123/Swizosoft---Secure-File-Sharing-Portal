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
    <link rel="stylesheet" href="assets/css/style.css"> <!-- Assuming Intern 1 creates this -->
</head>
<body>
    <div class="container">
        <h2>Register for Swizoshare</h2>
        <?php if (!empty($registration_error)): ?>
            <p style="color: red;"><?php echo htmlspecialchars($registration_error); ?></p>
        <?php endif; ?>
        <?php if (!empty($registration_success)): ?>
            <p style="color: green;"><?php echo $registration_success; ?></p>
        <?php endif; ?>
        <form action="register.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <div>
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($name); ?>">
            </div>
            <div>
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($email); ?>">
            </div>
            <div>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div>
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit">Register</button>
        </form>
        <p>Already have an account? <a href="index.php">Login here</a></p>
    </div>
</body>
</html>