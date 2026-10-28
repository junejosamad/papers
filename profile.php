<?php
require_once 'config.php';
requireLogin();

$success = '';
$error = '';

// Get user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isValidCsrf()) {
    $error = 'Your session has expired. Please refresh the page and try again.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($_POST['full_name'] ?? '');
    $campus = sanitize($_POST['campus'] ?? '');
    $department = sanitize($_POST['department'] ?? '');
    $semester = (int) ($_POST['semester'] ?? 0);
    $whatsapp_number = sanitize($_POST['whatsapp_number'] ?? '');

    // Handle profile image upload
    $profile_image = $user['profile_image'];
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        // Extension comes from the detected MIME type, never from the user's filename
        $allowed = ['image/jpeg' => 'jpg', 'image/jpg' => 'jpg', 'image/png' => 'png'];
        $file = $_FILES['profile_image'];

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (isset($allowed[$mime]) && $file['size'] <= 2097152) { // 2MB limit
            $ext = $allowed[$mime];

            // Create profile directory if not exists
            $profile_dir = UPLOAD_DIR . 'profile/';
            if (!file_exists($profile_dir)) {
                mkdir($profile_dir, 0755, true);
            }

            // Use enrollment number as filename
            $filename = $user['enrollment_number'] . '.' . $ext;
            $upload_path = $profile_dir . $filename;

            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Store relative path in database
                $profile_image = 'profile/' . $filename;
            }
        }
    }

    // Update profile
    $stmt = $pdo->prepare("
        UPDATE users 
        SET full_name = ?, campus = ?, department = ?, semester = ?, profile_image = ?, whatsapp_number = ?
        WHERE id = ?
    ");
    $stmt->execute([$full_name, $campus, $department, $semester, $profile_image, $whatsapp_number, $_SESSION['user_id']]);

    $success = 'Profile updated successfully!';

    // Refresh user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
}

