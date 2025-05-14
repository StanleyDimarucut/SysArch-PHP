<?php
session_start();
if (!isset($_SESSION["admin_username"])) {
    header("Location: login.php");
    exit();
}

// Connect to the database
include("db.php");

// Fetch admin notifications
$admin_notif_query = "SELECT * FROM notifications WHERE for_admin = 1 AND is_read = 0 ORDER BY created_at DESC";
$admin_notif_result = mysqli_query($con, $admin_notif_query);
$admin_notif_count = $admin_notif_result ? mysqli_num_rows($admin_notif_result) : 0;

// Handle marking notification as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'], $_POST['notif_id'])) {
    $notif_id = intval($_POST['notif_id']);
    mysqli_query($con, "UPDATE notifications SET is_read = 1 WHERE id = $notif_id");
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Handle announcement deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_id"])) {
    $delete_id = $_POST["delete_id"];
    $delete_query = "DELETE FROM announcements WHERE id = ?";
    $delete_stmt = mysqli_prepare($con, $delete_query);
    mysqli_stmt_bind_param($delete_stmt, "i", $delete_id);
    mysqli_stmt_execute($delete_stmt);
    header("Location: announcement.php?success=Announcement deleted successfully!");
    exit();
}

// Handle announcement update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["edit_id"])) {
    $edit_id = $_POST["edit_id"];
    $title = trim($_POST["edit_title"]);
    $content = trim($_POST["edit_content"]);
    
    if (!empty($title) && !empty($content)) {
        $update_query = "UPDATE announcements SET title = ?, content = ? WHERE id = ?";
        $update_stmt = mysqli_prepare($con, $update_query);
        mysqli_stmt_bind_param($update_stmt, "ssi", $title, $content, $edit_id);
        mysqli_stmt_execute($update_stmt);
        header("Location: announcement.php?success=Announcement updated successfully!");
        exit();
    } else {
        header("Location: announcement.php?error=All fields are required.");
        exit();
    }
}

// Handle new announcement submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["title"]) && !isset($_POST["edit_id"])) {
    $title = trim($_POST["title"]);
    $content = trim($_POST["content"]);
    $admin_username = $_SESSION["admin_username"];

    if (!empty($title) && !empty($content)) {
        $query = "INSERT INTO announcements (title, content, admin_username, date_posted) VALUES (?, ?, ?, NOW())";
        $stmt = mysqli_prepare($con, $query);
        mysqli_stmt_bind_param($stmt, "sss", $title, $content, $admin_username);
        mysqli_stmt_execute($stmt);

        // --- NOTIFICATION LOGIC START ---
        $students_query = "SELECT IDNO FROM register WHERE USERNAME != 'admin'";
        $students_result = mysqli_query($con, $students_query);
        while ($student = mysqli_fetch_assoc($students_result)) {
            $student_id = $student['IDNO'];
            $notif_msg = "New announcement posted: \"$title\"";
            $notif_query = "INSERT INTO notifications (user_id, message) VALUES ($student_id, '$notif_msg')";
            mysqli_query($con, $notif_query);
        }
        // --- NOTIFICATION LOGIC END ---

        header("Location: announcement.php?success=Announcement posted successfully!");
        exit();
    } else {
        header("Location: announcement.php?error=All fields are required.");
        exit();
    }
}

