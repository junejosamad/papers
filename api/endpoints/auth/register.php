<?php
/**
 * POST /api/auth/register
 * Register new user (Bahria email only)
 */

$input = getRequestInput();

// Validate input
$errors = validateInput($input, [
    'enrollment_number' => 'required|min:5|max:20',
    'email' => 'required|email|bahria_email',
    'full_name' => 'required|min:2|max:100',
    'campus' => 'required',
    'department' => 'required'
]);

if (!empty($errors)) {
    ApiResponse::validationError($errors);
}

$enrollment = strtoupper(trim($input['enrollment_number']));
$email = strtolower(trim($input['email']));
$fullName = trim($input['full_name']);
$campus = trim($input['campus']);
$department = trim($input['department']);
$semester = isset($input['semester']) ? (int) $input['semester'] : null;
$whatsapp = isset($input['whatsapp']) ? trim($input['whatsapp']) : null;

// Validate enrollment format (XX-XXXXXX-XXX)
if (!preg_match('/^\d{2}-\d{6}-\d{3}$/', $enrollment)) {
    ApiResponse::validationError(['enrollment_number' => 'Invalid enrollment format. Use XX-XXXXXX-XXX']);
}

// Validate Bahria email
if (!preg_match('/@bahria\.edu\.pk$/i', $email)) {
    ApiResponse::validationError(['email' => 'Only Bahria University emails (@bahria.edu.pk) are allowed']);
}

// Valid campuses
$validCampuses = ['Islamabad E-8', 'Islamabad H-11', 'Karachi', 'Karachi Health Sciences', 'Lahore'];
if (!in_array($campus, $validCampuses)) {
    ApiResponse::validationError(['campus' => 'Invalid campus selected']);
}

try {
    // Check if enrollment already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE enrollment_number = ?");
    $stmt->execute([$enrollment]);
    if ($stmt->fetch()) {
        ApiResponse::error('VALIDATION_ERROR', 'Enrollment number already registered', 'enrollment_number', 409);
    }

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        ApiResponse::error('VALIDATION_ERROR', 'Email already registered', 'email', 409);
    }

    // Generate verification token
    $verificationToken = bin2hex(random_bytes(32));
    $tokenExpiry = date('Y-m-d H:i:s', strtotime('+24 hours'));

    // Insert user (no password yet - set on verification)
    $stmt = $pdo->prepare("
        INSERT INTO users (
            enrollment_number, email, password_hash, full_name, campus, 
            department, semester, whatsapp_number, verification_token, token_expiry
        ) VALUES (?, ?, '', ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $enrollment,
        $email,
        $fullName,
        $campus,
        $department,
        $semester,
        $whatsapp,
        $verificationToken,
        $tokenExpiry
    ]);

    $userId = $pdo->lastInsertId();

    // Send verification email
    try {
        sendVerificationEmail($email, $verificationToken, $enrollment);
        $emailSent = true;
    } catch (Exception $e) {
        error_log("Email send error: " . $e->getMessage());
        $emailSent = false;
    }

    // Log activity
    logActivity($pdo, $userId, 'signup');

    ApiResponse::success([
        'message' => 'Registration successful! Please check your email to verify your account and set your password.',
        'email_sent' => $emailSent,
        'user_id' => $userId
    ], null, 201);

} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    ApiResponse::error('SERVER_ERROR', 'Registration failed', null, 500);
}
