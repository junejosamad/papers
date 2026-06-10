<?php
/**
 * GET /api/auth/me
 * Get current authenticated user
 */

$user = AuthMiddleware::require();

ApiResponse::noCache();
ApiResponse::success([
    'user' => $user
]);
