<?php
/**
 * Database Migration: Create API Tables
 * Run this once to create the required tables for the API
 */

require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

try {
    $results = [];

    // Create API tokens table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS api_tokens (
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
    $results[] = "api_tokens table created";

    // Check if paper_requests table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'paper_requests'");
    if ($stmt->rowCount() > 0) {
        // Add vote_count column if it doesn't exist
        $stmt = $pdo->query("SHOW COLUMNS FROM paper_requests LIKE 'vote_count'");
        if ($stmt->rowCount() === 0) {
            $pdo->exec("ALTER TABLE paper_requests ADD COLUMN vote_count INT DEFAULT 0");
            $results[] = "vote_count column added to paper_requests";
        }

        // Create request_votes table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS request_votes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                request_id INT NOT NULL,
                user_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_vote (request_id, user_id),
                INDEX idx_request (request_id),
                INDEX idx_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $results[] = "request_votes table created";

        // Create request_comments table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS request_comments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                request_id INT NOT NULL,
                user_id INT NOT NULL,
                message TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_request (request_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $results[] = "request_comments table created";
    } else {
        $results[] = "paper_requests table not found - skipping related tables";
    }

    // Create notifications table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notifications (
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
    $results[] = "notifications table created";

    echo json_encode([
        'success' => true,
        'message' => 'API database tables created successfully!',
        'results' => $results
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
