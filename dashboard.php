<?php
require_once 'config.php';
requireLogin();

// Get user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$current_user = $stmt->fetch();

// Get user favorites
$stmt = $pdo->prepare("SELECT paper_id FROM favorites WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$favorites = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Pagination settings
$papers_per_page = 12;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $papers_per_page;

// Get filters
$campus = $_GET['campus'] ?? '';
$dept = $_GET['dept'] ?? '';
$semester = $_GET['semester'] ?? '';
$course = $_GET['course'] ?? '';
$teacher = $_GET['teacher'] ?? '';
$search = $_GET['search'] ?? '';

// Build query for counting total papers
$count_query = "SELECT COUNT(*) FROM papers p WHERE 1=1";
$params = [];

if ($campus) {
    $count_query .= " AND p.campus = ?";
    $params[] = $campus;
}
if ($dept) {
    $count_query .= " AND p.department = ?";
    $params[] = $dept;
}
if ($semester) {
    $count_query .= " AND p.semester = ?";
    $params[] = $semester;
}
if ($course) {
    $count_query .= " AND (p.course_code LIKE ? OR p.course_title LIKE ?)";
    $params[] = "%$course%";
    $params[] = "%$course%";
}
if ($teacher) {
    $count_query .= " AND p.teacher_name LIKE ?";
    $params[] = "%$teacher%";
}
if ($search) {
    $count_query .= " AND (p.title LIKE ? OR p.course_code LIKE ? OR p.course_title LIKE ? OR p.teacher_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Get total count
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_papers = $stmt->fetchColumn();
$total_pages = ceil($total_papers / $papers_per_page);

// Build query for fetching papers
$query = "SELECT p.*, u.enrollment_number as uploader
          FROM papers p 
          JOIN users u ON p.user_id = u.id 
          WHERE 1=1";
$params = [];

if ($campus) {
    $query .= " AND p.campus = ?";
    $params[] = $campus;
}
if ($dept) {
    $query .= " AND p.department = ?";
    $params[] = $dept;
}
if ($semester) {
    $query .= " AND p.semester = ?";
    $params[] = $semester;
}
if ($course) {
    $query .= " AND (p.course_code LIKE ? OR p.course_title LIKE ?)";
    $params[] = "%$course%";
    $params[] = "%$course%";
}
if ($teacher) {
    $query .= " AND p.teacher_name LIKE ?";
    $params[] = "%$teacher%";
}
if ($search) {
    $query .= " AND (p.title LIKE ? OR p.course_code LIKE ? OR p.course_title LIKE ? OR p.teacher_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY p.upload_date DESC LIMIT ? OFFSET ?";
$params[] = $papers_per_page;
$params[] = $offset;

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$papers = $stmt->fetchAll();

// Helper function to build pagination URL
function buildPaginationUrl($page_num)
{
    $params = $_GET;
    $params['page'] = $page_num;
    return '?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Bahria Papers Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">

    <script src="assets/js/darkmode.js"></script> <!-- Dark Mode Logic -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.worker.min.js"></script>
</head>

<body class="dashboard-body">

    <div class="modal" id="uploadModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Upload Past Paper</h3>
                <button class="close-btn" onclick="closeUploadModal()">
                    <span class="material-icons">close</span>
                </button>
            </div>

            <form action="upload.php" method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="upload-zone" id="uploadZone">
                    <span class="material-icons upload-icon">cloud_upload</span>
                    <h4>Drag & Drop or Click to Upload</h4>
                    <p>PDF or Image files only (Max 10MB)</p>
                    <input type="file" name="paper_file" id="fileInput" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>

                <div id="selectedFile" style="display: none;">
                    <strong>Selected: </strong><span id="fileName"></span>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Campus *</label>
                        <select name="campus" id="uploadCampus" required>
                            <option value="">Select Campus</option>
                            <option value="Islamabad E-8">Islamabad E-8 Campus</option>
                            <option value="Islamabad H-11">Islamabad H-11 Campus</option>
                            <option value="Karachi">Karachi Campus</option>
                            <option value="Karachi Health Sciences">Karachi (Health Sciences)</option>
                            <option value="Lahore">Lahore Campus</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Department *</label>
                        <select name="department" id="uploadDepartment" required>
                            <option value="">Select a Campus First</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Semester *</label>
                        <select name="semester" required>
                            <option value="">Select Semester</option>
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <option value="<?php echo $i; ?>">Semester <?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Course Code *</label>
                        <input type="text" name="course_code" class="input-field" placeholder="e.g., CS-101" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Course Title *</label>
                    <input type="text" name="course_title" class="input-field"
                        placeholder="e.g., Programming Fundamentals" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Exam Type *</label>
                        <select name="exam_type" required>
                            <option value="Final">Final Exam</option>
                            <option value="Mid">Mid Term</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Teacher Name(s) *</label>
                        <input type="text" name="teacher_name" class="input-field"
                            placeholder="e.g., Dr. Ahmed, Ms. Sarah" required>
                        <small style="color: #666; font-size: 11px;">Separate multiple names with commas</small>
                    </div>
                </div>

                <div class="form-group">
                    <label>Year *</label>
                    <input type="number" name="year" class="input-field" placeholder="e.g., 2024" min="2000" max="2026"
                        required>
                </div>

                <button type="submit" class="btn btn-primary btn-full">
                    <span class="material-icons">publish</span>
                    Upload Paper
                </button>
            </form>
        </div>
    </div>

    <div class="modal" id="viewModal">
        <div class="modal-content" style="max-width: 1000px;">
            <div class="modal-header">
                <h3 id="viewModalTitle">View Paper</h3>
                <button class="close-btn" onclick="closeViewModal()">
                    <span class="material-icons">close</span>
                </button>
            </div>

            <div id="viewerContainer"
                style="background: #f0f0f0; border-radius: 12px; overflow: hidden; min-height: 600px;">

                <div id="pdfViewer"
                    style="display: none; text-align: center; overflow-y: auto; max-height: 600px; padding-top: 10px;">

                    <div id="pdfControls"
                        style="margin-bottom: 10px; display: flex; justify-content: center; align-items: center; gap: 15px;">
                        <button id="prevPage" class="btn btn-secondary btn-sm" onclick="onPrevPage()">
                            <span class="material-icons">chevron_left</span>
                            Prev
                        </button>
                        <span>
                            Page <span id="pageNum">1</span> of <span id="pageCount">0</span>
                        </span>
                        <button id="nextPage" class="btn btn-secondary btn-sm" onclick="onNextPage()">
                            Next
                            <span class="material-icons">chevron_right</span>
                        </button>
                    </div>

                    <canvas id="pdfCanvas" style="border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.2);"></canvas>

                </div>
                <div id="imageViewer" style="display: none; text-align: center; padding: 20px;">
                    <img id="imagePreview"
                        style="max-width: 100%; max-height: 600px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
                </div>

                <div id="viewerLoading"
                    style="display: flex; align-items: center; justify-content: center; height: 600px;">
                    <div style="text-align: center;">
                        <div
                            style="border: 4px solid #f3f3f3; border-top: 4px solid #1976D2; border-radius: 50%; width: 50px; height: 50px; animation: spin 1s linear infinite; margin: 0 auto;">
                        </div>
                        <p style="margin-top: 20px; color: #666;">Loading paper...</p>
                    </div>
                </div>
            </div>

            <div style="margin-top: 20px; text-align: center;">
                <button id="downloadFromView" class="btn btn-primary" onclick="openDownloadModalFromView()"
                    style="margin-right: 10px;">
                    <span class="material-icons">download</span>
                    Download Paper
                </button>
                <button id="reportFromView" class="btn btn-secondary" onclick="openReportModal()">
                    <span class="material-icons">flag</span>
                    Report Issue
                </button>
            </div>
        </div>
    </div>

    <!-- Donation/Download Confirmation Modal -->
    <div class="modal" id="donationModal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3>Download Confirmation</h3>
                <button class="close-btn" onclick="closeDonationModal()">
                    <span class="material-icons">close</span>
                </button>
            </div>
            <div class="modal-body" style="text-align: center;">
                <div style="margin-bottom: 20px;">
                    <span class="material-icons" style="font-size: 64px; color: #4ca1af;">cloud_download</span>
                </div>

                <h4 style="margin-bottom: 10px;">Your download is ready!</h4>
                <p style="color: #666; margin-bottom: 25px;">Downloading <strong><span
                            id="downloadPaperTitle"></span></strong>...</p>

                <div class="donation-box">
                    <div
                        style="display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 10px;">
                        <span class="material-icons" style="color: #ff9800;">volunteer_activism</span>
                        <h5 style="margin: 0; font-size: 16px;">Support the Portal</h5>
                    </div>
                    <p style="font-size: 14px; color: #555; margin-bottom: 15px; line-height: 1.5;">
                        This server costs money to run. If this portal helps you study, please consider a small
                        contribution.
                    </p>

                    <div class="donation-method">
                        <span class="material-icons" style="font-size: 30px; color: var(--md-primary);">payments</span>
                        <div class="donation-details">
                            <h4><?php echo htmlspecialchars(SUPPORT_PAYMENT_LABEL); ?></h4>
                            <p><?php echo htmlspecialchars(SUPPORT_PAYMENT_VALUE); ?></p>
                            <div style="font-size: 12px; color: #666; margin-top: 4px;"><?php echo htmlspecialchars(SUPPORT_PAYMENT_NOTE); ?></div>
                        </div>
                    </div>
                </div>

                <button class="btn btn-primary btn-full" onclick="proceedDownload()">
                    <span class="material-icons">download</span>
                    Download Now
                </button>
            </div>
        </div>
    </div>

    <div class="modal" id="reportModal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3>Report Paper Issue</h3>
                <button class="close-btn" onclick="closeReportModal()">
                    <span class="material-icons">close</span>
                </button>
            </div>

            <form id="reportForm" onsubmit="submitReport(event)">
                <input type="hidden" id="reportPaperId" name="paper_id">

                <div class="form-group">
                    <label>Issue Type *</label>
                    <select name="report_type" required class="input-field">
                        <option value="">Select Issue Type</option>
                        <option value="wrong_file">Wrong File/Paper</option>
                        <option value="wrong_info">Incorrect Information</option>
                        <option value="inappropriate">Inappropriate Content</option>
                        <option value="quality_issue">Poor Quality/Unreadable</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Description *</label>
                    <textarea name="description" class="input-field" rows="5"
                        placeholder="Please describe the issue in detail..." required
                        style="resize: vertical;"></textarea>
                </div>

                <div id="reportMessage" style="display: none; margin-bottom: 15px;"></div>

                <button type="submit" class="btn btn-primary btn-full" id="reportSubmitBtn">
                    <span class="material-icons">send</span>
                    Submit Report
                </button>
            </form>
        </div>
    </div>

    <div class="container">
        <div class="container">
            <?php include 'includes/header.php'; ?>

            <div class="opensource-card">
                <div class="opensource-card-content">
                    <div class="opensource-card-icon">
                        <span class="material-icons">code</span>
                    </div>
                    <div>
                        <h3>Help shape Bahria Papers Portal</h3>
                        <p>
                            This project is now open source. Contributors can pick priority issues, improve the file structure,
                            harden security, document APIs, and build features the student community needs.
                        </p>
                    </div>
                </div>
                <div class="opensource-card-actions">
                    <a href="https://github.com/junejosamad/papers" target="_blank" rel="noopener" class="btn">
                        <span class="material-icons">open_in_new</span>
                        GitHub
                    </a>
                    <a href="https://github.com/junejosamad/papers/blob/main/docs/PRIORITIES.md" target="_blank" rel="noopener" class="btn btn-secondary">
                        <span class="material-icons">task_alt</span>
                        Priority Issues
                    </a>
                </div>
            </div>

            <div class="main-content">
                <div class="filters-card">
                    <h3>
                        <span class="material-icons">filter_list</span>
                        Filters
                    </h3>

                    <form method="GET" id="filterForm">
                        <div class="filter-group">
                            <label>Campus</label>
                            <select name="campus" id="filterCampus" onchange="this.form.submit()">
                                <option value="">All Campuses</option>
                                <option value="Islamabad E-8" <?php echo $campus === 'Islamabad E-8' ? 'selected' : ''; ?>>
                                    Islamabad E-8</option>
                                <option value="Islamabad H-11" <?php echo $campus === 'Islamabad H-11' ? 'selected' : ''; ?>>
                                    Islamabad H-11</option>
                                <option value="Karachi" <?php echo $campus === 'Karachi' ? 'selected' : ''; ?>>Karachi
                                </option>
                                <option value="Karachi Health Sciences" <?php echo $campus === 'Karachi Health Sciences' ? 'selected' : ''; ?>>Karachi (Health Sci.)</option>
                                <option value="Lahore" <?php echo $campus === 'Lahore' ? 'selected' : ''; ?>>Lahore
                                </option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>Department</label>
                            <select name="dept" id="filterDept" onchange="this.form.submit()">
                                <option value="">All Departments</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>Semester</label>
                            <select name="semester" onchange="this.form.submit()">
                                <option value="">All Semesters</option>
                                <?php for ($i = 1; $i <= 8; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $semester == $i ? 'selected' : ''; ?>>
                                        Semester <?php echo $i; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>Course</label>
                            <input type="text" name="course" class="input-field" placeholder="Enter course name"
                                value="<?php echo htmlspecialchars($course); ?>">
                        </div>

                        <div class="filter-group">
                            <label>Teacher</label>
                            <input type="text" name="teacher" class="input-field" placeholder="Enter teacher name"
                                value="<?php echo htmlspecialchars($teacher); ?>">
                        </div>

                        <button type="submit" class="btn btn-primary btn-full">Apply Filters</button>
                        <a href="dashboard.php" class="btn btn-secondary btn-full" style="margin-top: 8px;">Clear
                            Filters</a>
                    </form>
                </div>

                <div class="papers-section">
                    <div class="section-header">
                        <h2>Explore Past Papers</h2>
                        <div class="search-bar">
                            <form method="GET">
                                <?php
                                // Preserve filters in search
                                foreach (['campus', 'dept', 'semester', 'course', 'teacher'] as $filter) {
                                    if (!empty($_GET[$filter])) {
                                        echo '<input type="hidden" name="' . $filter . '" value="' . htmlspecialchars($_GET[$filter]) . '">';
                                    }
                                }
                                ?>
                                <input type="text" name="search" class="search-input" placeholder="Search papers..."
                                    value="<?php echo htmlspecialchars($search); ?>">
                            </form>
                        </div>
                    </div>

                    <?php if ($total_papers > 0): ?>
                        <div style="margin-bottom: 16px; color: #666; font-size: 14px;">
                            Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $papers_per_page, $total_papers); ?>
                            of
                            <?php echo $total_papers; ?> papers
                        </div>
                    <?php endif; ?>

                    <div class="papers-grid">
                        <?php if (empty($papers)): ?>
                            <p class="no-results">No papers found matching your criteria.</p>
                        <?php else: ?>
                            <?php foreach ($papers as $paper): ?>
                                <div class="paper-card">
                                    <div class="paper-icon">
                                        <span class="material-icons">
                                            <?php echo $paper['file_type'] === 'pdf' ? 'picture_as_pdf' : 'image'; ?>
                                        </span>
                                    </div>
                                    <button
                                        class="btn-icon favorite-btn <?php echo in_array($paper['id'], $favorites) ? 'active' : ''; ?>"
                                        onclick="toggleFavorite(<?php echo $paper['id']; ?>, this)"
                                        title="<?php echo in_array($paper['id'], $favorites) ? 'Remove from Backpack' : 'Add to Backpack'; ?>">
                                        <span
                                            class="material-icons"><?php echo in_array($paper['id'], $favorites) ? 'favorite' : 'favorite_border'; ?></span>
                                    </button>
                                    <div class="paper-title">
                                        <?php echo htmlspecialchars($paper['title'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <div class="paper-meta">
                                        <span><strong>Course:</strong>
                                            <?php echo htmlspecialchars($paper['course_code'], ENT_QUOTES, 'UTF-8'); ?> -
                                            <?php echo htmlspecialchars($paper['course_title'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <span><strong>Teacher:</strong>
                                            <?php echo htmlspecialchars($paper['teacher_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <span><strong>Campus:</strong>
                                            <?php echo htmlspecialchars($paper['campus'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <span><strong>Semester:</strong> <?php echo $paper['semester']; ?> | Year:
                                            <?php echo $paper['year']; ?></span>
                                        <span><strong>Views:</strong> <?php echo $paper['views']; ?> |
                                            <strong>Downloads:</strong>
                                            <?php echo $paper['downloads']; ?></span>
                                    </div>
                                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                        <div class="chip"><?php echo htmlspecialchars($paper['department']); ?></div>
                                        <?php
                                        $examType = $paper['exam_type'];
                                        $style = '';

                                        if (empty($examType)) {
                                            $titleLower = strtolower($paper['title']);
                                            if (preg_match('/mid(s?)/', $titleLower)) {
                                                $examType = 'Mid';
                                            } elseif (preg_match('/final(s?)/', $titleLower)) {
                                                $examType = 'Final';
                                            } else {
                                                $examType = 'Unspecified';
                                            }
                                        }

                                        // Set styles based on determined type
                                        if ($examType === 'Mid') {
                                            $style = 'background: #FFF3E0; color: #E65100'; // Orange for Mid
                                        } elseif ($examType === 'Final') {
                                            $style = 'background: #E3F2FD; color: #1565C0'; // Blue for Final
                                        } else {
                                            $style = 'background: #F5F5F5; color: #616161'; // Grey for Unspecified
                                        }
                                        ?>
                                                <div class="chip" style="<?php echo $style; ?>">
                                            <?php echo htmlspecialchars($examType); ?>
                                        </div>
                                    </div>
                                    <div style="display: flex; gap: 8px; margin-top: 12px;">
                                        <button class="btn btn-primary btn-sm"
                                            onclick="viewPaper(<?php echo $paper['id']; ?>, '<?php echo htmlspecialchars($paper['title'], ENT_QUOTES); ?>', '<?php echo $paper['file_type']; ?>')">
                                            <span class="material-icons">visibility</span>
                                            View
                                        </button>
                                        <button class="btn btn-secondary btn-sm"
                                            onclick="openDownloadModal(<?php echo $paper['id']; ?>, '<?php echo htmlspecialchars($paper['title'], ENT_QUOTES); ?>')">
                                            <span class="material-icons">download</span>
                                            Download
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="<?php echo buildPaginationUrl(1); ?>" class="pagination-btn">
                                    <span class="material-icons">first_page</span>
                                </a>
                                <a href="<?php echo buildPaginationUrl($page - 1); ?>" class="pagination-btn">
                                    <span class="material-icons">chevron_left</span>
                                </a>
                            <?php endif; ?>

                            <?php
                            // Show page numbers
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);

                            if ($start_page > 1) {
                                echo '<a href="' . buildPaginationUrl(1) . '" class="pagination-btn">1</a>';
                                if ($start_page > 2) {
                                    echo '<span class="pagination-ellipsis">...</span>';
                                }
                            }

                            for ($i = $start_page; $i <= $end_page; $i++) {
                                $active_class = ($i == $page) ? ' active' : '';
                                echo '<a href="' . buildPaginationUrl($i) . '" class="pagination-btn' . $active_class . '">' . $i . '</a>';
                            }

                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) {
                                    echo '<span class="pagination-ellipsis">...</span>';
                                }
                                echo '<a href="' . buildPaginationUrl($total_pages) . '" class="pagination-btn">' . $total_pages . '</a>';
                            }
                            ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="<?php echo buildPaginationUrl($page + 1); ?>" class="pagination-btn">
                                    <span class="material-icons">chevron_right</span>
                                </a>
                                <a href="<?php echo buildPaginationUrl($total_pages); ?>" class="pagination-btn">
                                    <span class="material-icons">last_page</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Donation Footer -->
                <div class="donation-footer">
                    <div style="max-width: 600px; margin: 0 auto;">
                        <span class="material-icons" style="font-size: 32px; margin-bottom: 10px;">favorite</span>
                        <h3>Keep this portal alive!</h3>
                        <p>We are a small student-run team. Running this server costs money. If you find this resource
                            helpful during your exams, a small contribution (even PKR 100) helps us keep the lights on.
                        </p>

                        <div
                            style="background: rgba(255,255,255,0.1); padding: 15px; border-radius: 10px; margin-top: 15px; display: inline-block; text-align: left;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span class="material-icons" style="font-size: 24px;">payments</span>
                                <div>
                                    <strong style="font-size: 16px;"><?php echo htmlspecialchars(SUPPORT_PAYMENT_VALUE); ?></strong>
                                    <div style="font-size: 12px; opacity: 0.8;"><?php echo htmlspecialchars(SUPPORT_PAYMENT_NOTE); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
            @keyframes spin {
                0% {
                    transform: rotate(0deg);
                }

                100% {
                    transform: rotate(360deg);
                }
            }

            /* Pagination Styles */
            .pagination {
                display: flex;
                justify-content: center;
                align-items: center;
                gap: 8px;
                margin-top: 32px;
                padding: 20px 0;
            }

            .pagination-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 40px;
                height: 40px;
                padding: 0 12px;
                border: 1px solid #e0e0e0;
                border-radius: 8px;
                background: white;
                color: #333;
                text-decoration: none;
                font-weight: 500;
                transition: all 0.2s ease;
            }

            .pagination-btn:hover {
                background: #f5f5f5;
                border-color: #1976D2;
                color: #1976D2;
            }

            .pagination-btn.active {
                background: #1976D2;
                color: white;
                border-color: #1976D2;
            }

            .pagination-btn .material-icons {
                font-size: 20px;
            }

            .pagination-ellipsis {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 40px;
                height: 40px;
                color: #999;
            }
        </style>

        <script>
            // Global variable to track currently viewing paper
            let currentViewingPaperId = null;
            let currentDownloadId = null;

            // --- NEW PDF.js Global Variables ---
            let pdfDoc = null;
            let pageNum = 1;
            let pageRendering = false;
            let pageNumPending = null;
            const scale = 1.5; // You can adjust this scale
            let canvas = null;
            let ctx = null;

            // Set the worker source for PDF.js
            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.worker.min.js';

            // Campus and Department Data
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

            // --- NEW PDF.js Function: Load and render a PDF ---
            function loadPdf(url) {
                if (!canvas) {
                    canvas = document.getElementById('pdfCanvas');
                    ctx = canvas.getContext('2d');
                }

                // Asynchronously download PDF
                pdfjsLib.getDocument(url).promise.then(function (pdfDoc_) {
                    pdfDoc = pdfDoc_;
                    document.getElementById('pageCount').textContent = pdfDoc.numPages;

                    // Initial page render
                    pageNum = 1;
                    renderPage(pageNum);
                }).catch(err => {
                    console.error("Error loading PDF: ", err);
                    document.getElementById('viewerLoading').innerHTML = '<p style="color: red;">Error: Could not load paper.</p>';
                });
            }

            // --- NEW PDF.js Function: Render a specific page ---
            function renderPage(num) {
                pageRendering = true;
                document.getElementById('viewerLoading').style.display = 'flex'; // Show loader while rendering

                // Using promise to fetch the page
                pdfDoc.getPage(num).then(function (page) {
                    const viewport = page.getViewport({ scale: scale });

                    // Adjust canvas size to fit the container width
                    const container = document.getElementById('pdfViewer');
                    // Ensure container width is positive, fallback to a default
                    let containerWidth = container.clientWidth > 0 ? container.clientWidth : 960;
                    const desiredWidth = containerWidth - 20; // 20px padding
                    const scaledViewport = page.getViewport({ scale: desiredWidth / viewport.width });

                    canvas.height = scaledViewport.height;
                    canvas.width = scaledViewport.width;

                    // Render PDF page into canvas context
                    const renderContext = {
                        canvasContext: ctx,
                        viewport: scaledViewport
                    };

                    const renderTask = page.render(renderContext);

                    // Wait for rendering to finish
                    renderTask.promise.then(function () {
                        pageRendering = false;
                        document.getElementById('viewerLoading').style.display = 'none'; // Hide loader

                        if (pageNumPending !== null) {
                            // New page waiting to be rendered
                            renderPage(pageNumPending);
                            pageNumPending = null;
                        }
                    });
                });

                // Update page counters
                document.getElementById('pageNum').textContent = num;

                // Update button states
                document.getElementById('prevPage').disabled = (num <= 1);
                document.getElementById('nextPage').disabled = (num >= pdfDoc.numPages);
            }

            // --- NEW PDF.js Function: Queue a page render ---
            function queueRenderPage(num) {
                if (pageRendering) {
                    pageNumPending = num;
                } else {
                    renderPage(num);
                }
            }

            // --- NEW PDF.js Function: Go to previous page ---
            function onPrevPage() {
                if (pageNum <= 1) {
                    return;
                }
                pageNum--;
                queueRenderPage(pageNum);
            }

            // --- NEW PDF.js Function: Go to next page ---
            function onNextPage() {
                if (pageNum >= pdfDoc.numPages) {
                    return;
                }
                pageNum++;
                queueRenderPage(pageNum);
            }


            // Upload Modal Functions
            function openUploadModal() {
                document.getElementById('uploadModal').classList.add('active');
            }

            function closeUploadModal() {
                document.getElementById('uploadModal').classList.remove('active');
            }

            // --- MODIFIED View Paper Modal Function ---
            function viewPaper(paperId, title, fileType) {
                currentViewingPaperId = paperId;
                const modal = document.getElementById('viewModal');
                const modalTitle = document.getElementById('viewModalTitle');
                const pdfViewer = document.getElementById('pdfViewer');
                const imageViewer = document.getElementById('imageViewer');
                const loading = document.getElementById('viewerLoading');
                const downloadLink = document.getElementById('downloadFromView');

                // Set title
                modalTitle.textContent = title;

                // Set download behavior
                // downloadLink.href = 'download.php?id=' + paperId; // OLD Direct Link
                // Remove href, handle click in button

                // Show loading
                loading.style.display = 'flex';
                pdfViewer.style.display = 'none';
                imageViewer.style.display = 'none';

                // Show modal
                modal.classList.add('active');

                // Load content based on file type
                if (fileType === 'pdf') {
                    pdfViewer.style.display = 'block'; // Show our PDF.js container
                    loadPdf('view.php?id=' + paperId); // Call our new function
                } else {
                    const imagePreview = document.getElementById('imagePreview');
                    imagePreview.src = 'view.php?id=' + paperId;
                    imagePreview.onload = function () {
                        loading.style.display = 'none';
                        imageViewer.style.display = 'block';
                    };
                }
            }

            // --- MODIFIED Close View Modal Function ---
            function closeViewModal() {
                const modal = document.getElementById('viewModal');
                modal.classList.remove('active');

                // Clear image viewer
                document.getElementById('imagePreview').src = '';

                // --- NEW: Clean up PDF.js ---
                if (pdfDoc) {
                    pdfDoc.destroy();
                    pdfDoc = null;
                }
                // Clear canvas
                if (ctx) {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                }

                // Reset loading text in case of error
                const loadingDiv = document.getElementById('viewerLoading');
                if (!loadingDiv.querySelector('.material-icons')) {
                    loadingDiv.innerHTML = `
                    <div style="text-align: center;">
                        <div style="border: 4px solid #f3f3f3; border-top: 4px solid #1976D2; border-radius: 50%; width: 50px; height: 50px; animation: spin 1s linear infinite; margin: 0 auto;"></div>
                        <p style="margin-top: 20px; color: #666;">Loading paper...</p>
                    </div>`;
                }
            }

            // Report Modal Functions
            function openReportModal() {
                if (!currentViewingPaperId) return;

                document.getElementById('reportPaperId').value = currentViewingPaperId;
                document.getElementById('reportModal').classList.add('active');
                document.getElementById('reportForm').reset();
                document.getElementById('reportMessage').style.display = 'none';
            }

            function closeReportModal() {
                document.getElementById('reportModal').classList.remove('active');
            }

            function submitReport(e) {
                e.preventDefault();

                const form = e.target;
                const submitBtn = document.getElementById('reportSubmitBtn');
                const messageDiv = document.getElementById('reportMessage');

                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="material-icons">hourglass_empty</span> Submitting...';

                const formData = new FormData(form);

                fetch('report.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        messageDiv.style.display = 'block';

                        if (data.success) {
                            messageDiv.className = 'alert alert-success';
                            messageDiv.innerHTML = '<span class="material-icons">check_circle</span> ' + data.message;

                            setTimeout(() => {
                                closeReportModal();
                                messageDiv.style.display = 'none';
                            }, 2000);
                        } else {
                            messageDiv.className = 'alert alert-error';
                            messageDiv.innerHTML = '<span class="material-icons">error</span> ' + data.message;
                        }

                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<span class="material-icons">send</span> Submit Report';
                    })
                    .catch(error => {
                        messageDiv.style.display = 'block';
                        messageDiv.className = 'alert alert-error';
                        messageDiv.innerHTML = '<span class="material-icons">error</span> Failed to submit report. Please try again.';

                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<span class="material-icons">send</span> Submit Report';
                    });
            }

            // Function to update department dropdown based on selected campus
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
                }
            }

            // File upload handling
            document.addEventListener('DOMContentLoaded', function () {
                // Setup for Upload Modal
                const uploadCampus = document.getElementById('uploadCampus');
                const uploadDepartment = document.getElementById('uploadDepartment');
                uploadCampus.addEventListener('change', () => {
                    updateDepartments(uploadCampus, uploadDepartment, 'Select Department');
                });

                // Setup for Filter Sidebar
                const filterCampus = document.getElementById('filterCampus');
                const filterDept = document.getElementById('filterDept');
                const currentDept = '<?php echo $dept; ?>'; // Get current department filter from PHP

                // Populate departments on page load based on current filter
                updateDepartments(filterCampus, filterDept, 'All Departments', currentDept);

                const uploadZone = document.getElementById('uploadZone');
                const fileInput = document.getElementById('fileInput');

                uploadZone.addEventListener('click', () => fileInput.click());

                uploadZone.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    uploadZone.classList.add('drag-over');
                });

                uploadZone.addEventListener('dragleave', () => {
                    uploadZone.classList.remove('drag-over');
                });

                uploadZone.addEventListener('drop', (e) => {
                    e.preventDefault();
                    uploadZone.classList.remove('drag-over');
                    if (e.dataTransfer.files.length > 0) {
                        fileInput.files = e.dataTransfer.files;
                        displaySelectedFile(e.dataTransfer.files[0]);
                    }
                });

                fileInput.addEventListener('change', (e) => {
                    if (e.target.files.length > 0) {
                        displaySelectedFile(e.target.files[0]);
                    }
                });

                function displaySelectedFile(file) {
                    document.getElementById('selectedFile').style.display = 'block';
                    document.getElementById('fileName').textContent = file.name;
                }

                // Close modals when clicking outside
                document.getElementById('uploadModal').addEventListener('click', function (e) {
                    if (e.target === this) {
                        closeUploadModal();
                    }
                });

                document.getElementById('viewModal').addEventListener('click', function (e) {
                    if (e.target === this) {
                        closeViewModal();
                    }
                });

                document.getElementById('reportModal').addEventListener('click', function (e) {
                    if (e.target === this) {
                        closeReportModal();
                    }
                });

                document.getElementById('donationModal').addEventListener('click', function (e) {
                    if (e.target === this) {
                        closeDonationModal();
                    }
                });
            });

            // Donation Modal Functions
            function openDownloadModal(id, title) {
                currentDownloadId = id;
                document.getElementById('downloadPaperTitle').textContent = title;
                document.getElementById('donationModal').classList.add('active');
            }

            function openDownloadModalFromView() {
                // Use the current viewing paper details
                if (currentViewingPaperId) {
                    const title = document.getElementById('viewModalTitle').textContent;
                    openDownloadModal(currentViewingPaperId, title);
                }
            }

            function closeDonationModal() {
                document.getElementById('donationModal').classList.remove('active');
            }

            function proceedDownload() {
                if (currentDownloadId) {
                    window.location.href = 'download.php?id=' + currentDownloadId;
                    closeDonationModal();
                }
            }

            // Favorites Logic
            function toggleFavorite(paperId, btn) {
                fetch('api/toggle_favorite.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ paper_id: paperId })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const icon = btn.querySelector('.material-icons');
                            if (data.action === 'added') {
                                btn.classList.add('active');
                                icon.textContent = 'favorite';
                                btn.title = 'Remove from Backpack';
                            } else {
                                btn.classList.remove('active');
                                icon.textContent = 'favorite_border';
                                btn.title = 'Add to Backpack';
                            }
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            }
        </script>

</body>

</html>
