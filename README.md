---

# Swizoshare: Secure File Sharing Platform

**Swizoshare** is a robust and secure web-based platform designed for private and controlled file sharing. It provides users with a personal dashboard to upload, manage, and securely share their files with others via unique, time-limited download links.

## Table of Contents

*   [Features](#features)
*   [Technology Stack](#technology-stack)
*   [Getting Started](#getting-started)
    *   [Prerequisites](#prerequisites)
    *   [Installation](#installation)
    *   [Database Setup](#database-setup)
*   [Usage](#usage)
*   [Security Measures](#security-measures)
*   [Project Structure](#project-structure)
*   [Contributing](#contributing)
*   [License](#license)
*   [Contact](#contact)

## Features

*   **User Authentication:** Secure registration and login system with password hashing.
*   **Personalized Dashboard:** A central hub for users to view and manage all their uploaded files.
*   **File Uploads:** Easily upload files with client-side and server-side validation (e.g., file size limits).
*   **File Management:** View file details (name, size, upload date) and remove unwanted files from the dashboard.
*   **Secure Sharing Links:** Generate unique, unguessable, and optionally time-limited links for sharing files with anyone, without requiring them to log in.
*   **Controlled Downloads:** `download.php` handles file serving, ensuring tokens are valid and not expired before granting access.
*   **CSRF Protection:** Implemented across all forms and AJAX requests to prevent Cross-Site Request Forgery attacks.
*   **Secure File Storage:** Files are stored in a protected `uploads/` directory, preventing direct execution and unauthorized access.
*   **Database Integration:** Uses a robust relational database to store user and file metadata, as well as share link details.

## Technology Stack

*   **Backend:** PHP (>= 7.4 recommended)
*   **Database:** MySQL / MariaDB
*   **Frontend:** HTML, CSS, JavaScript (Vanilla JS for AJAX)
*   **Web Server:** Apache / Nginx

## Getting Started

Follow these instructions to get Swizoshare up and running on your local machine for development and testing purposes.

### Prerequisites

Before you begin, ensure you have the following installed:

*   **Web Server:** Apache or Nginx (with PHP-FPM for Nginx)
*   **PHP:** Version 7.4 or higher
    *   Required extensions: `mysqli` (or `pdo_mysql`), `gd` (optional, for image processing if you extend functionality), `mbstring`, `json`.
*   **Database:** MySQL or MariaDB
*   **Composer** (optional, for managing PHP dependencies if you introduce any)

### Installation

1.  **Clone the Repository:**
    ```bash
    git clone https://github.com/yourusername/Secure-file-sharing.git # Replace with your actual repo URL
    cd Secure-file-sharing
    ```

2.  **Configure Web Server:**
    *   Point your web server's document root to the `Secure-file-sharing/` directory.
    *   Ensure PHP is correctly configured and working with your web server.

3.  **Set `uploads/` Permissions:**
    The `uploads/` directory requires write permissions for your web server user (e.g., `www-data` on Ubuntu, `apache` on CentOS).
    ```bash
    chmod -R 775 uploads/
    chown -R www-data:www-data uploads/ # Adjust user/group as per your system
    ```
    *(Note: `775` is a common development permission; for production, you might restrict it further if possible, e.g., `755` for directories and `644` for files, and ensure the web server only has write access to the `uploads` directory itself, not its parent.)*

### Database Setup

1.  **Create Database:**
    Access your MySQL/MariaDB server (e.g., via phpMyAdmin, Adminer, or command line) and create a new database for Swizoshare.
    ```sql
    CREATE DATABASE IF NOT EXISTS swizoshare DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    ```

2.  **Import Schema:**
    Import the `init_db.sql` file into your newly created database. This will set up all necessary tables (users, files, share_links) and insert some test data.
    ```bash
    mysql -u your_db_user -p swizoshare < init_db.sql
    ```
    (You will be prompted for your database user's password.)

3.  **Configure `includes/db.php`:**
    Open `includes/db.php` and update the database connection details (hostname, username, password, database name) to match your environment.

    ```php
    // includes/db.php (example snippet)
    function get_db_connection() {
        static $mysqli;
        if (!isset($mysqli)) {
            $mysqli = new mysqli("localhost", "your_db_user", "your_db_password", "swizoshare"); // <--- Update these
            if ($mysqli->connect_error) {
                die("Connection failed: " . $mysqli->connect_error);
            }
        }
        return $mysqli;
    }
    ```

## Usage

1.  **Access the Platform:**
    Open your web browser and navigate to the base URL where you've installed Swizoshare (e.g., `http://localhost/Secure-file-sharing/`).

2.  **Register / Log In:**
    *   If you're a new user, register an account.
    *   Log in with your credentials.

3.  **Dashboard:**
    *   **Upload Files:** Use the "Upload File" section to add new files to your dashboard.
    *   **View Files:** Your uploaded files will appear in the "Your Files" table.
    *   **Download:** Click the "Download" button to get your file.
    *   **Share:** Click the "Share" button to generate a unique, time-limited link. This link can be copied and shared with others.
    *   **Remove:** Click the "Remove" button to permanently delete a file from your account and the server.

4.  **Shared Files:**
    Anyone with a valid share link (e.g., `http://localhost/Secure-file-sharing/download.php?token=your_unique_token`) can download the associated file without needing to log in, provided the link has not expired.

## Security Measures

*   **Prepared Statements:** All database interactions use prepared statements to prevent SQL Injection.
*   **Password Hashing:** User passwords are encrypted using `password_hash()` (Bcrypt) for secure storage.
*   **CSRF Protection:** CSRF tokens are used to protect against Cross-Site Request Forgery attacks for all state-changing actions.
*   **Session Management:** Secure PHP session handling with `session_start()` and `session_regenerate_id()`.
*   **File Type & Size Validation:** Both client-side and server-side checks are performed during file uploads.
*   **Protected `uploads/` Directory:** An `.htaccess` file prevents direct execution of scripts within the `uploads/` directory, mitigating risks from malicious uploads.
*   **Ownership Verification:** Operations like file removal and share link generation strictly verify that the logged-in user owns the file.
*   **Share Link Expiration:** Sharing tokens can have an expiration date, automatically invalidating links after a set period.

## Project Structure

```
Secure-file-sharing/
│
├── README.md                 # This file
├── index.php                 # Login page, redirects to dashboard if logged in
├── register.php              # User registration page
├── logout.php                # Handles user logout
├── dashboard.php             # User's file management dashboard
├── upload.php                # Handles file upload POST requests
├── download.php              # Serves files securely via share links
│
├── api/
│   ├── list_files.php        # JSON API to fetch user's file list for dashboard
│   ├── remove_file.php       # JSON API to remove a user's file
│   └── generate_share_link.php # JSON API to generate unique shareable links
│
├── includes/
│   ├── db.php                # Database connection utility
│   └── auth.php              # Authentication functions (login, logout, CSRF)
│
├── uploads/                  # Directory where uploaded files are stored
│   └── .htaccess             # Security configuration for uploads directory
│
├── assets/
│   ├── css/
│   │   └── style.css         # Main stylesheet (mostly embedded in dashboard.php for this version)
│   └── js/                   # Placeholder for future JavaScript files
│
└── init_db.sql               # SQL script for database schema and initial data
```

## Contributing

Contributions are welcome! If you'd like to improve Swizoshare, please follow these steps:

1.  Fork the repository.
2.  Create a new branch (`git checkout -b feature/your-feature-name`).
3.  Make your changes.
4.  Commit your changes (`git commit -m 'Add new feature'`).
5.  Push to the branch (`git push origin feature/your-feature-name`).
6.  Create a new Pull Request.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
*(Note: You will need to create a `LICENSE` file in your root directory if you want to include one.)*

## Contact

For any questions or suggestions, please open an issue in the GitHub repository
