<?php
require_once 'config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate file upload
        if (!isset($_FILES['paper_file']) || $_FILES['paper_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error');
        }

        $file = $_FILES['paper_file'];

        // Check file size
        if ($file['size'] > MAX_FILE_SIZE) {
            throw new Exception('File size exceeds 10MB limit');
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
        $campus = sanitize($_POST['campus']);
        $department = sanitize($_POST['department']);
        $semester = (int) $_POST['semester'];
        $course_code = sanitize($_POST['course_code']);
        $course_title = sanitize($_POST['course_title']);
        $teacher_name = sanitize($_POST['teacher_name']);
        $year = (int) $_POST['year'];
        $exam_type = sanitize($_POST['exam_type']);

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
        $unique_name = uniqid() . '_' . time() . '.' . $extension;
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
        logActivity($pdo, $_SESSION['user_id'], 'upload', $paper_id);

        header('Location: dashboard.php?upload=success');
        exit;

    } catch (Exception $e) {
        header('Location: dashboard.php?error=' . urlencode($e->getMessage()));
        exit;
    }
} else {
    header('Location: dashboard.php');
    exit;
}
?>