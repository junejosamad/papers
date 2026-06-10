<?php

require_once 'config.php';
requireLogin();

$success = '';
$error = '';

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'posted') {
        $success = "Request posted successfully! The community has been notified.";
    } elseif ($_GET['msg'] === 'closed') {
        $success = "Request closed successfully.";
    } elseif ($_GET['msg'] === 'commented') {
        $success = "Comment added successfully.";
    }
}

// Handle New Request Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_request'])) {
        $campus = sanitize($_POST['campus']);
        $course_code = sanitize($_POST['course_code']);
        $message = sanitize($_POST['message']);

        if ($campus && $course_code) {
            $stmt = $pdo->prepare("INSERT INTO paper_requests (user_id, campus, course_code, message) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$_SESSION['user_id'], $campus, $course_code, $message])) {
                // Redirect to avoid resubmission
                header("Location: requests.php?msg=posted");
                exit;
            } else {
                $error = "Failed to post request.";
            }
        } else {
            $error = "Please fill in all required fields.";
        }
    }

    // Handle Vote
    if (isset($_POST['toggle_vote'])) {
        $request_id = (int) $_POST['request_id'];

        // Check if already voted
        $stmt = $pdo->prepare("SELECT id FROM request_votes WHERE request_id = ? AND user_id = ?");
        $stmt->execute([$request_id, $_SESSION['user_id']]);

        if ($stmt->fetch()) {
            // Remove vote
            $pdo->prepare("DELETE FROM request_votes WHERE request_id = ? AND user_id = ?")->execute([$request_id, $_SESSION['user_id']]);
            $pdo->prepare("UPDATE paper_requests SET vote_count = vote_count - 1 WHERE id = ?")->execute([$request_id]);
        } else {
            // Add vote
            $pdo->prepare("INSERT INTO request_votes (request_id, user_id) VALUES (?, ?)")->execute([$request_id, $_SESSION['user_id']]);
            $pdo->prepare("UPDATE paper_requests SET vote_count = vote_count + 1 WHERE id = ?")->execute([$request_id]);

            // NOTIFICATION TRIGGER: VOTE
            $stmt = $pdo->prepare("SELECT user_id, course_code FROM paper_requests WHERE id = ?");
            $stmt->execute([$request_id]);
            $req = $stmt->fetch();
            if ($req && $req['user_id'] != $_SESSION['user_id']) {
                $msg = $current_user['full_name'] . " voted on your request for " . $req['course_code'];
                $link = "requests.php?search=" . urlencode($req['course_code']);
                $pdo->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'vote', ?, ?)")
                    ->execute([$req['user_id'], $msg, $link]);
            }
        }
        exit; // AJAX response handled by frontend reload or UI update usually, but here request is just POST
    }

    // Handle Comment
    if (isset($_POST['submit_comment'])) {
        $request_id = (int) $_POST['request_id'];
        $comment = sanitize($_POST['comment']);

        if ($comment && $request_id) {
            $stmt = $pdo->prepare("INSERT INTO request_comments (request_id, user_id, message) VALUES (?, ?, ?)");
            $stmt->execute([$request_id, $_SESSION['user_id'], $comment]);

            // NOTIFICATION TRIGGER: COMMENT
            $stmt = $pdo->prepare("SELECT user_id, course_code FROM paper_requests WHERE id = ?");
            $stmt->execute([$request_id]);
            $req = $stmt->fetch();
            if ($req && $req['user_id'] != $_SESSION['user_id']) {
                $msg = $current_user['full_name'] . " commented on your request for " . $req['course_code'];
                $link = "requests.php?search=" . urlencode($req['course_code']);
                $pdo->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'comment', ?, ?)")
                    ->execute([$req['user_id'], $msg, $link]);
            }
        }
        // Redirect to avoid resubmission
        header("Location: requests.php?msg=commented");
        exit;
    }

    // Handle Close Request
    if (isset($_POST['close_request'])) {
        $request_id = (int) $_POST['request_id'];

        // Verify ownership
        $stmt = $pdo->prepare("SELECT user_id FROM paper_requests WHERE id = ?");
        $stmt->execute([$request_id]);
        $req = $stmt->fetch();

        if ($req) {
            $is_owner = ($req['user_id'] == $_SESSION['user_id']);

            if ($is_owner) {
                $status = 'Closed';
                $stmt = $pdo->prepare("UPDATE paper_requests SET status = ? WHERE id = ?");
                $stmt->execute([$status, $request_id]);

                header("Location: requests.php?msg=closed");
                exit;
            }
        }
    }
}

