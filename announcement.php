<?php
session_start();
if (!isset($_SESSION["admin_username"])) {
    header("Location: login.php");
    exit();
}

// Connect to the database
include("db.php");

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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
        }

        .navbar {
            background-color: #144c94;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            margin: 0 15px;
            font-size: 0.95rem;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .navbar a:hover {
            color: #ffd700;
        }

        .main-container {
            margin-top: 80px;
            padding: 2rem;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }

        .dashboard-header {
            margin-bottom: 2rem;
        }

        .dashboard-header h1 {
            color: #1a1a1a;
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }

        .card h2 {
            color: #1a1a1a;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f0f2f5;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        label {
            display: block;
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        input, textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.95rem;
            transition: border-color 0.3s ease;
        }

        textarea {
            min-height: 150px;
            resize: vertical;
        }

        input:focus, textarea:focus {
            outline: none;
            border-color: #144c94;
        }

        .btn-submit {
            background-color: #144c94;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-submit:hover {
            background-color: #0d3a7d;
        }

        .announcement {
            padding: 1.5rem;
            background: #f8f9fa;
            margin-bottom: 1rem;
            border-radius: 8px;
            border-left: 4px solid #144c94;
            transition: transform 0.2s ease;
        }

        .announcement:hover {
            transform: translateX(5px);
        }

        .announcement strong {
            display: block;
            font-size: 1.1rem;
            color: #1a1a1a;
            margin-bottom: 0.5rem;
        }

        .announcement small {
            display: block;
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 0.75rem;
        }

        .announcement p {
            font-size: 0.95rem;
            color: #444;
            line-height: 1.6;
        }

        .announcement-actions {
            margin-top: 1rem;
            display: flex;
            gap: 0.75rem;
        }

        .btn-edit, .btn-delete {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-edit {
            background-color: #ffc107;
            color: #000;
        }

        .btn-delete {
            background-color: #dc3545;
            color: white;
        }

        .btn-edit:hover {
            background-color: #e0a800;
        }

        .btn-delete:hover {
            background-color: #c82333;
        }

        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-weight: 500;
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

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            position: relative;
        }

        .close {
            position: absolute;
            right: 1.5rem;
            top: 1rem;
            font-size: 1.5rem;
            font-weight: bold;
            color: #666;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .close:hover {
            color: #000;
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }

            .card {
                padding: 1.5rem;
            }

            .announcement-actions {
                flex-direction: column;
            }

            .btn-edit, .btn-delete {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="#" style="font-size: 1.2rem; font-weight: 600;">Admin Dashboard</a>
        <div>
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="announcement.php">Announcements</a>
            <a href="student_list.php">View Student List</a>
            <a href="students.php">Sit-in</a>
            <a href="sitin_view.php">Current Sit-in</a>
            <a href="session_history.php">Sit-in Reports</a>
            <a href="login.php" style="color: #ffd700;">Log out</a>
        </div>
    </div>

    <div class="main-container">
        <div class="dashboard-header">
            <h1>Announcements Management</h1>
        </div>

        <div class="card">
            <h2>Post New Announcement</h2>
            <?php if (isset($_GET["error"])) { echo "<div class='alert alert-error'>" . htmlspecialchars($_GET["error"]) . "</div>"; } ?>
            <?php if (isset($_GET["success"])) { echo "<div class='alert alert-success'>" . htmlspecialchars($_GET["success"]) . "</div>"; } ?>

            <form action="announcement.php" method="POST">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" required placeholder="Enter announcement title">
                </div>
                <div class="form-group">
                    <label for="content">Content</label>
                    <textarea id="content" name="content" required placeholder="Enter announcement content"></textarea>
                </div>
                <button type="submit" class="btn-submit">Post Announcement</button>
            </form>
        </div>

        <div class="card">
            <h2>Previous Announcements</h2>
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                <div class="announcement" id="announcement-<?php echo $row['id']; ?>">
                    <strong><?php echo htmlspecialchars($row["title"]); ?></strong>
                    <small>Posted by <?php echo htmlspecialchars($row["admin_username"]); ?> on <?php echo date("F j, Y", strtotime($row["date_posted"])); ?></small>
                    <p><?php echo nl2br(htmlspecialchars($row["content"])); ?></p>
                    <div class="announcement-actions">
                        <button class="btn-edit" onclick="openEditModal(<?php echo $row['id']; ?>, '<?php echo addslashes($row['title']); ?>', '<?php echo addslashes($row['content']); ?>')">Edit</button>
                        <button class="btn-delete" onclick="deleteAnnouncement(<?php echo $row['id']; ?>)">Delete</button>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Edit Announcement</h2>
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
                <button type="submit" class="btn-submit">Update Announcement</button>
            </form>
        </div>
    </div>

    <script>
        // Auto-expand textarea as user types
        document.getElementById("content").addEventListener("input", function() {
            this.style.height = "auto";
            this.style.height = (this.scrollHeight) + "px";
        });

        document.getElementById("edit_content").addEventListener("input", function() {
            this.style.height = "auto";
            this.style.height = (this.scrollHeight) + "px";
        });

        // Edit Modal Functions
        function openEditModal(id, title, content) {
            document.getElementById("editModal").style.display = "block";
            document.getElementById("edit_id").value = id;
            document.getElementById("edit_title").value = title;
            document.getElementById("edit_content").value = content;
        }

        function closeEditModal() {
            document.getElementById("editModal").style.display = "none";
        }

        // Delete Function
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
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>
