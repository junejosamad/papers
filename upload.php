<?php
require_once 'config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isValidCsrf()) {
            throw new Exception('Your session has expired. Please refresh the page and try again');
        }

        $max_size_mb = round(MAX_FILE_SIZE / 1048576, 1);

        // Validate file upload
        $upload_error = $_FILES['paper_file']['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($upload_error !== UPLOAD_ERR_OK) {
            $upload_messages = [
                UPLOAD_ERR_INI_SIZE => "File size exceeds {$max_size_mb}MB limit",
                UPLOAD_ERR_FORM_SIZE => "File size exceeds {$max_size_mb}MB limit",
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded. Please try again',
                UPLOAD_ERR_NO_FILE => 'Please choose a file to upload',
            ];
            throw new Exception($upload_messages[$upload_error] ?? 'File upload error');
        }

        $file = $_FILES['paper_file'];

        // Check file size
        if ($file['size'] > MAX_FILE_SIZE) {
            throw new Exception("File size exceeds {$max_size_mb}MB limit");
        }

        // Check file type
        $allowed_types = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime_type, $allowed_types)) {
            throw new Exception('Invalid file type. Only PDF and images are allowed');
        }

        // Get form data
        $campus = sanitize($_POST['campus'] ?? '');
        $department = sanitize($_POST['department'] ?? '');
        $semester = (int) ($_POST['semester'] ?? 0);
        $course_code = sanitize($_POST['course_code'] ?? '');
        $course_title = sanitize($_POST['course_title'] ?? '');
        $teacher_name = sanitize($_POST['teacher_name'] ?? '');
        $year = (int) ($_POST['year'] ?? 0);
        $exam_type = sanitize($_POST['exam_type'] ?? '');

        // Validate form data
        if ($department === '' || $course_code === '' || $course_title === '' || $teacher_name === '') {
            throw new Exception('Please fill in all required fields');
        }
        if (strlen($course_code) > 20 || strlen($course_title) > 200 || strlen($teacher_name) > 100) {
            throw new Exception('One or more fields are too long');
        }
        if (!in_array($campus, VALID_CAMPUSES, true)) {
            throw new Exception('Invalid campus selected');
        }
        if (!in_array($exam_type, VALID_EXAM_TYPES, true)) {
            throw new Exception('Invalid exam type');
        }
        if ($semester < 1 || $semester > 12) {
            throw new Exception('Invalid semester');
        }
        if ($year < 2000 || $year > (int) date('Y') + 1) {
            throw new Exception('Invalid year');
        }

        // Generate title
        $title = "$course_title - $campus - $year ($exam_type)";

        // Determine file type and assign safe extension
        $mime_map = [
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png'
        ];

        if (!isset($mime_map[$mime_type])) {
            throw new Exception('Invalid file type detected');
        }

        $extension = $mime_map[$mime_type];

        // Set file_type for database (pdf or image)
        $file_type = ($extension === 'pdf') ? 'pdf' : 'image';

        // Generate unique filename
        $unique_name = bin2hex(random_bytes(16)) . '.' . $extension;
        $upload_path = UPLOAD_DIR . $unique_name;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            throw new Exception('Failed to save file');
        }

        // Insert into database
        $stmt = $pdo->prepare("
            INSERT INTO papers (
                user_id, title, file_name, file_path, file_type, file_size,
                campus, department, semester, course_code, course_title,
                teacher_name, year, exam_type
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $_SESSION['user_id'],
            $title,
            $file['name'],
            $unique_name,
            $file_type,
            $file['size'],
            $campus,
            $department,
            $semester,
            $course_code,
            $course_title,
            $teacher_name,
            $year,
            $exam_type
        ]);

        $paper_id = $pdo->lastInsertId();
        $upload_path = null; // Saved to the database; keep the file from here on
        logActivity($pdo, $_SESSION['user_id'], 'upload', $paper_id);

        header('Location: dashboard.php?upload=success');
        exit;

    } catch (Exception $e) {
        if (isset($upload_path) && file_exists($upload_path)) {
            unlink($upload_path);
        }

        // Database errors are logged, not shown to the user
        $message = $e instanceof PDOException ? 'Upload failed. Please try again' : $e->getMessage();
        if ($e instanceof PDOException) {
            error_log("Upload error: " . $e->getMessage());
        }
        header('Location: dashboard.php?error=' . urlencode($message));
        exit;
    }
} else {
    header('Location: dashboard.php');
    exit;
}
?>