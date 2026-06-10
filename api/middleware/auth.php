<?php
/**
 * Authentication Middleware
 * JWT token verification and user loading
 */

require_once __DIR__ . '/../helpers/jwt.php';
require_once __DIR__ . '/../helpers/response.php';

class AuthMiddleware
{

    private static $currentUser = null;

    /**
     * Require authentication
     * Returns user data or sends 401 error
     */
    public static function require()
    {
        global $pdo;

        $user = self::getUser();

        if (!$user) {
            ApiResponse::error('AUTH_REQUIRED', 'Authentication required', null, 401);
        }

        // Check if user is banned
        if ($user['status'] === 'banned') {
            ApiResponse::error('AUTH_BANNED', 'Your account has been banned', null, 403);
        }

        return $user;
    }

    /**
     * Optional authentication
     * Returns user data or null
     */
    public static function optional()
    {
        return self::getUser();
    }

    /**
     * Get current authenticated user
     */
    public static function getUser()
    {
        global $pdo;

        if (self::$currentUser !== null) {
            return self::$currentUser;
        }

        $token = self::getBearerToken();

        if (!$token) {
            return null;
        }

        // Decode JWT
        $payload = JWT::decode($token);

        if (!$payload || !isset($payload['user_id'])) {
            return null;
        }

        // Load user from database
        try {
            $stmt = $pdo->prepare("
                SELECT id, enrollment_number, email, full_name, campus, department, 
                       semester, profile_image, whatsapp_number, status, created_at
                FROM users 
                WHERE id = ? AND status != 'banned'
            ");
            $stmt->execute([$payload['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                self::$currentUser = $user;
            }

            return $user;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get user ID from token
     */
    public static function getUserId()
    {
        $user = self::getUser();
        return $user ? $user['id'] : null;
    }

    /**
     * Extract Bearer token from Authorization header
     */
    private static function getBearerToken()
    {
        $headers = self::getAuthorizationHeader();

        if (!$headers) {
            return null;
        }

        if (preg_match('/Bearer\s+(.*)$/i', $headers, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Get Authorization header
     */
    private static function getAuthorizationHeader()
    {
        // Try standard header
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            return $_SERVER['HTTP_AUTHORIZATION'];
        }

        // Try Apache-specific header
        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            return $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        // Try getallheaders()
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (isset($headers['Authorization'])) {
                return $headers['Authorization'];
            }
            if (isset($headers['authorization'])) {
                return $headers['authorization'];
            }
        }

        return null;
    }
}
