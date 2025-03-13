<?php
session_start();
if (!isset($_SESSION["admin_username"])) {
    header("Location: login.php");
    exit();
}

// Connect to the database
include("db.php");
$admin_username = $_SESSION["admin_username"];

// Fetch admin details
$query = "SELECT IDNO, CONCAT(FIRSTNAME, ' ', LASTNAME) AS full_name FROM register WHERE USERNAME = ?";
$stmt = mysqli_prepare($con, $query);
mysqli_stmt_bind_param($stmt, "s", $admin_username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    $idno = htmlspecialchars($row["IDNO"]);
    $fullname = htmlspecialchars($row["full_name"]);
} else {
    $fullname = "Admin";
}

// Set default admin profile image
$profile_img = "images/admin-pfp.png"; 

// Fetch latest announcements
$announcement_query = "SELECT title, content, date_posted FROM announcements ORDER BY date_posted DESC LIMIT 5";
$announcement_result = mysqli_query($con, $announcement_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS | Admin Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: rgb(230, 233, 241);
        }
        .navbar {
            background-color: #144c94;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar a {
            color: white;
            text-decoration: none;
            margin: 0 10px;
            font-size: larger;
        }
        .navbar a:hover {
            color: yellow;
        }
        .container {
    display: flex;
    justify-content: space-between;
    padding: 20px;
    gap: 20px;
    width: 100%;
    max-width: 1400px;
    margin: auto;
}

.card {
    background-color: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
    min-height: 400px;
    display: flex;
    flex-direction: column;
    align-items: center;
}

/* Make Admin Info Card smaller */
.admin-card {
    flex: 0.3; /* Adjusts the width to 30% */
}

/* Make Announcements Card bigger */
.announcement-card {
    flex: 0.7; /* Adjusts the width to 70% */
}

/* Style for announcements */
.announcement-list {
    text-align: left;
    max-height: 450px;
    overflow-y: auto;
    width: 100%;
    padding: 10px;
}

.announcement {
    background: #f9f9f9;
    padding: 16px;
    margin-bottom: 12px;
    border-radius: 6px;
    border-left: 5px solid #144c94;
    word-wrap: break-word;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    width: 95%;
}


.announcement strong {
    font-size: 18px;
    color: #144c94;
}

.announcement small {
    display: block;
    font-size: 12px;
    color: gray;
    margin-top: 4px;
}

.announcement p {
    margin: 6px 0 0;
    font-size: 14px;
    line-height: 1.4;
    color: #333;
}

    </style>
</head>
<body>
    <div class="navbar">
        <a>Admin Dashboard</a>
        <div>
            <a href="announcement.php">Create Announcements</a>
            <a href="students.php">Students</a>
            <a href="login.php" style="color: orange;">Log out</a>
        </div>
    </div>

    <div class="container">
        <!-- Admin Information -->
        <div class="card">
            <h3>Admin Information</h3>
            <img src="<?php echo $profile_img; ?>" alt="Admin Profile Picture" class="profile-img">
            <p><strong><?php echo $fullname; ?></strong></p>
        </div>

        <!-- Announcements -->
        <div class="card">
            <h3>Recent Announcements</h3>
            <div class="announcement-list">
                <?php while ($announcement = mysqli_fetch_assoc($announcement_result)) { ?>
                    <div class="announcement">
                        <strong><?php echo htmlspecialchars($announcement["title"]); ?></strong>
                        <small><?php echo date("Y-M-d", strtotime($announcement["date_posted"])); ?></small>
                        <p><?php echo nl2br(htmlspecialchars($announcement["content"])); ?></p>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <script>
        alert("Welcome, <?php echo addslashes($fullname); ?>!");
    </script>
</body>
</html>