// Get user statistics
$stmt = $pdo->prepare("SELECT COUNT(*) as upload_count FROM papers WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT COUNT(*) as download_count 
    FROM activity_logs 
    WHERE user_id = ? AND action_type = 'download'
");
$stmt->execute([$_SESSION['user_id']]);
$download_stats = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Bahria Papers Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/darkmode.js"></script>
</head>

<body class="dashboard-body">
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo">
                <span class="material-icons">school</span>
                <div class="logo-text">
                    <h1>Bahria University</h1>
                    <p>Past Papers Portal</p>
                </div>
            </div>
            <div class="user-info">
                <button class="btn btn-secondary" onclick="toggleTheme()" title="Toggle Dark/Light Mode"
                    style="padding: 8px 12px; min-width: auto;">
                    <span class="material-icons" id="theme-icon">dark_mode</span>
                </button>
                <a href="dashboard.php" class="btn btn-secondary">
                    <span class="material-icons">arrow_back</span>
                    Back to Dashboard
                </a>
                <a href="logout.php" class="btn btn-secondary">
                    <span class="material-icons">logout</span>
                    Logout
                </a>
            </div>
        </div>

        <!-- Profile Content -->
        <div class="profile-container">
            <div class="profile-sidebar">
                <div class="profile-card">
                    <div class="profile-image-container">
                        <?php if ($user['profile_image']): ?>
                            <img src="uploads/<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile"
                                class="profile-img">
                        <?php else: ?>
                            <div class="profile-placeholder">
                                <span class="material-icons">person</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <h2><?php echo htmlspecialchars($user['full_name'] ?: $user['enrollment_number']); ?></h2>
                    <p class="enrollment"><?php echo htmlspecialchars($user['enrollment_number']); ?></p>
                    <p class="email"><?php echo htmlspecialchars($user['email']); ?></p>
                    <?php if (!empty($user['whatsapp_number'])): ?>
                        <p class="email"
                            style="display: flex; align-items: center; justify-content: center; gap: 5px; margin-top: 5px;">
                            <span class="material-icons" style="font-size: 16px; color: #25D366;">whatsapp</span>
                            <?php echo htmlspecialchars($user['whatsapp_number']); ?>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="stats-card">
                    <h3>My Statistics</h3>
                    <div class="stat-item">
                        <span class="material-icons">upload_file</span>
                        <div>
                            <strong><?php echo $stats['upload_count']; ?></strong>
                            <span>Papers Uploaded</span>
                        </div>
                    </div>
                    <div class="stat-item">
                        <span class="material-icons">download</span>
                        <div>
                            <strong><?php echo $download_stats['download_count']; ?></strong>
                            <span>Papers Downloaded</span>
                        </div>
                    </div>
                    <div class="stat-item">
                        <span class="material-icons">calendar_today</span>
                        <div>
                            <strong><?php echo date('M Y', strtotime($user['created_at'])); ?></strong>
                            <span>Member Since</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="profile-main">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <span class="material-icons">check_circle</span>
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <span class="material-icons">error</span>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div class="profile-form-card">
                    <h3>Edit Profile</h3>
                    <form method="POST" enctype="multipart/form-data">
                        <?php echo csrfField(); ?>
                        <div class="form-group">
                            <label>Profile Picture</label>
                            <input type="file" name="profile_image" accept="image/jpeg,image/jpg,image/png"
                                class="input-field">
                            <span class="helper-text">JPG, JPEG or PNG. Max 2MB</span>
                        </div>

                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="full_name" class="input-field"
                                value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>WhatsApp Number (Optional)</label>
                            <div style="position: relative;">
                                <span class="material-icons"
                                    style="position: absolute; left: 10px; top: 16px; color: #25D366;">whatsapp</span>
                                <input type="text" name="whatsapp_number" class="input-field"
                                    style="padding-left: 40px;" placeholder="e.g. 03001234567"
                                    value="<?php echo htmlspecialchars($user['whatsapp_number'] ?? ''); ?>">
                            </div>
                            <span class="helper-text">We will use this to send you exam reminders.</span>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Campus *</label>
                                <select name="campus" required class="input-field">
                                    <option value="">Select Campus</option>
                                    <option value="Islamabad E-8" <?php echo ($user['campus'] ?? '') === 'Islamabad E-8' ? 'selected' : ''; ?>>Islamabad E-8 Campus</option>
                                    <option value="Islamabad H-11" <?php echo ($user['campus'] ?? '') === 'Islamabad H-11' ? 'selected' : ''; ?>>Islamabad H-11 Campus</option>
                                    <option value="Karachi" <?php echo ($user['campus'] ?? '') === 'Karachi' ? 'selected' : ''; ?>>Karachi Campus</option>
                                    <option value="Karachi Health Sciences" <?php echo ($user['campus'] ?? '') === 'Karachi Health Sciences' ? 'selected' : ''; ?>>Karachi (Health Sciences)
                                    </option>
                                    <option value="Lahore" <?php echo ($user['campus'] ?? '') === 'Lahore' ? 'selected' : ''; ?>>Lahore Campus</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Department *</label>
                                <select name="department" required class="input-field">
                                    <option value="">Select Campus First</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Current Semester *</label>
                            <select name="semester" required class="input-field">
                                <option value="">Select Semester</option>
                                <?php for ($i = 1; $i <= 8; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo ($user['semester'] ?? '') == $i ? 'selected' : ''; ?>>
                                        Semester <?php echo $i; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary btn-full">
                            <span class="material-icons">save</span>
                            Save Changes
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Campus and Department Data (Same as Dashboard)
        const campusDepartments = {
            "Islamabad E-8": [
                { value: "MS", text: "Management Sciences" },
                { value: "CS", text: "Computer Science" },
                { value: "IT", text: "Information Technology" },
                { value: "AI", text: "Artificial Intelligence" },
                { value: "H&SS", text: "Humanities & Social Sciences" },
                { value: "LAW", text: "Law" },
                { value: "MEDIA", text: "Media Studies" },
                { value: "PSY", text: "Psychology" }
            ],
            "Islamabad H-11": [
                { value: "EE", text: "Electrical Engineering" },
                { value: "SE", text: "Software Engineering" },
                { value: "CE", text: "Computer Engineering" },
                { value: "RIS", text: "RIS" },
                { value: "AI", text: "Artificial Intelligence" },
                { value: "PSY", text: "Psychology" },
                { value: "EES", text: "Earth & Environmental Sciences" },
                { value: "GEO", text: "Geology" }
            ],
            "Karachi": [
                { value: "MS", text: "Management Sciences" },
                { value: "CS", text: "Computer Science" },
                { value: "EE", text: "Electrical Engineering" },
                { value: "SE", text: "Software Engineering" },
                { value: "H&SS", text: "Humanities & Social Sciences" },
                { value: "MEDIA", text: "Media Arts & Sciences" },
                { value: "IS", text: "Islamic Studies" },
                { value: "PSY", text: "Professional Psychology" },
                { value: "MSci", text: "Maritime Sciences" }
            ],
            "Karachi Health Sciences": [
                { value: "MEDICAL", text: "Medical & Dental Sciences" },
                { value: "AHS", text: "Allied Health Sciences" }
            ],
            "Lahore": [
                { value: "MS", text: "Management Sciences" },
                { value: "CS", text: "Computer Science" },
                { value: "PSY", text: "Professional Psychology" }
            ]
        };

        function updateDepartments(campusSelectElem, deptSelectElem, defaultOptionText, selectedDeptValue) {
            const selectedCampus = campusSelectElem.value;
            deptSelectElem.innerHTML = ''; // Clear existing options

            // Add default option
            const defaultOpt = document.createElement('option');
            defaultOpt.value = '';
            defaultOpt.textContent = defaultOptionText;
            deptSelectElem.appendChild(defaultOpt);

            if (selectedCampus && campusDepartments[selectedCampus]) {
                const departments = campusDepartments[selectedCampus];
                departments.forEach(dept => {
                    const option = document.createElement('option');
                    option.value = dept.value;
                    option.textContent = dept.text;
                    if (dept.value === selectedDeptValue) {
                        option.selected = true;
                    }
                    deptSelectElem.appendChild(option);
                });
            } else if (!selectedCampus) {
                // Reset to "Select Campus First" if no campus selected
                defaultOpt.textContent = "Select Campus First";
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const campusSelect = document.querySelector('select[name="campus"]');
            const deptSelect = document.querySelector('select[name="department"]');
            const userDept = '<?php echo $user['department'] ?? ''; ?>';

            campusSelect.addEventListener('change', () => {
                updateDepartments(campusSelect, deptSelect, 'Select Department');
            });

            // Initial load
            updateDepartments(campusSelect, deptSelect, 'Select Department', userDept);
        });
    </script>
</body>

</html>