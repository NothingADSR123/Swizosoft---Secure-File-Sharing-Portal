<?php
// dashboard.php
require_once 'includes/db.php'; // Your database connection
require_once 'includes/auth.php'; // Your authentication functions

// CRITICAL SECURITY CHECK: Redirect if the user is NOT logged in.
// Use your isLoggedIn() function for this!
requireLogin(); // This function from includes/auth.php handles the redirect

// Get user info from session
$user_id = getUserId();
$user_name = $_SESSION['user_name'] ?? 'User'; // You stored this during login

// For CSRF protection on forms (upload, remove, generate share link)
$csrf_token = generateCsrfToken(); // This will be used in all AJAX POST requests
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Swizoshare</title>
    <!-- Add CSRF token meta tag for easier JavaScript access -->
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrf_token); ?>">
    <style>
        body { font-family: sans-serif; margin: 0; background-color: #f4f4f4; }
        .container { max-width: 800px; margin: 40px auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px; }
        input[type="text"], input[type="password"], input[type="file"] { padding: 10px; margin: 5px 0 15px 0; display: inline-block; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; width: 100%; max-width: 300px; }
        #file-list { width: 100%; border-collapse: collapse; margin-top: 20px; }
        #file-list th, #file-list td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        #file-list th { background-color: #f2f2f2; }
        button { padding: 10px 15px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; transition: background-color 0.3s; }
        button:hover { background-color: #0056b3; }
        .small-btn { padding: 5px 10px; font-size: 0.9em; background-color: #28a745; }
        .small-btn:hover { background-color: #1e7e34; }
        .msg { padding: 15px; margin-bottom: 20px; border-radius: 4px; font-weight: bold; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .progress-bar { height: 25px; background-color: #e9ecef; border-radius: 5px; margin-top: 15px; overflow: hidden; }
        .progress-bar-fill { height: 100%; background-color: #007bff; width: 0%; text-align: center; color: white; line-height: 25px; transition: width 0.1s; }
        .modal { position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); display: flex; justify-content: center; align-items: center; }
        .modal-content { background-color: white; padding: 30px; border-radius: 10px; max-width: 400px; text-align: center; }
        hr { margin: 20px 0; border: 0; border-top: 1px solid #eee; }
        h3 { color: #333; margin-top: 30px; }
        .download-link { text-decoration: none; color: white; margin-right: 10px; }
        .remove-btn { background-color: #dc3545 !important; }
        .remove-btn:hover { background-color: #c82333 !important; }
        #logout-btn { background-color: #dc3545; }
        #logout-btn:hover { background-color: #c82333; }
        /* NEW: Style for share button */
        .share-btn { background-color: #17a2b8 !important; margin-left: 5px; }
        .share-btn:hover { background-color: #138496 !important; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>File Dashboard</h2>
            <p>Welcome, <?php echo htmlspecialchars($user_name); ?>!</p>
            <button id="logout-btn">Logout</button>
        </div>

        <h3>Upload File</h3>
        <form id="upload-form" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <label for="fileToUpload">Select File (Max 5MB):</label>
            <input type="file" name="fileToUpload" id="fileToUpload" required>
            <button type="submit">Upload File</button>
        </form>
        <div id="upload-message" class="msg" style="display:none;"></div>
        <div class="progress-bar" id="progressBar" style="display:none;">
            <div class="progress-bar-fill" id="progressBarFill">0%</div>
        </div>

        <hr>

        <h3>Your Files</h3>
        <div id="file-list-container">
            <table id="file-list">
                <thead>
                    <tr>
                        <th>File Name</th>
                        <th>Size</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- File list will be populated by JavaScript -->
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const fileListBody = document.querySelector('#file-list tbody');
            const uploadForm = document.getElementById('upload-form');
            const uploadMessage = document.getElementById('upload-message');
            const progressBar = document.getElementById('progressBar');
            const progressBarFill = document.getElementById('progressBarFill');
            const logoutBtn = document.getElementById('logout-btn');

            // Pass CSRF token to JS for AJAX requests
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content'); // Get from meta tag

            // --- Utility Functions ---
            function showMessage(msg, type) {
                uploadMessage.textContent = msg;
                uploadMessage.className = 'msg ' + type; // Add space
                uploadMessage.style.display = 'block';
                setTimeout(() => uploadMessage.style.display = 'none', 5000);
            }

            // --- 1. Fetch File List (AJAX) ---
            function fetchFileList() {
                fileListBody.innerHTML = '<tr><td colspan="4">Loading files...</td></tr>';
                fetch('api/list_files.php')
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(files => {
                        fileListBody.innerHTML = '';
                        if (files.length === 0) {
                            fileListBody.innerHTML = '<tr><td colspan="4">No files uploaded yet.</td></tr>';
                            return;
                        }
                        files.forEach(file => {
                            const row = fileListBody.insertRow();
                            row.insertCell().textContent = file.original_name; // Use original_name from DB
                            row.insertCell().textContent = (file.size_bytes / 1024).toFixed(2) + ' KB'; // Format size
                            row.insertCell().textContent = new Date(file.upload_time).toLocaleString(); // Format date
                            const actionCell = row.insertCell();

                            const downloadLink = `download.php?id=${file.id}`; // Assuming 'id' from your DB
                            const removeButton = `<button data-file-id="${file.id}" class="small-btn remove-btn">Remove</button>`;
                            // NEW: Share Button
                            const shareButton = `<button data-file-id="${file.id}" class="small-btn share-btn">Share</button>`;

                            actionCell.innerHTML = `
                                <a href="${downloadLink}" class="small-btn download-link">Download</a>
                                ${shareButton}
                                ${removeButton}
                            `;
                        });
                    })
                    .catch(error => {
                        console.error('Error fetching file list:', error);
                        showMessage('Failed to load files.', 'error');
                        fileListBody.innerHTML = '<tr><td colspan="4" class="msg error">Failed to load files.</td></tr>';
                    });
            }

            // --- 2. AJAX File Upload ---
            uploadForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const fileInput = document.getElementById('fileToUpload');
                const file = fileInput.files[0];

                if (fileInput.files.length === 0) {
                    showMessage('Please select a file to upload.', 'error');
                    return;
                }

                if (file.size > 5 * 1024 * 1024) { // 5MB in bytes
                    showMessage('File size must not exceed 5MB.', 'error');
                    return;
                }

                const formData = new FormData(this); // Automatically includes fileToUpload and csrf_token
                uploadMessage.style.display = 'none';
                progressBar.style.display = 'block';
                progressBarFill.style.width = '0%';
                progressBarFill.textContent = '0%';

                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'upload.php', true); // Point to your dedicated upload script

                xhr.upload.onprogress = function(event) {
                    if (event.lengthComputable) {
                        const percent = Math.round((event.loaded / event.total) * 100);
                        progressBarFill.style.width = percent + '%';
                        progressBarFill.textContent = percent + '%';
                    }
                };

                xhr.onload = function() {
                    progressBar.style.display = 'none';
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (xhr.status === 200 && response.success) {
                            showMessage(response.message, 'success');
                            fileInput.value = ''; // Clear file input
                            fetchFileList(); // Refresh the list
                        } else {
                            showMessage(response.message || 'Upload failed due to a server error.', 'error');
                        }
                    } catch (e) {
                        showMessage('Error processing server response.', 'error');
                        console.error('JSON parse error:', e);
                    }
                };

                xhr.onerror = function() {
                    progressBar.style.display = 'none';
                    showMessage('Network error or server unreachable.', 'error');
                };

                xhr.send(formData);
            });

            // --- 3. File Removal (AJAX) ---
            function removeFile(fileId) {
                if (!confirm(`Are you sure you want to permanently remove this file?`)) {
                    return;
                }
                const formData = new FormData();
                formData.append('file_id', fileId); // Send file ID, not name, for security
                formData.append('csrf_token', csrfToken); // Add CSRF token to DELETE request

                fetch('api/remove_file.php', {
                    method: 'POST', // Or DELETE if you configure your server to handle it
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        showMessage(data.message, 'success');
                        fetchFileList();
                    } else {
                        showMessage(data.message || 'File removal failed.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Removal error:', error);
                    showMessage('Network error during file removal.', 'error');
                });
            }

            // --- NEW: 4. Generate Share Link (AJAX) ---
            async function generateShareLink(fileId) {
                // You could add UI for custom expiration here if desired
                // For now, it uses the default expiration in generate_share_link.php

                const formData = new FormData();
                formData.append('file_id', fileId);
                formData.append('csrf_token', csrfToken);

                try {
                    const response = await fetch('api/generate_share_link.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
    // Fix the returned URL to include Swizosoft(WarmUp)
    // const correctedUrl = data.share_url.replace(
    //     "http://localhost/",
    //     "http://localhost/Swizosoft(WarmUp)/"
    // );
const correctedUrl = `${window.location.origin}/Swizosoft---Secure-File-Sharing-Portal/download.php?token=${data.token}`;


    prompt("Copy this share link:", correctedUrl);
    showMessage(data.message, 'success');
} else {
    showMessage(data.message || 'Failed to generate share link.', 'error');
}
                } catch (error) {
                    console.error('Error generating share link:', error);
                    showMessage('Network error during share link generation.', 'error');
                }
            }


            // --- 5. Logout (AJAX) ---
            logoutBtn.addEventListener('click', () => {
                fetch('logout.php') // logout.php will handlesession destruction and redirect.
                .then(response => {
                    window.location.href = 'index.php'; // Or'login.php'
                }).catch(error => {
                    console.error('Logout error:', error);
                    alert('Could not connect to the server for logout.');
                });
            });

            // --- Event Delegation for Action Buttons (Remove and Share) ---
            fileListBody.addEventListener('click', (e) => {
                if (e.target.classList.contains('remove-btn')) {
                    const fileId = e.target.getAttribute('data-file-id');
                    if (fileId) {
                        removeFile(fileId);
                    }
                }
                // NEW: Handle Share button clicks
                if (e.target.classList.contains('share-btn')) {
                    const fileId = e.target.getAttribute('data-file-id');
                    if (fileId) {
                        generateShareLink(fileId);
                    }
                }
            });

            // Initial load of files when the dashboard loads
            fetchFileList();
        });
    </script>
</body>
</html>