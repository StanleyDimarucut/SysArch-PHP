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
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: rgb(230, 233, 241);
        }
        .navbar {
            background-color: #144c94;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar a {
            color: white;
            text-decoration: none;
            margin: 0 15px;
            font-size: 18px;
        }
        .navbar a:hover {
            color: yellow;
        }
        .container {
            width: 95%;
            margin: 20px auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        h2 {
            color: #144c94;
            font-size: 1.5rem;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            color: #666;
            font-size: 14px;
            margin-bottom: 8px;
        }

        input, textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        textarea {
            min-height: 150px;
            resize: vertical;
        }

        .btn-submit {
            background-color: #007bff;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .announcement {
            padding: 15px;
            background: #f9f9f9;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .announcement strong {
            display: block;
            font-size: 16px;
            color: #144c94;
            margin-bottom: 5px;
        }

        .announcement small {
            display: block;
            font-size: 13px;
            color: #666;
            margin-bottom: 10px;
        }

        .announcement p {
            font-size: 14px;
            color: #444;
            line-height: 1.5;
        }

        .announcement-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }

        .btn-edit, .btn-delete {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            color: white;
        }

        .btn-edit {
            background-color: #007bff;
        }

        .btn-delete {
            background-color: #dc3545;
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
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            position: relative;
        }

        .close {
            position: absolute;
            right: 15px;
            top: 10px;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }

        .alert {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 4px;
            font-size: 14px;
            z-index: 1000;
        }

        .alert-success {
            background-color: #28a745;
            color: white;
        }

        .alert-error {
            background-color: #dc3545;
            color: white;
        }

        @media (max-width: 768px) {
            .container {
                width: 95%;
                padding: 15px;
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
        <a href="admin_dashboard.php" style="font-size: 1.2rem; font-weight: 600;">Admin Dashboard</a>
        <div>
            <a href="announcement.php">Announcements</a>
            <a href="student_list.php">View Student List</a>
            <a href="view_feedback.php">Feedback</a>
            <a href="students.php">Sit-in</a>
            <a href="sitin_view.php">Current Sit-in</a>
            <a href="session_history.php">Sit-in Reports</a>
            <a href="sitin_history.php">Sit-in History</a>
            <a href="login.php" style="color: #ffd700;">Log out</a>
        </div>
    </div>

    <div class="container">
        <div class="card">
            <h2>Post New Announcement</h2>
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
                <div class="announcement">
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

    <?php if (isset($_GET["error"])): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($_GET["error"]); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET["success"])): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($_GET["success"]); ?>
        </div>
    <?php endif; ?>

    <script>
        document.getElementById("content").addEventListener("input", function() {
            this.style.height = "auto";
            this.style.height = (this.scrollHeight) + "px";
        });

        document.getElementById("edit_content").addEventListener("input", function() {
            this.style.height = "auto";
            this.style.height = (this.scrollHeight) + "px";
        });

        function openEditModal(id, title, content) {
            document.getElementById("editModal").style.display = "block";
            document.getElementById("edit_id").value = id;
            document.getElementById("edit_title").value = title;
            document.getElementById("edit_content").value = content;
        }

        function closeEditModal() {
            document.getElementById("editModal").style.display = "none";
        }

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

        window.onclick = function(event) {
            const modal = document.getElementById("editModal");
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>

