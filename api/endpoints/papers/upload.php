<?php
/**
 * POST /api/papers
 * Upload a new paper
 */

$user = AuthMiddleware::require();

// Check if file is uploaded
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
    ];
    $errorCode = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
    $message = $errorMessages[$errorCode] ?? 'File upload error';
    ApiResponse::error('UPLOAD_ERROR', $message, null, 400);
}

$file = $_FILES['file'];

// Validate file size
if ($file['size'] > MAX_FILE_SIZE) {
    ApiResponse::error('UPLOAD_ERROR', 'File size exceeds 10MB limit', null, 400);
}

// Validate file type
$allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    ApiResponse::error('INVALID_FILE', 'Only PDF and image files are allowed', null, 400);
}

// Validate form data
$input = $_POST;
$errors = validateInput($input, [
    'campus' => 'required',
    'department' => 'required',
    'semester' => 'required|numeric',
    'course_code' => 'required|max:20',
    'course_title' => 'required|max:200',
    'teacher_name' => 'required|max:100',
    'year' => 'required|numeric',
    'exam_type' => 'required'
]);

if (!empty($errors)) {
    ApiResponse::validationError($errors);
}

$campus = sanitize($input['campus']);
$department = sanitize($input['department']);
$semester = (int) $input['semester'];
$courseCode = sanitize($input['course_code']);
$courseTitle = sanitize($input['course_title']);
$teacherName = sanitize($input['teacher_name']);
$year = (int) $input['year'];
$examType = sanitize($input['exam_type']);

// Valid campuses
$validCampuses = ['Islamabad E-8', 'Islamabad H-11', 'Karachi', 'Karachi Health Sciences', 'Lahore'];
if (!in_array($campus, $validCampuses)) {
    ApiResponse::validationError(['campus' => 'Invalid campus selected']);
}

// Validate exam type
$validExamTypes = ['Mid', 'Final', 'Quiz', 'Assignment', 'Other'];
if (!in_array($examType, $validExamTypes)) {
    ApiResponse::validationError(['exam_type' => 'Invalid exam type']);
}

// Validate year
$currentYear = (int) date('Y');
if ($year < 2000 || $year > $currentYear + 1) {
    ApiResponse::validationError(['year' => 'Invalid year']);
}

try {
    // Generate safe filename
    $mimeMap = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/png' => 'png'
    ];
    $extension = $mimeMap[$mimeType];
    $fileType = ($extension === 'pdf') ? 'pdf' : 'image';
    $uniqueName = uniqid() . '_' . time() . '.' . $extension;
    $uploadPath = UPLOAD_DIR . $uniqueName;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        ApiResponse::error('UPLOAD_ERROR', 'Failed to save file', null, 500);
    }

    // Generate title
    $title = "$courseTitle - $campus - $year ($examType)";

    // Insert into database
    $stmt = $pdo->prepare("
        INSERT INTO papers (
            user_id, title, file_name, file_path, file_type, file_size,
            campus, department, semester, course_code, course_title,
            teacher_name, year, exam_type
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $user['id'],
        $title,
        $file['name'],
        $uniqueName,
        $fileType,
        $file['size'],
        $campus,
        $department,
        $semester,
        $courseCode,
        $courseTitle,
        $teacherName,
        $year,
        $examType
    ]);

    $paperId = $pdo->lastInsertId();

    // Log activity
    logActivity($pdo, $user['id'], 'upload', $paperId);

    // Clear papers cache
    ApiCache::delete('papers_trending');
    ApiCache::delete('papers_recent');

    // Fetch created paper
    $stmt = $pdo->prepare("SELECT * FROM papers WHERE id = ?");
    $stmt->execute([$paperId]);
    $paper = $stmt->fetch(PDO::FETCH_ASSOC);

    $paper['upload_date'] = date('c', strtotime($paper['upload_date']));
    unset($paper['file_path']);
    unset($paper['file_name']);

    ApiResponse::success([
        'message' => 'Paper uploaded successfully!',
        'paper' => $paper
    ], null, 201);

} catch (Exception $e) {
    error_log("Upload error: " . $e->getMessage());
    // Clean up file if it was uploaded
    if (isset($uploadPath) && file_exists($uploadPath)) {
        unlink($uploadPath);
    }
    ApiResponse::error('SERVER_ERROR', 'Upload failed', null, 500);
}
