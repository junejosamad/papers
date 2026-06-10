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

        <!-- Notification Bell -->
        <div class="notification-wrapper" style="position: relative; display: inline-block;">
            <button class="btn btn-secondary" onclick="toggleNotifications()" title="Notifications"
                style="position: relative;">
                <span class="material-icons">notifications</span>
                <span id="notification-badge" class="badge"
                    style="display: none; position: absolute; top: -5px; right: -5px; background: red; color: white; border-radius: 50%; padding: 2px 6px; font-size: 10px;">0</span>
            </button>
            <div id="notification-dropdown" class="dropdown-menu"
                style="display: none; position: absolute; right: 0; top: 100%; width: 300px; background: var(--md-surface); box-shadow: 0 4px 12px rgba(0,0,0,0.15); border-radius: 8px; z-index: 1000; overflow: hidden;">
                <div style="padding: 10px; border-bottom: 1px solid rgba(0,0,0,0.1); font-weight: bold;">Notifications
                </div>
                <div id="notification-list" style="max-height: 300px; overflow-y: auto;">
                    <!-- Items will be loaded here -->
                    <div style="padding: 15px; text-align: center; color: #666;">No new notifications</div>
                </div>
            </div>
        </div>

        <a href="profile.php" class="user-badge" title="My Profile" style="cursor: pointer; text-decoration: none;">
            <?php if ($current_user['profile_image']): ?>
                <img src="uploads/<?php echo htmlspecialchars($current_user['profile_image']); ?>" alt="Profile"
                    style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; margin-right: 8px; vertical-align: middle;">
            <?php else: ?>
                <span class="material-icons"
                    style="vertical-align: middle; margin-right: 4px; font-size: 20px;">person</span>
            <?php endif; ?>
            <?php echo htmlspecialchars($current_user['full_name'] ?: $current_user['enrollment_number']); ?>
        </a>
        <a href="backpack.php" class="btn btn-secondary" title="My Backpack">
            <span class="material-icons">backpack</span>
            My Backpack
        </a>
        <a href="calculator.php" class="btn btn-secondary" title="GPA Calculator">
            <span class="material-icons">calculate</span>
            GPA Calc
        </a>
        <a href="requests.php" class="btn btn-secondary" title="Request Board">
            <span class="material-icons">campaign</span>
            Requests
        </a>
        <button class="btn btn-primary" onclick="openUploadModal()">
            <span class="material-icons">upload_file</span>
            Upload Paper
        </button>
        <a href="logout.php" class="btn btn-secondary">
            <span class="material-icons">logout</span>
            Logout
        </a>
    </div>
</div>

<script>
    function toggleNotifications() {
        const dropdown = document.getElementById('notification-dropdown');
        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';

        // Mark as read or just refresh
        fetchNotifications();
    }

    // Fetch Notifications from Server
    function fetchNotifications() {
        fetch('ajax/get_notifications.php')
            .then(response => response.json())
            .then(data => {
                const list = document.getElementById('notification-list');
                const badge = document.getElementById('notification-badge');

                // Update Badge
                if (data.unread_count > 0) {
                    badge.textContent = data.unread_count;
                    badge.style.display = 'block';
                    document.title = `(${data.unread_count}) ` + document.title.replace(/^\(\d+\) /, '');
                } else {
                    badge.style.display = 'none';
                    document.title = document.title.replace(/^\(\d+\) /, '');
                }

                // Update List
                list.innerHTML = '';
                if (data.notifications.length === 0) {
                    list.innerHTML = '<div style="padding: 15px; text-align: center; color: #666;">No notifications</div>';
                } else {
                    data.notifications.forEach(notif => {
                        const item = document.createElement('div');
                        item.style.padding = '10px';
                        item.style.borderBottom = '1px solid #eee';
                        item.style.backgroundColor = notif.is_read == 0 ? '#f0f7ff' : '#fff';
                        item.style.cursor = 'pointer';
                        item.innerHTML = `
                        <div style="font-size: 13px; font-weight: ${notif.is_read == 0 ? 'bold' : 'normal'}">${notif.message}</div>
                        <div style="font-size: 11px; color: #999;">${new Date(notif.created_at).toLocaleString()}</div>
                    `;
                        item.onclick = function () {
                            if (notif.link) window.location.href = notif.link;
                        };
                        list.appendChild(item);
                    });
                }
            });
    }

    // Browser Push Notification Check
    let lastNotificationId = null;

    function checkNewNotifications() {
        fetch('ajax/get_notifications.php')
            .then(response => response.json())
            .then(data => {
                // Update badge regardless
                const badge = document.getElementById('notification-badge');
                if (data.unread_count > 0) {
                    badge.textContent = data.unread_count;
                    badge.style.display = 'block';
                }

                // Check for new notification to push
                if (data.notifications.length > 0) {
                    const latest = data.notifications[0];
                    if (latest.is_read == 0 && latest.id != lastNotificationId) {
                        lastNotificationId = latest.id;

                        // Show Browser Notification
                        if (Notification.permission === "granted") {
                            const notif = new Notification("New Alert", {
                                body: latest.message,
                                icon: 'assets/img/logo.png' // Optional
                            });
                            notif.onclick = function () {
                                window.focus();
                                if (latest.link) window.location.href = latest.link;
                            };
                        }
                    }
                }
            });
    }

    // Initial Calls and Polling
    setInterval(checkNewNotifications, 2000); // Check every 2s for near-instant updates
    checkNewNotifications(); // Check on load

    // Close dropdown when clicking outside
    window.addEventListener('click', function (e) {
        if (!e.target.closest('.notification-wrapper')) {
            document.getElementById('notification-dropdown').style.display = 'none';
        }
    });

    // Browser Notification Request
    if (Notification.permission !== "granted" && Notification.permission !== "denied") {
        Notification.requestPermission();
    }
</script>