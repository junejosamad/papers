<?php
/**
 * Safe Database Migration Script
 * Updates schema without losing existing data
 * 
 * Run: http://localhost/pp/api/safe_migrate.php
 */

require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

$results = [];
$errors = [];

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ===============================================
    // USERS TABLE - Add missing columns
    // ===============================================
    $usersColumns = [
        'whatsapp_number' => "ALTER TABLE users ADD COLUMN whatsapp_number VARCHAR(20) DEFAULT NULL AFTER semester",
        'profile_image' => "ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL AFTER whatsapp_number",
        'is_verified' => "ALTER TABLE users ADD COLUMN is_verified TINYINT(1) DEFAULT 0 AFTER profile_image",
        'status' => "ALTER TABLE users ADD COLUMN status ENUM('active', 'banned') DEFAULT 'active' AFTER is_verified",
        'last_login' => "ALTER TABLE users ADD COLUMN last_login DATETIME DEFAULT NULL AFTER status",
        'verification_token' => "ALTER TABLE users ADD COLUMN verification_token VARCHAR(64) DEFAULT NULL",
        'token_expiry' => "ALTER TABLE users ADD COLUMN token_expiry DATETIME DEFAULT NULL",
        'password_reset_token' => "ALTER TABLE users ADD COLUMN password_reset_token VARCHAR(64) DEFAULT NULL",
        'password_reset_expiry' => "ALTER TABLE users ADD COLUMN password_reset_expiry DATETIME DEFAULT NULL",
        'firebase_uid' => "ALTER TABLE users ADD COLUMN firebase_uid VARCHAR(128) DEFAULT NULL, ADD INDEX idx_firebase (firebase_uid)"
    ];

    foreach ($usersColumns as $column => $sql) {
        if (!columnExists($pdo, 'users', $column)) {
            $pdo->exec($sql);
            $results[] = "Added '$column' to users table";
        }
    }

    // ===============================================
    // PAPERS TABLE - Add missing columns
    // ===============================================
    $papersColumns = [
        'file_size' => "ALTER TABLE papers ADD COLUMN file_size INT DEFAULT 0 AFTER file_type",
        'exam_type' => "ALTER TABLE papers ADD COLUMN exam_type VARCHAR(20) DEFAULT 'Final' AFTER year",
        'views' => "ALTER TABLE papers ADD COLUMN views INT DEFAULT 0 AFTER downloads"
    ];

    foreach ($papersColumns as $column => $sql) {
        if (!columnExists($pdo, 'papers', $column)) {
            $pdo->exec($sql);
            $results[] = "Added '$column' to papers table";
        }
    }

    // ===============================================
    // FAVORITES TABLE - Create if not exists
    // ===============================================
    if (!tableExists($pdo, 'favorites')) {
        $pdo->exec("
            CREATE TABLE favorites (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                paper_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_favorite (user_id, paper_id),
                INDEX idx_user (user_id),
                INDEX idx_paper (paper_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $results[] = "Created 'favorites' table";
    }

    // ===============================================
    // PAPER_REQUESTS TABLE - Create if not exists
    // ===============================================
    if (!tableExists($pdo, 'paper_requests')) {
        $pdo->exec("
            CREATE TABLE paper_requests (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                campus VARCHAR(50) NOT NULL,
                course_code VARCHAR(50) NOT NULL,
                message TEXT,
                status ENUM('Open', 'Fulfilled', 'Closed') DEFAULT 'Open',
                vote_count INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user (user_id),
                INDEX idx_status (status),
                INDEX idx_campus (campus)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $results[] = "Created 'paper_requests' table";
    } else {
        // Add vote_count if missing
        if (!columnExists($pdo, 'paper_requests', 'vote_count')) {
            $pdo->exec("ALTER TABLE paper_requests ADD COLUMN vote_count INT DEFAULT 0");
            $results[] = "Added 'vote_count' to paper_requests table";
        }
    }

    // ===============================================
    // REQUEST_VOTES TABLE - Create if not exists
    // ===============================================
    if (!tableExists($pdo, 'request_votes')) {
        $pdo->exec("
            CREATE TABLE request_votes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                request_id INT NOT NULL,
                user_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_vote (request_id, user_id),
                INDEX idx_request (request_id),
                INDEX idx_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $results[] = "Created 'request_votes' table";
    }

    // ===============================================
    // REQUEST_COMMENTS TABLE - Create if not exists
    // ===============================================
    if (!tableExists($pdo, 'request_comments')) {
        $pdo->exec("
            CREATE TABLE request_comments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                request_id INT NOT NULL,
                user_id INT NOT NULL,
                message TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_request (request_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $results[] = "Created 'request_comments' table";
    }

    // ===============================================
    // NOTIFICATIONS TABLE - Create if not exists
    // ===============================================
    if (!tableExists($pdo, 'notifications')) {
        $pdo->exec("
            CREATE TABLE notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                type VARCHAR(50) NOT NULL,
                message TEXT NOT NULL,
                link VARCHAR(255) DEFAULT NULL,
                is_read TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_read (user_id, is_read)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $results[] = "Created 'notifications' table";
    }

    // ===============================================
    // API_TOKENS TABLE - Create if not exists
    // ===============================================
    if (!tableExists($pdo, 'api_tokens')) {
        $pdo->exec("
            CREATE TABLE api_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token_hash VARCHAR(64) NOT NULL,
                device_info VARCHAR(255) DEFAULT NULL,
                expires_at DATETIME NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_token (token_hash),
                INDEX idx_user (user_id),
                INDEX idx_expires (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $results[] = "Created 'api_tokens' table";
    }

    // ===============================================
    // ACTIVITY_LOGS TABLE - Create if not exists
    // ===============================================
    if (!tableExists($pdo, 'activity_logs')) {
        $pdo->exec("
            CREATE TABLE activity_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                action_type VARCHAR(50) NOT NULL,
                paper_id INT DEFAULT NULL,
                ip_address VARCHAR(45) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user (user_id),
                INDEX idx_action (action_type),
                INDEX idx_date (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $results[] = "Created 'activity_logs' table";
    }

    // ===============================================
    // ADD INDEXES IF MISSING
    // ===============================================
    // Add indexes for better query performance
    try {
        $pdo->exec("ALTER TABLE papers ADD INDEX idx_upload_date (upload_date)");
        $results[] = "Added index on papers.upload_date";
    } catch (Exception $e) {
        // Index might already exist
    }

    try {
        $pdo->exec("ALTER TABLE papers ADD INDEX idx_views (views)");
        $results[] = "Added index on papers.views";
    } catch (Exception $e) {
        // Index might already exist
    }

    // ===============================================
    // OUTPUT RESULTS
    // ===============================================
    if (empty($results)) {
        $results[] = "Database schema is already up to date!";
    }

    echo json_encode([
        'success' => true,
        'message' => 'Migration completed successfully!',
        'changes' => $results,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'changes_before_error' => $results
    ], JSON_PRETTY_PRINT);
}

// ===============================================
// HELPER FUNCTIONS
// ===============================================

function tableExists($pdo, $table)
{
    $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
    return $stmt->rowCount() > 0;
}

function columnExists($pdo, $table, $column)
{
    $stmt = $pdo->query("SHOW COLUMNS FROM $table LIKE '$column'");
    return $stmt->rowCount() > 0;
}
