-- import this file in http://localhost/phpmyadmin/
--Go to phpMyAdmin → Import.
--Upload init_db.sql.
--Import.

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
INSERT INTO
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


    