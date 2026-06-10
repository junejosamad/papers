<?php
/**
 * GET /api/meta/departments
 * GET /api/meta/departments/{campus}
 * Get departments for a campus
 */

$campusParam = $campus ?? $_GET['campus'] ?? '';

$allDepartments = [
    'Islamabad E-8' => [
        ['id' => 'MS', 'name' => 'Management Sciences'],
        ['id' => 'CS', 'name' => 'Computer Science'],
        ['id' => 'IT', 'name' => 'Information Technology'],
        ['id' => 'AI', 'name' => 'Artificial Intelligence'],
        ['id' => 'HSS', 'name' => 'Humanities & Social Sciences'],
        ['id' => 'LAW', 'name' => 'Law'],
        ['id' => 'MEDIA', 'name' => 'Media Studies'],
        ['id' => 'PSY', 'name' => 'Psychology']
    ],
    'Islamabad H-11' => [
        ['id' => 'EE', 'name' => 'Electrical Engineering'],
        ['id' => 'SE', 'name' => 'Software Engineering'],
        ['id' => 'CE', 'name' => 'Computer Engineering'],
        ['id' => 'RIS', 'name' => 'RIS'],
        ['id' => 'AI', 'name' => 'Artificial Intelligence'],
        ['id' => 'PSY', 'name' => 'Psychology'],
        ['id' => 'EES', 'name' => 'Earth & Environmental Sciences'],
        ['id' => 'GEO', 'name' => 'Geology']
    ],
    'Karachi' => [
        ['id' => 'MS', 'name' => 'Management Sciences'],
        ['id' => 'CS', 'name' => 'Computer Science'],
        ['id' => 'EE', 'name' => 'Electrical Engineering'],
        ['id' => 'SE', 'name' => 'Software Engineering'],
        ['id' => 'HSS', 'name' => 'Humanities & Social Sciences'],
        ['id' => 'MEDIA', 'name' => 'Media Arts & Sciences'],
        ['id' => 'IS', 'name' => 'Islamic Studies'],
        ['id' => 'PSY', 'name' => 'Professional Psychology'],
        ['id' => 'MSci', 'name' => 'Maritime Sciences']
    ],
    'Karachi Health Sciences' => [
        ['id' => 'MEDICAL', 'name' => 'Medical & Dental Sciences'],
        ['id' => 'AHS', 'name' => 'Allied Health Sciences']
    ],
    'Lahore' => [
        ['id' => 'MS', 'name' => 'Management Sciences'],
        ['id' => 'CS', 'name' => 'Computer Science'],
        ['id' => 'PSY', 'name' => 'Professional Psychology']
    ]
];

if ($campusParam && isset($allDepartments[$campusParam])) {
    $departments = $allDepartments[$campusParam];
} else {
    // Return all departments grouped by campus
    $departments = $allDepartments;
}

ApiResponse::setCacheHeaders(CACHE_TTL_LONG);
ApiResponse::success($departments);
