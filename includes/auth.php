<?php
// includes/auth.php

// Ensure session is started only once
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Function to check if a user is currently logged in
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

// Function to get the current logged-in user's ID
function getUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

// Function to generate a CSRF token
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Function to validate a CSRF token
function validateCsrfToken(string $token): bool {
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Function to redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: index.php?redirect=" . urlencode($_SERVER['REQUEST_URI'])); // Redirect to login page
        exit();
    }
}

// You'll expand this with more security functions later, like:
// - logout()
// - checkInactivityTimeout()
// - handleLoginLockout()
?>