// Handle Filters
$search = $_GET['search'] ?? '';
$filter_campus = $_GET['campus'] ?? '';
$filter_status = $_GET['status'] ?? 'Open'; // Default to Open

// Build Query
$query = "
    SELECT r.*, u.full_name, u.enrollment_number,
    (SELECT COUNT(*) FROM request_votes v WHERE v.request_id = r.id AND v.user_id = " . $_SESSION['user_id'] . ") as has_voted
    FROM paper_requests r 
    JOIN users u ON r.user_id = u.id 
    WHERE 1=1
";
$params = [];

if ($search) {
    $query .= " AND (r.course_code LIKE ? OR r.message LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter_campus) {
    $query .= " AND r.campus = ?";
    $params[] = $filter_campus;
}

if ($filter_status !== 'All') {
    $query .= " AND r.status = ?";
    $params[] = $filter_status;
}

// Join Comments (Separate query often cleaner, but for now simple fetching)
$query .= " ORDER BY r.vote_count DESC, r.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$requests = $stmt->fetchAll();

// Get user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$current_user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Board - Bahria Papers Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/darkmode.js"></script>
    <style>
        .request-card {
            background: var(--md-surface-variant);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 5px solid var(--md-primary);
            position: relative;
        }

        .status-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-Open {
            background: #E3F2FD;
            color: #1976D2;
        }

        .status-In-Progress {
            background: #FFF3E0;
            color: #F57C00;
        }

        .status-Fulfilled {
            background: #E8F5E9;
            color: #2E7D32;
        }

        .status-Closed {
            background: #FFEBEE;
            color: #C62828;
        }

        .request-meta {
            font-size: 13px;
            color: #666;
            margin-bottom: 8px;
        }

        .request-course {
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 5px;
        }

        .request-msg {
            font-style: italic;
            color: #444;
            background: rgba(255, 255, 255, 0.5);
            padding: 10px;
            border-radius: 8px;
            margin: 10px 0;
        }

        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            padding-top: 10px;
        }

        .vote-btn {
            background: none;
            border: 1px solid #ddd;
            border-radius: 20px;
            padding: 5px 12px;
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .vote-btn.voted {
            background: #E3F2FD;
            border-color: #1976D2;
            color: #1976D2;
        }

        .comments-section {
            margin-top: 15px;
            background: rgba(0, 0, 0, 0.02);
            padding: 10px;
            border-radius: 8px;
            display: none;
        }

        .comment {
            font-size: 13px;
            padding: 8px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .search-bar {
            background: white;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            flex-wrap: wrap;
        }

        .search-input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            min-width: 200px;
        }
    </style>
</head>

<body class="dashboard-body">

    <div class="container">
        <?php include 'includes/header.php'; ?>

        <!-- Custom sub-header for requests -->
        <div class="header" style="margin-top: 20px; box-shadow: none; background: transparent; padding: 0;">
            <div class="user-info" style="justify-content: flex-end; width: 100%;">
                <button class="btn btn-primary" onclick="openRequestModal()">
                    <span class="material-icons">add_circle</span>
                    New Request
                </button>
            </div>
        </div>

        <div class="container">

            <?php if ($success): ?>
                <div class="alert alert-success" style="margin-bottom: 20px;">
                    <span class="material-icons">check_circle</span>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <div class="section-header">
                <h2>
                    <span class="material-icons"
                        style="color: #FF9800; vertical-align: middle; margin-right: 10px;">campaign</span>
                    Community Request Board
                </h2>
                <p style="color: #666;">Can't find a paper? Ask the community!</p>
            </div>

            <!-- Search & Filter -->
            <form method="GET" class="search-bar">
                <input type="text" name="search" class="search-input" placeholder="Search by course code or message..."
                    value="<?php echo htmlspecialchars($search); ?>">
                <select name="campus" class="search-input" style="flex: 0 0 150px;">
                    <option value="">All Campuses</option>
                    <option value="Islamabad E-8" <?php echo $filter_campus == 'Islamabad E-8' ? 'selected' : ''; ?>>
                        Islamabad
                        E-8</option>
                    <option value="Islamabad H-11" <?php echo $filter_campus == 'Islamabad H-11' ? 'selected' : ''; ?>>
                        Islamabad H-11</option>
                    <option value="Karachi" <?php echo $filter_campus == 'Karachi' ? 'selected' : ''; ?>>Karachi</option>
                    <option value="Lahore" <?php echo $filter_campus == 'Lahore' ? 'selected' : ''; ?>>Lahore</option>
                </select>
                <select name="status" class="search-input" style="flex: 0 0 150px;">
                    <option value="Open" <?php echo $filter_status == 'Open' ? 'selected' : ''; ?>>Open</option>
                    <option value="Fulfilled" <?php echo $filter_status == 'Fulfilled' ? 'selected' : ''; ?>>Fulfilled
                    </option>
                    <option value="All" <?php echo $filter_status == 'All' ? 'selected' : ''; ?>>All Status</option>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
            </form>

            <?php if (empty($requests)): ?>
                <div style="text-align: center; padding: 40px;">
                    <p>No requests found for your search.</p>
                </div>
            <?php else: ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px;">
                    <?php foreach ($requests as $req): ?>
                        <div class="request-card">
                            <span class="status-badge status-<?php echo str_replace(' ', '-', $req['status']); ?>">
                                <?php echo $req['status']; ?>
                            </span>

                            <div class="request-meta">
                                <span class="material-icons" style="font-size: 14px; vertical-align: middle;">person</span>
                                <?php echo htmlspecialchars($req['full_name'] ?: $req['enrollment_number']); ?> •
                                <?php echo date('M j, Y', strtotime($req['created_at'])); ?>
                            </div>

                            <div class="request-course">
                                <?php echo htmlspecialchars($req['course_code']); ?>
                                <span class="chip"
                                    style="font-size: 11px; padding: 2px 8px;"><?php echo htmlspecialchars($req['campus']); ?></span>
                            </div>

                            <?php if ($req['message']): ?>
                                <div class="request-msg">"<?php echo htmlspecialchars($req['message']); ?>"</div>
                            <?php endif; ?>

                            <!-- Action Bar -->
                            <div class="action-bar">
                                <button class="vote-btn <?php echo $req['has_voted'] ? 'voted' : ''; ?>"
                                    onclick="toggleVote(<?php echo $req['id']; ?>, this)">
                                    <span class="material-icons" style="font-size: 16px;">thumb_up</span>
                                    <span class="count"><?php echo $req['vote_count']; ?></span>
                                </button>

                                <button class="btn btn-sm btn-secondary" onclick="toggleComments(<?php echo $req['id']; ?>)">
                                    <span class="material-icons" style="font-size: 16px;">comment</span>
                                    Discuss
                                </button>

                                <a href="dashboard.php?upload_for=<?php echo urlencode($req['course_code']); ?>"
                                    class="btn btn-primary btn-sm">
                                    <span class="material-icons" style="font-size: 16px;">upload_file</span>
                                    Upload
                                </a>

                                <?php
                                $can_close = ($req['user_id'] == $_SESSION['user_id']);
                                $status_clean = trim(strtolower($req['status']));
                                $is_closed = ($status_clean === 'closed');

                                if ($can_close && !$is_closed):
                                    ?>
                                    <button class="btn btn-sm btn-danger" onclick="closeRequest(<?php echo $req['id']; ?>)"
                                        title="Close this request (Status: <?php echo htmlspecialchars($req['status']); ?>)">
                                        <span class="material-icons" style="font-size: 16px;">close</span>
                                    </button>
                                <?php endif; ?>
                            </div>

                            <!-- Comments Section -->
                            <div id="comments-<?php echo $req['id']; ?>" class="comments-section">
                                <?php
                                // Fetch comments for this request
                                $c_stmt = $pdo->prepare("
                                SELECT c.*, u.full_name 
                                FROM request_comments c 
                                JOIN users u ON c.user_id = u.id 
                                WHERE c.request_id = ? 
                                ORDER BY c.created_at ASC
                            ");
                                $c_stmt->execute([$req['id']]);
                                $comments = $c_stmt->fetchAll();

                                if (empty($comments)) {
                                    echo '<div style="font-style: italic; color: #999; text-align: center; padding: 10px;">No comments yet.</div>';
                                }

                                foreach ($comments as $comment):
                                    ?>
                                    <div class="comment">
                                        <strong><?php echo htmlspecialchars($comment['full_name']); ?>:</strong>
                                        <?php echo htmlspecialchars($comment['message']); ?>
                                    </div>
                                <?php endforeach; ?>

                                <form method="POST" style="margin-top: 10px; display: flex; gap: 5px;">
                                    <input type="hidden" name="submit_comment" value="1">
                                    <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                    <input type="text" name="comment" placeholder="Add a comment..." required
                                        style="flex:1; padding: 5px; border-radius: 4px; border: 1px solid #ddd;">
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <span class="material-icons" style="font-size: 16px;">send</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>

        <!-- Request Modal -->
        <div class="modal" id="requestModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Submit a Request</h3>
                    <button class="close-btn" onclick="closeRequestModal()">
                        <span class="material-icons">close</span>
                    </button>
                </div>

                <form method="POST">
                    <input type="hidden" name="submit_request" value="1">
                    <div class="form-group">
                        <label>Campus *</label>
                        <select name="campus" required class="input-field">
                            <option value="">Select Campus</option>
                            <option value="Islamabad E-8">Islamabad E-8</option>
                            <option value="Islamabad H-11">Islamabad H-11</option>
                            <option value="Karachi">Karachi</option>
                            <option value="Lahore">Lahore</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Course Name/Code *</label>
                        <input type="text" name="course_code" class="input-field"
                            placeholder="e.g. CS-101 Data Structures" required>
                    </div>
                    <div class="form-group">
                        <label>Message (Optional)</label>
                        <textarea name="message" class="input-field" rows="3"
                            placeholder="Specific year? Mid/Final?"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-full">
                        <span class="material-icons">send</span>
                        Post Request
                    </button>
                </form>
            </div>
        </div>

        <script>
            const requestModal = document.getElementById('requestModal');

            function openRequestModal() { requestModal.style.display = 'flex'; }
            function closeRequestModal() { requestModal.style.display = 'none'; }
            window.onclick = function (event) { if (event.target == requestModal) closeRequestModal(); }

            function toggleComments(id) {
                const section = document.getElementById('comments-' + id);
                section.style.display = section.style.display === 'block' ? 'none' : 'block';
            }

            function toggleVote(requestId, btn) {
                const formData = new FormData();
                formData.append('toggle_vote', '1');
                formData.append('request_id', requestId);

                fetch('requests.php', {
                    method: 'POST',
                    body: formData
                }).then(() => {
                    location.reload(); // Simple reload for state update
                });
            }

            function closeRequest(requestId) {
                if (confirm('Are you sure you want to close this request? This indicates you no longer need the paper.')) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'requests.php';

                    const inputId = document.createElement('input');
                    inputId.type = 'hidden';
                    inputId.name = 'request_id';
                    inputId.value = requestId;

                    const inputAction = document.createElement('input');
                    inputAction.type = 'hidden';
                    inputAction.name = 'close_request';
                    inputAction.value = '1';

                    form.appendChild(inputId);
                    form.appendChild(inputAction);
                    document.body.appendChild(form);
                    form.submit();
                }
            }
        </script>
</body>

</html>