<?php
/**
 * GET /api/meta/campuses
 * Get list of campuses
 */

$campuses = [
    ['id' => 'islamabad-e8', 'name' => 'Islamabad E-8', 'city' => 'Islamabad'],
    ['id' => 'islamabad-h11', 'name' => 'Islamabad H-11', 'city' => 'Islamabad'],
    ['id' => 'karachi', 'name' => 'Karachi', 'city' => 'Karachi'],
    ['id' => 'karachi-health', 'name' => 'Karachi Health Sciences', 'city' => 'Karachi'],
    ['id' => 'lahore', 'name' => 'Lahore', 'city' => 'Lahore']
];

ApiResponse::setCacheHeaders(CACHE_TTL_LONG);
ApiResponse::success($campuses);
