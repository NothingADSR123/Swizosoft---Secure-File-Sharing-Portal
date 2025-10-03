-- import this file in http://localhost/phpmyadmin/
-- Go to phpMyAdmin → Import.
-- Upload init_db.sql.
-- Import.

CREATE DATABASE IF NOT EXISTS swizoshare DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE swizoshare;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Email VARCHAR(150) NOT NULL UNIQUE,
    Password_hash VARCHAR(255) NOT NULL,
    Created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Failed_logins INT DEFAULT 0,
    Last_failed DATETIME DEFAULT NULL,
    Is_locked TINYINT(1) DEFAULT 0
) ENGINE = InnoDB;

-- Files table
CREATE TABLE IF NOT EXISTS files (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    Original_name VARCHAR(255) NOT NULL,
    Stored_name VARCHAR(255) NOT NULL,
    Uploader_id INT NOT NULL,
    Mime_type VARCHAR(100) NOT NULL,
    Size_bytes INT NOT NULL,
    Upload_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Download_count INT DEFAULT 0,
    Is_public TINYINT(1) DEFAULT 0,
    Expire_at DATETIME DEFAULT NULL,
    FOREIGN KEY (Uploader_id) REFERENCES users (Id) ON DELETE CASCADE
) ENGINE = InnoDB;

-- Share links table
CREATE TABLE IF NOT EXISTS share_links (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    File_id INT NOT NULL,
    Token VARCHAR(128) NOT NULL UNIQUE,
    Expires_at DATETIME DEFAULT NULL,
    Created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (File_id) REFERENCES files (Id) ON DELETE CASCADE
) ENGINE = InnoDB;

-- Test users insterted with hashed passwords
INSERT IGNORE INTO
    users (Name, Email, Password_hash)
VALUES (
        'Test User',
        'test1@swizo.com',
        '$2b$12$SSBVIFCvyUB9KaS4C7KRBeXifAfXk64LP6M7nfei393ciyCDJ8xvK'
    ),
    (
        'Alice QA',
        'alice@swizo.com',
        '$2b$12$cysRpNH6NzKj7TKLyCBjTOaXAZORkV/72vGM3mUlMNPUHR5mRGBd2'
    ),
    (
        'Bob Tester',
        'bob@swizo.com',
        '$2b$12$Fexepro3AWUieLXzFgN11.MIuVPMOTMvqmQPbQBDLlNU/QVDhre0u'
    );

-- Sample files for dashboard testing

-- For Test User (id = 1)
INSERT IGNORE INTO
    files (
        Original_name,
        Stored_name,
        Uploader_id,
        Mime_type,
        Size_bytes
    )
VALUES (
        'project-doc.pdf',
        'a1b2c3d4_project-doc.pdf',
        1,
        'application/pdf',
        24576
    ),
    (
        'design.png',
        'e5f6g7h8_design.png',
        1,
        'image/png',
        54321
    );

-- For Alice QA (id = 2)
INSERT IGNORE INTO
    files (
        Original_name,
        Stored_name,
        Uploader_id,
        Mime_type,
        Size_bytes
    )
VALUES (
        'requirements.docx',
        'i9j0k1l2_requirements.docx',
        2,
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        32768
    ),
    (
        'report.jpg',
        'm3n4o5p6_report.jpg',
        2,
        'image/jpeg',
        15872
    );

-- For Bob Tester (id = 3)
INSERT IGNORE INTO
    files (
        Original_name,
        Stored_name,
        Uploader_id,
        Mime_type,
        Size_bytes
    )
VALUES (
        'test-plan.pdf',
        'q7r8s9t0_test-plan.pdf',
        3,
        'application/pdf',
        20480
    ),
    (
        'screenshot.png',
        'u1v2w3x4_screenshot.png',
        3,
        'image/png',
        67890
    );

-- Sample share links for testing secure downloads

INSERT IGNORE INTO
    share_links (File_id, Token, Expires_at)
VALUES (
        1,
        'tok_abc123456789',
        DATE_ADD(NOW(), INTERVAL 7 DAY)
    ),
    (
        2,
        'tok_def987654321',
        DATE_ADD(NOW(), INTERVAL 3 DAY)
    ),
    (
        3,
        'tok_ghi456123789',
        DATE_ADD(NOW(), INTERVAL 5 DAY)
    ),
    (
        4,
        'tok_jkl321654987',
        DATE_ADD(NOW(), INTERVAL 1 DAY)
    ),
    (
        5,
        'tok_mno741852963',
        DATE_ADD(NOW(), INTERVAL 10 DAY)
    ),
    (
        6,
        'tok_pqr369258147',
        DATE_ADD(NOW(), INTERVAL 2 DAY)
    );