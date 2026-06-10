-- ============================================
-- DATABASE SCHEMA (MySQL/phpMyAdmin)
-- ============================================
-- Run this in phpMyAdmin SQL tab

CREATE DATABASE IF NOT EXISTS papers CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE papers;

-- Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_number VARCHAR(20) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    verification_token VARCHAR(64) DEFAULT NULL,
    token_expiry DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_enrollment (enrollment_number),
    INDEX idx_verification (verification_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Papers Table
CREATE TABLE papers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type ENUM('pdf', 'image') NOT NULL,
    file_size INT NOT NULL,
    campus VARCHAR(50) NOT NULL,
    department VARCHAR(10) NOT NULL,
    semester TINYINT NOT NULL,
    course_code VARCHAR(20) NOT NULL,
    course_title VARCHAR(200) NOT NULL,
    teacher_name VARCHAR(100) NOT NULL,
    year YEAR NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    downloads INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_campus (campus),
    INDEX idx_department (department),
    INDEX idx_semester (semester),
    INDEX idx_course (course_code),
    INDEX idx_teacher (teacher_name),
    INDEX idx_year (year),
    FULLTEXT INDEX idx_search (title, course_code, course_title, teacher_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity Logs Table
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action_type ENUM('login', 'logout', 'upload', 'download', 'signup') NOT NULL,
    paper_id INT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_action (user_id, action_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
