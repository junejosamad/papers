<?php
/**
 * API Configuration - PRODUCTION
 * Collexa Past Papers API
 * 
 * ⚠️ IMPORTANT: Keep this file secure and never commit to public repos
 */

// Prevent direct access
if (!defined('API_ACCESS')) {
    define('API_ACCESS', true);
}

// Load main config (database connection)
require_once __DIR__ . '/../config.php';

// ===========================================
// PRODUCTION SETTINGS
// ===========================================

// Environment: development, staging, or production
define('API_ENV', envValue('API_ENV', 'development'));

// JWT Configuration
$jwtSecret = envValue('JWT_SECRET', 'change-me-generate-a-secure-random-secret');
if (API_ENV === 'production' && $jwtSecret === 'change-me-generate-a-secure-random-secret') {
    http_response_code(500);
    exit('JWT_SECRET must be configured in production.');
}
define('JWT_SECRET', $jwtSecret);
define('JWT_ALGORITHM', 'HS256');
define('JWT_ACCESS_EXPIRY', 60 * 60 * 24 * 7);   // 7 days
define('JWT_REFRESH_EXPIRY', 60 * 60 * 24 * 30); // 30 days

// Rate Limiting
define('RATE_LIMIT_REQUESTS', 100);
define('RATE_LIMIT_WINDOW', 60); // 60 seconds

// Pagination Defaults
define('DEFAULT_PAGE_SIZE', 20);
define('MAX_PAGE_SIZE', 100);

// Cache Settings
define('CACHE_DIR', __DIR__ . '/cache/');
define('CACHE_TTL_SHORT', 60 * 5);    // 5 minutes
define('CACHE_TTL_MEDIUM', 60 * 30);  // 30 minutes
define('CACHE_TTL_LONG', 60 * 60);    // 1 hour

// Create cache directory
if (!file_exists(CACHE_DIR)) {
    mkdir(CACHE_DIR, 0755, true);
}

// ===========================================
// CORS - PRODUCTION (Restrict origins)
// ===========================================
function setCorsHeaders()
{
    $configuredOrigins = envValue('CORS_ALLOWED_ORIGINS', '');
    $allowedOrigins = array_filter(array_map('trim', explode(',', $configuredOrigins)));

    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    // Check if origin is allowed
    if (in_array($origin, $allowedOrigins)) {
        header("Access-Control-Allow-Origin: $origin");
    } elseif (API_ENV !== 'production') {
        // Allow all in development
        header("Access-Control-Allow-Origin: *");
    }

    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Max-Age: 86400");

    // Handle preflight
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

// ===========================================
// SECURITY HEADERS
// ===========================================
function setSecurityHeaders()
{
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: DENY");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");

    if (API_ENV === 'production') {
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
    }
}

// ===========================================
// ERROR CODES
// ===========================================
define('ERROR_CODES', [
    'AUTH_REQUIRED' => 'Authentication required',
    'AUTH_INVALID' => 'Invalid credentials',
    'AUTH_EXPIRED' => 'Token expired',
    'AUTH_BANNED' => 'Account is banned',
    'AUTH_UNVERIFIED' => 'Email not verified',
    'VALIDATION_ERROR' => 'Validation failed',
    'NOT_FOUND' => 'Resource not found',
    'FORBIDDEN' => 'Access denied',
    'RATE_LIMITED' => 'Too many requests',
    'SERVER_ERROR' => 'Internal server error',
    'UPLOAD_ERROR' => 'File upload failed',
    'INVALID_FILE' => 'Invalid file type',
    'INVALID_TOKEN' => 'Invalid or expired token',
]);

// ===========================================
// REQUEST INPUT HELPER
// ===========================================
function getRequestInput()
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (strpos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?? [];
    }

    return array_merge($_GET, $_POST);
}

// ===========================================
// VALIDATION HELPER
// ===========================================
function validateInput($data, $rules)
{
    $errors = [];

    foreach ($rules as $field => $rule) {
        $value = $data[$field] ?? null;
        $rulesList = explode('|', $rule);

        foreach ($rulesList as $r) {
            if ($r === 'required' && (is_null($value) || $value === '')) {
                $errors[$field] = "$field is required";
                break;
            }

            if ($r === 'email' && $value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[$field] = "Invalid email format";
                break;
            }

            if (strpos($r, 'min:') === 0 && $value) {
                $min = (int) substr($r, 4);
                if (strlen($value) < $min) {
                    $errors[$field] = "$field must be at least $min characters";
                    break;
                }
            }

            if (strpos($r, 'max:') === 0 && $value) {
                $max = (int) substr($r, 4);
                if (strlen($value) > $max) {
                    $errors[$field] = "$field must not exceed $max characters";
                    break;
                }
            }

            if ($r === 'numeric' && $value && !is_numeric($value)) {
                $errors[$field] = "$field must be numeric";
                break;
            }

            if ($r === 'bahria_email' && $value) {
                if (!preg_match('/(@student\.bahria\.edu\.pk$|^\d{2}-\d{6}-\d{3}$)/i', $value)) {
                    $errors[$field] = "Only Bahria University emails allowed";
                    break;
                }
            }
        }
    }

    return $errors;
}

// ===========================================
// INITIALIZE
// ===========================================

// Set CORS headers
setCorsHeaders();

// Set security headers
setSecurityHeaders();

// Set JSON content type
header('Content-Type: application/json; charset=utf-8');

// Enable gzip compression
if (extension_loaded('zlib') && !ini_get('zlib.output_compression')) {
    ob_start('ob_gzhandler');
}

// Disable error display in production (log only)
if (API_ENV === 'production') {
    ini_set('display_errors', 0);
    error_reporting(E_ALL);
}
