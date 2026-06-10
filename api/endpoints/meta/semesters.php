<?php
/**
 * GET /api/meta/semesters
 * Get list of semesters
 */

$semesters = [];
for ($i = 1; $i <= 10; $i++) {
    $semesters[] = ['id' => $i, 'name' => "Semester $i"];
}

ApiResponse::setCacheHeaders(CACHE_TTL_LONG);
ApiResponse::success($semesters);
