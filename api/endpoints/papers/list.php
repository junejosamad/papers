<?php
/**
 * GET /api/papers
 * List papers with filters and pagination
 */

// Optional auth - for tracking favorites
$user = AuthMiddleware::optional();
$userId = $user ? $user['id'] : null;

// Get query parameters
$campus = $_GET['campus'] ?? '';
$department = $_GET['department'] ?? '';
$semester = $_GET['semester'] ?? '';
$course = $_GET['course'] ?? '';
$teacher = $_GET['teacher'] ?? '';
$examType = $_GET['exam_type'] ?? '';
$year = $_GET['year'] ?? '';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = min(MAX_PAGE_SIZE, max(1, (int) ($_GET['per_page'] ?? DEFAULT_PAGE_SIZE)));
$offset = ($page - 1) * $perPage;

// Build cache key
$cacheKey = "papers_list_" . md5(json_encode([
    $campus,
    $department,
    $semester,
    $course,
    $teacher,
    $examType,
    $year,
    $search,
    $sort,
    $page,
    $perPage
]));

// Build query
$where = ["1=1"];
$params = [];

if ($campus) {
    $where[] = "p.campus = ?";
    $params[] = $campus;
}

if ($department) {
    $where[] = "p.department = ?";
    $params[] = $department;
}

if ($semester) {
    $where[] = "p.semester = ?";
    $params[] = (int) $semester;
}

if ($course) {
    $where[] = "(p.course_code LIKE ? OR p.course_title LIKE ?)";
    $params[] = "%$course%";
    $params[] = "%$course%";
}

if ($teacher) {
    $where[] = "p.teacher_name LIKE ?";
    $params[] = "%$teacher%";
}

if ($examType) {
    $where[] = "p.exam_type = ?";
    $params[] = $examType;
}

if ($year) {
    $where[] = "p.year = ?";
    $params[] = (int) $year;
}

if ($search) {
    $where[] = "(p.title LIKE ? OR p.course_code LIKE ? OR p.course_title LIKE ? OR p.teacher_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereClause = implode(' AND ', $where);

// Sorting
$orderBy = match ($sort) {
    'popular' => 'p.views DESC, p.downloads DESC',
    'downloads' => 'p.downloads DESC',
    'oldest' => 'p.upload_date ASC',
    default => 'p.upload_date DESC'
};

try {
    // Get total count
    $countQuery = "SELECT COUNT(*) FROM papers p WHERE $whereClause";
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $total = (int) $stmt->fetchColumn();

    // Get papers with uploader info
    $favJoin = $userId
        ? "LEFT JOIN favorites f ON p.id = f.paper_id AND f.user_id = ?"
        : "";
    $favSelect = $userId
        ? ", IF(f.id IS NOT NULL, 1, 0) as is_favorited"
        : ", 0 as is_favorited";

    $query = "
        SELECT p.id, p.title, p.file_type, p.file_size, p.campus, p.department,
               p.semester, p.course_code, p.course_title, p.teacher_name,
               p.year, p.exam_type, p.upload_date, p.downloads, p.views,
               u.enrollment_number as uploader_enrollment,
               u.full_name as uploader_name
               $favSelect
        FROM papers p
        JOIN users u ON p.user_id = u.id
        $favJoin
        WHERE $whereClause
        ORDER BY $orderBy
        LIMIT ? OFFSET ?
    ";

    $queryParams = $userId ? [$userId, ...$params, $perPage, $offset] : [...$params, $perPage, $offset];

    $stmt = $pdo->prepare($query);
    $stmt->execute($queryParams);
    $papers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format dates and booleans
    foreach ($papers as &$paper) {
        $paper['is_favorited'] = (bool) $paper['is_favorited'];
        $paper['upload_date'] = date('c', strtotime($paper['upload_date']));
        $paper['file_size_formatted'] = formatFileSize($paper['file_size']);
    }

    // Set cache headers for 5 minutes
    ApiResponse::setCacheHeaders(300);

    // Generate and check ETag
    $etag = ApiCache::generateETag($papers);
    header("ETag: $etag");
    ApiCache::checkNotModified($etag);

    ApiResponse::paginated($papers, $page, $perPage, $total);

} catch (Exception $e) {
    error_log("Papers list error: " . $e->getMessage());
    ApiResponse::error('SERVER_ERROR', 'Failed to fetch papers', null, 500);
}

// Helper function for file size formatting
function formatFileSize($bytes)
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}
