<?php
require_once 'config.php';
requireLogin();

// Get user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$current_user = $stmt->fetch();

// Get user favorites IDs for the UI state
$stmt = $pdo->prepare("SELECT paper_id FROM favorites WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$favorites = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Build query to fetch ONLY favorite papers
$query = "SELECT p.*, u.enrollment_number as uploader
          FROM papers p 
          JOIN users u ON p.user_id = u.id 
          JOIN favorites f ON p.id = f.paper_id
          WHERE f.user_id = ?
          ORDER BY f.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$papers = $stmt->fetchAll();

$total_papers = count($papers);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Backpack - Bahria Papers Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/darkmode.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.worker.min.js"></script>
</head>

<body class="dashboard-body">

    <!-- Include Upload Modal Logic (Reuse or Duplicate - Duplicating for simplicity for now) -->
    <!-- Minimal Header -->
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
            <a href="profile.php" class="user-badge" style="cursor: pointer; text-decoration: none;">
                <?php if ($current_user['profile_image']): ?>
                    <img src="uploads/<?php echo htmlspecialchars($current_user['profile_image']); ?>" alt="Profile"
                        style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; margin-right: 8px; vertical-align: middle;">
                <?php else: ?>
                    <span class="material-icons"
                        style="vertical-align: middle; margin-right: 4px; font-size: 20px;">person</span>
                <?php endif; ?>
                <?php echo htmlspecialchars($current_user['full_name'] ?: $current_user['enrollment_number']); ?>
            </a>
            <a href="logout.php" class="btn btn-secondary">
                <span class="material-icons">logout</span>
                Logout
            </a>
        </div>
    </div>

    <div class="container">
        <div class="main-content" style="grid-template-columns: 1fr;"> <!-- One column layout -->
            <div class="papers-section">
                <div class="section-header">
                    <h2>
                        <span class="material-icons"
                            style="color: #E91E63; vertical-align: middle; margin-right: 10px;">backpack</span>
                        My Backpack
                    </h2>
                </div>

                <?php if (empty($papers)): ?>
                    <div style="text-align: center; padding: 60px; color: #666;">
                        <span class="material-icons"
                            style="font-size: 64px; opacity: 0.5; margin-bottom: 20px;">backpack</span>
                        <h3>Your Backpack is empty</h3>
                        <p>Save important papers here for quick access during exams.</p>
                        <a href="dashboard.php" class="btn btn-primary"
                            style="margin-top: 20px; display: inline-flex; align-items: center; gap: 8px;">
                            <span class="material-icons">search</span>
                            Browse Papers
                        </a>
                    </div>
                <?php else: ?>
                    <div class="papers-grid">
                        <?php foreach ($papers as $paper): ?>
                            <div class="paper-card">
                                <div class="paper-icon">
                                    <span class="material-icons">
                                        <?php echo $paper['file_type'] === 'pdf' ? 'picture_as_pdf' : 'image'; ?>
                                    </span>
                                </div>
                                <button class="btn-icon favorite-btn active"
                                    onclick="removeFromBackpack(<?php echo $paper['id']; ?>, this)"
                                    title="Remove from Backpack">
                                    <span class="material-icons">favorite</span>
                                </button>
                                <div class="paper-title"><?php echo htmlspecialchars($paper['title']); ?></div>
                                <div class="paper-meta">
                                    <span><strong>Course:</strong> <?php echo htmlspecialchars($paper['course_code']); ?></span>
                                    <span><strong>Teacher:</strong>
                                        <?php echo htmlspecialchars($paper['teacher_name']); ?></span>
                                    <span><strong>Semester:</strong> <?php echo $paper['semester']; ?></span>
                                </div>

                                <div style="display: flex; gap: 8px; margin-top: 12px;">
                                    <button class="btn btn-primary btn-sm"
                                        onclick="window.location.href='download.php?id=<?php echo $paper['id']; ?>'">
                                        <span class="material-icons">download</span>
                                        Download
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function removeFromBackpack(paperId, btn) {
            if (!confirm('Remove this paper from your backpack?')) return;

            fetch('api/toggle_favorite.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ paper_id: paperId })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.action === 'removed') {
                        // Remove card from DOM
                        const card = btn.closest('.paper-card');
                        card.style.opacity = '0';
                        setTimeout(() => {
                            card.remove();
                            // If empty, reload to show empty state
                            if (document.querySelectorAll('.paper-card').length === 0) {
                                location.reload();
                            }
                        }, 300);
                    }
                });
        }
    </script>
</body>

</html>