<?php
/**
 * GET /api/meta/exam-types
 * Get list of exam types
 */

$examTypes = [
    ['id' => 'Mid', 'name' => 'Mid Term'],
    ['id' => 'Final', 'name' => 'Final Exam'],
    ['id' => 'Quiz', 'name' => 'Quiz'],
    ['id' => 'Assignment', 'name' => 'Assignment'],
    ['id' => 'Other', 'name' => 'Other']
];

ApiResponse::setCacheHeaders(CACHE_TTL_LONG);
ApiResponse::success($examTypes);