// Fetch all announcements
$query = "SELECT * FROM announcements ORDER BY date_posted DESC";
$result = mysqli_query($con, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS | Announcements</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f2f5;
        }

        .navbar {
            background: linear-gradient(135deg, #144c94 0%, #1a5dba 100%);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-brand {
            color: white;
            text-decoration: none;
            font-size: 1.3rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .navbar-brand:hover {
            background: rgba(255,255,255,0.1);
            transform: translateY(-1px);
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .nav-link {
            color: white;
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 8px 16px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.05);
        }

        .nav-link:hover {
            background: rgba(255,255,255,0.15);
            color: #ffd700;
            transform: translateY(-1px);
        }

        .nav-link i {
            font-size: 14px;
        }

        .nav-link.logout {
            background: rgba(255,217,0,0.15);
            color: #ffd700;
            border: 1px solid rgba(255,217,0,0.3);
        }

        .nav-link.logout:hover {
            background: rgba(255,217,0,0.25);
        }

        .mobile-menu-toggle {
            display: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        @media (max-width: 1024px) {
            .mobile-menu-toggle {
                display: block;
            }

            .nav-menu {
                display: none;
                width: 100%;
                padding: 15px 0;
                margin-top: 15px;
                border-top: 1px solid rgba(255,255,255,0.1);
            }

            .nav-menu.active {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 8px;
            }

            .navbar {
                flex-wrap: wrap;
            }

            .navbar-brand {
                flex: 1;
            }

            .nav-link {
                justify-content: center;
            }
        }

        .main-container {
            width: 95%;
            max-width: 1400px;
            margin: 20px auto;
            padding: 0;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .page-header {
            background: white;
            padding: 25px 30px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            margin-bottom: 24px;
        }

        .page-header h1 {
            color: #1a5dba;
            font-size: 24px;
            margin: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
        }

        .page-header h1 i {
            margin-right: 10px;
        }

        .card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.05);
            margin-bottom: 24px;
            transition: transform 0.2s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .card h2 {
            color: #1a5dba;
            font-size: 18px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f2f5;
            font-weight: 600;
            display: flex;
            align-items: center;
        }

        .card h2 i {
            margin-right: 10px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            color: #666;
            font-size: 14px;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #e5e9ef;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: #f8fafc;
            font-family: 'Inter', sans-serif;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #1a5dba;
            outline: none;
            background: white;
            box-shadow: 0 0 0 3px rgba(26,93,186,0.1);
        }

        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }

        .btn-submit {
            background: linear-gradient(135deg, #144c94 0%, #1a5dba 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(26,93,186,0.2);
        }

        .announcement-list {
            margin-top: 20px;
        }

        .announcement {
            padding: 16px;
            background: #f8fafc;
            margin-bottom: 12px;
            border: 1px solid #e5e9ef;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .announcement:hover {
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transform: translateX(4px);
        }

        .announcement strong {
            display: block;
            font-size: 15px;
            color: #1a5dba;
            margin-bottom: 6px;
            font-weight: 600;
        }

        .announcement small {
            display: block;
            font-size: 13px;
            color: #666;
            margin-bottom: 8px;
        }

        .announcement p {
            font-size: 14px;
            color: #444;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .announcement-actions {
            display: flex;
            gap: 8px;
        }

        .btn-edit,
        .btn-delete {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }

        .btn-edit {
            background: linear-gradient(135deg, #144c94 0%, #1a5dba 100%);
            color: white;
        }

        .btn-delete {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }

        .btn-edit:hover,
        .btn-delete:hover {
            transform: translateY(-1px);
        }

        .btn-edit:hover {
            box-shadow: 0 4px 12px rgba(26,93,186,0.2);
        }

        .btn-delete:hover {
            box-shadow: 0 4px 12px rgba(220,53,69,0.2);
        }

        .btn-edit i,
        .btn-delete i {
            margin-right: 6px;
        }

        /* Modal Styles */
        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 32px;
            border-radius: 12px;
            width: 500px;
            position: relative;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            animation: modalSlideIn 0.3s ease;
            margin: 0;
        }

        .modal-content h2 {
            font-size: 20px;
            margin: 0 0 24px 0;
            padding-right: 24px;
        }

        .modal-content .form-group {
            margin-bottom: 20px;
        }

        .modal-content input,
        .modal-content textarea {
            padding: 12px;
            font-size: 14px;
        }

        .modal-content textarea {
            min-height: 120px;
            max-height: 300px;
        }

        .modal-content .btn-submit {
            width: 100%;
            margin-top: 8px;
        }

        .close {
            position: absolute;
            right: 24px;
            top: 24px;
            font-size: 24px;
            font-weight: bold;
            color: #666;
            cursor: pointer;
            transition: all 0.2s ease;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .close:hover {
            background: rgba(0,0,0,0.05);
            color: #1a5dba;
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @media (max-width: 576px) {
            .modal-content {
                width: 90%;
                padding: 24px;
                margin: 20px;
            }
        }

        @media (max-width: 768px) {
            .main-container {
                width: 95%;
                padding: 10px;
            }
            .navbar {
                flex-direction: column;
                padding: 10px;
            }
            .navbar > div {
                margin-top: 10px;
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
            }
            .navbar a {
                margin: 5px;
            }
            .page-header {
                padding: 15px 20px;
            }
            .card {
                padding: 15px;
            }
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .notification-bell {
            position: absolute;
            top: 18px;
            right: 40px;
            display: inline-block;
            cursor: pointer;
            font-size: 22px;
            color: #fff;
            transition: color 0.2s;
            padding: 8px;
            border-radius: 8px;
            z-index: 1100;
        }

        .notification-bell:hover {
            color: #ffd700;
            background: rgba(255,255,255,0.1);
        }

        .notif-badge {
            position: absolute;
            top: 0;
            right: 0;
            background: #dc3545;
            color: #fff;
            border-radius: 50%;
            padding: 2px 7px;
            font-size: 12px;
            font-weight: bold;
        }

        .notif-dropdown {
            position: absolute;
            right: 15px;
            top: 60px;
            background: #fffbe6;
            border: 1px solid #ffe58f;
            border-radius: 8px;
            min-width: 300px;
            z-index: 2000;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            padding: 16px 12px 12px 12px;
        }

        .notif-dropdown h4 {
            margin: 0 0 10px 0;
            color: #d48806;
            font-size: 16px;
        }

        .notif-dropdown ul {
            list-style: none;
            padding: 0;
            margin: 0;
            max-height: 250px;
            overflow-y: auto;
        }

        .notif-dropdown li {
            margin-bottom: 8px;
            font-size: 15px;
            padding: 8px;
            border-bottom: 1px solid #ffe58f;
        }

        .notif-dropdown li:last-child {
            border-bottom: none;
        }

        .btn-mark-read {
            background: #1a5dba;
            color: white;
            border: none;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            margin-left: 8px;
        }

        .btn-mark-read:hover {
            background: #144c94;
        }

        @media (max-width: 1024px) {
            .notification-bell {
                top: 18px;
                right: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="admin_dashboard.php" class="navbar-brand">
            <i class="fas fa-chart-line"></i>
            Admin Dashboard
        </a>
        <div class="notification-bell" onclick="toggleAdminNotifDropdown()">
            <i class="fas fa-bell"></i>
            <?php if ($admin_notif_count > 0): ?>
                <span class="notif-badge"><?php echo $admin_notif_count; ?></span>
            <?php endif; ?>
        </div>
        <div class="mobile-menu-toggle" onclick="toggleMenu()">
            <i class="fas fa-bars"></i>
        </div>
        <div class="nav-menu">
            <a href="announcement.php" class="nav-link"><i class="fas fa-bullhorn"></i> Announcements</a>
            <a href="student_list.php" class="nav-link"><i class="fas fa-users"></i> Student List</a>
            <a href="view_feedback.php" class="nav-link"><i class="fas fa-comments"></i> Feedback</a>
            <a href="view_reservations.php" class="nav-link"><i class="fas fa-calendar-alt"></i> View Reservations</a>
            <a href="students.php" class="nav-link"><i class="fas fa-user-check"></i> Sit-in</a>
            <a href="sitin_view.php" class="nav-link"><i class="fas fa-clock"></i> Current Sit-in</a>
            <a href="session_history.php" class="nav-link"><i class="fas fa-history"></i> Reports</a>
            <a href="sitin_history.php" class="nav-link"><i class="fas fa-calendar-alt"></i> History</a>
            <a href="leaderboards.php" class="nav-link"><i class="fas fa-trophy"></i> Leaderboards</a>
            <a href="resources.php" class="nav-link"><i class="fas fa-book"></i> Resources</a>
            <a href="PC_management.php" class="nav-link"><i class="fas fa-desktop"></i> PC Management</a>
            <a href="lab_schedule.php" class="nav-link"><i class="fas fa-calendar-alt"></i> Lab Schedule</a>
            <a href="login.php" class="nav-link logout"><i class="fas fa-sign-out-alt"></i> Log out</a>
        </div>
    </div>

    <div id="adminNotifDropdown" class="notif-dropdown" style="display:none;">
        <h4>Admin Notifications</h4>
        <ul>
            <?php
            if ($admin_notif_result && mysqli_num_rows($admin_notif_result) > 0) {
                while($notif = mysqli_fetch_assoc($admin_notif_result)): ?>
                    <li>
                        <?php echo htmlspecialchars($notif['message']); ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="notif_id" value="<?php echo $notif['id']; ?>">
                            <button type="submit" name="mark_read" class="btn-mark-read">Mark as read</button>
                        </form>
                    </li>
                <?php endwhile;
            } else {
                echo '<li>No new notifications.</li>';
            }
            ?>
        </ul>
    </div>

    <div class="main-container">
        <?php if (isset($_GET["error"])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($_GET["error"]); ?>
            </div>
        <?php endif; ?>

        <div class="page-header">
            <h1><i class="fas fa-bullhorn"></i> Announcements Management</h1>
        </div>

        <div class="card">
            <h2><i class="fas fa-plus-circle"></i> Post New Announcement</h2>
            <form action="announcement.php" method="POST">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" required placeholder="Enter announcement title">
                </div>
                <div class="form-group">
                    <label for="content">Content</label>
                    <textarea id="content" name="content" required placeholder="Enter announcement content"></textarea>
                </div>
                <button type="submit" name="submit" class="btn-submit"><i class="fas fa-paper-plane"></i> Post Announcement</button>
            </form>
        </div>

        <div class="card">
            <h2><i class="fas fa-list"></i> Previous Announcements</h2>
            <div class="announcement-list">
                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                    <div class="announcement">
                        <strong><?php echo htmlspecialchars($row["title"]); ?></strong>
                        <small><i class="far fa-calendar-alt"></i> <?php echo date("F j, Y", strtotime($row["date_posted"])); ?></small>
                        <p><?php echo nl2br(htmlspecialchars($row["content"])); ?></p>
                        <div class="announcement-actions">
                            <button class="btn-edit" onclick="editAnnouncement(<?php echo $row['id']; ?>, '<?php echo addslashes($row['title']); ?>', '<?php echo addslashes($row['content']); ?>')">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn-delete" onclick="deleteAnnouncement(<?php echo $row['id']; ?>)">
                                <i class="fas fa-trash-alt"></i> Delete
                            </button>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2><i class="fas fa-edit"></i> Edit Announcement</h2>
            <form action="announcement.php" method="POST">
                <input type="hidden" id="edit_id" name="edit_id">
                <div class="form-group">
                    <label for="edit_title">Title</label>
                    <input type="text" id="edit_title" name="edit_title" required>
                </div>
                <div class="form-group">
                    <label for="edit_content">Content</label>
                    <textarea id="edit_content" name="edit_content" required></textarea>
                </div>
                <button type="submit" name="update" class="btn-submit"><i class="fas fa-save"></i> Update Announcement</button>
            </form>
        </div>
    </div>

    <script>
        // Auto-resize textareas
        document.getElementById("content").addEventListener("input", function() {
            this.style.height = "auto";
            this.style.height = (this.scrollHeight) + "px";
        });

        document.getElementById("edit_content").addEventListener("input", function() {
            this.style.height = "auto";
            this.style.height = (this.scrollHeight) + "px";
        });

        // Edit announcement function
        function editAnnouncement(id, title, content) {
            document.getElementById("editModal").classList.add("show");
            document.getElementById("edit_id").value = id;
            document.getElementById("edit_title").value = title;
            document.getElementById("edit_content").value = content;
            
            // Auto resize the textarea after setting content
            const textarea = document.getElementById("edit_content");
            textarea.style.height = "auto";
            textarea.style.height = (textarea.scrollHeight) + "px";
        }

        // Delete announcement function
        function deleteAnnouncement(id) {
            if (confirm("Are you sure you want to delete this announcement?")) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'announcement.php';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'delete_id';
                input.value = id;
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById("editModal");
            if (event.target == modal) {
                modal.classList.remove("show");
            }
        }

        // Close modal when clicking the X
        document.querySelector('.close').onclick = function() {
            document.getElementById("editModal").classList.remove("show");
        }

        // Handle alerts animation
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s ease-in-out';
                setTimeout(() => alert.style.opacity = '1', 100);
                
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }, 3000);
            });
        });

        function toggleMenu() {
            const menu = document.querySelector('.nav-menu');
            menu.classList.toggle('active');
        }

        function toggleAdminNotifDropdown() {
            var dropdown = document.getElementById('adminNotifDropdown');
            dropdown.style.display = (dropdown.style.display === 'none' || dropdown.style.display === '') ? 'block' : 'none';
        }

        document.addEventListener('click', function(event) {
            var bell = document.querySelector('.notification-bell');
            var dropdown = document.getElementById('adminNotifDropdown');
            if (!bell.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.style.display = 'none';
            }
        });
    </script>
</body>
</html>

