<?php
session_start();
if (!isset($_SESSION["admin_username"])) {
    header("Location: login.php");
    exit();
}

// Connect to the database
include("db.php");

// Handle announcement submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
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
            width: 50%;
            margin: 30px auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
        }
        .form-group {
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
        }
        label {
            font-weight: bold;
            margin-bottom: 5px;
        }
        input, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }
        textarea {
            resize: none; /* Prevent manual resizing */
            min-height: 100px;
            overflow: hidden;
        }
        .btn-submit {
            background-color: #144c94;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
        }
        .btn-submit:hover {
            background-color: #0f3c7a;
        }
        .announcement {
            padding: 15px;
            background: #f9f9f9;
            margin-top: 15px;
            border-radius: 5px;
            border-left: 5px solid #144c94;
            word-wrap: break-word; /* Ensures long text wraps */
        }
        .error { color: red; font-weight: bold; }
        .success { color: green; font-weight: bold; }
    </style>
</head>
<body>

    <div class="navbar">
        <a href="admin_dashboard.php">Admin Dashboard</a>
        <div>
            <a href="announcement.php">Create Announcements</a>
            <a href="students.php">Students</a>
            <a href="sitin_view.php">Current Sit-in</a>
            <a href="session_history.php">Sit-in Reports</a>
            <a href="../php/login.php" style="color: orange;">Log out</a>
        </div>
    </div>

    <div class="container">
        <h2>Post an Announcement</h2>

        <?php if (isset($_GET["error"])) { echo "<p class='error'>" . htmlspecialchars($_GET["error"]) . "</p>"; } ?>
        <?php if (isset($_GET["success"])) { echo "<p class='success'>" . htmlspecialchars($_GET["success"]) . "</p>"; } ?>

        <form action="announcement.php" method="POST">
            <div class="form-group">
                <label for="title">Title:</label>
                <input type="text" id="title" name="title" required>
            </div>
            <div class="form-group">
                <label for="content">Content:</label>
                <textarea id="content" name="content" required></textarea>
            </div>
            <button type="submit" class="btn-submit">Post Announcement</button>
        </form>

        <h2>Previous Announcements</h2>
        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
            <div class="announcement">
                <strong><?php echo htmlspecialchars($row["title"]); ?></strong><br>
                <small>Posted by <?php echo htmlspecialchars($row["admin_username"]); ?> on <?php echo $row["date_posted"]; ?></small>
                <p><?php echo nl2br(htmlspecialchars($row["content"])); ?></p>
            </div>
        <?php } ?>
    </div>

    <script>
        // Auto-expand textarea as user types
        document.getElementById("content").addEventListener("input", function() {
            this.style.height = "auto";
            this.style.height = (this.scrollHeight) + "px";
        });
    </script>

</body>
</html>
