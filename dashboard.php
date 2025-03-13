<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

// Connect to the database
include("db.php");
$username = $_SESSION["username"];

$announcement_query = "SELECT title, content, date_posted FROM announcements ORDER BY date_posted DESC LIMIT 5";
$announcement_result = mysqli_query($con, $announcement_query);

// Fetch user details including profile image
$query = "SELECT IDNO, CONCAT(FIRSTNAME, ' ', MIDNAME, ' ', LASTNAME) AS full_name, COURSE, YEARLEVEL, PROFILE_IMG FROM register WHERE USERNAME = ?";
$stmt = mysqli_prepare($con, $query);
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    $idno = htmlspecialchars($row["IDNO"]);
    $fullname = htmlspecialchars($row["full_name"]);
    $course = htmlspecialchars($row["COURSE"]);
    $year = htmlspecialchars($row["YEARLEVEL"]);
    $profile_img = !empty($row["PROFILE_IMG"]) ? htmlspecialchars($row["PROFILE_IMG"]) : "images/default.jpg"; // Use default if empty
} else {
    $fullname = "User";
    $course = "Unknown";
    $year = "Unknown";
    $profile_img = "images/default.jpg"; // Default profile image
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS | Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: rgb(230, 233, 241);
        }
        td {
            padding: 10px;
            text-align: left;
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
    gap: 25px; /* Increases space between cards */
    flex-wrap: nowrap;
    align-items: stretch; /* Makes all cards the same height */
}

/* General Card Styling */
/* General Card Styling */
.card {
    background-color: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
    text-align: center;
    flex-grow: 1;
}

/* Profile Card - Adjusted to Match Other Cards */
.profile-card {
    flex: 0 0 30%; /* Wider than before */
    max-width: 30%;
    display: flex;
    flex-direction: column;
    align-items: center;
}

/* Announcement & Rules Cards - Same Width */
.announcement-card,
.rules-card {
    flex: 0 0 35%;
    max-width: 35%;
    display: flex;
    flex-direction: column;
}

/* Ensure Consistent Heights & Scrollability */
.announcement-list,
.rules-list {
    max-height: 400px;
    overflow-y: auto;
    text-align: left;
    padding: 10px;
    word-wrap: break-word;
    flex-grow: 1;
}

/* Profile Image Styling */
.profile-card img {
    width: 100px; /* Adjust the size of the profile image */
    height: 100px;
    border-radius: 50%;
    margin-bottom: 10px;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .container {
        flex-wrap: wrap;
    }

    .profile-card,
    .announcement-card,
    .rules-card {
        width: 100%;
        max-width: 100%;
    }
}

        .profile-img {
            width: 120px; /* Reduce image size */
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #144c94;
        }
        .info-table {
            margin: 20px auto;
            text-align: left;
        }
        
    </style>
</head>
<body>
    <div class="navbar">
        <a>Dashboard</a>
        <div>
            <a href="dashboard.php">Home</a>
            <a href="profile.php">Edit Profile</a>
            <a href="history.php">History</a>
            <a href="Reservation.php">Reservation</a>
            <a href="../php/login.php" style="color: orange;">Log out</a>
        </div>
    </div>

    <div class="container">
        <!-- Student Information -->
        <div class="card">
            <div class="profile">
                <h3>Student Information</h3>
                <img src="<?php echo $profile_img; ?>" alt="Profile Picture" class="profile-img">
                <table class="info-table">
                    <tr><td>ID number:</td><td><?php echo $idno; ?></td></tr>
                    <tr><td>Name:</td><td><?php echo $fullname; ?></td></tr>
                    <tr><td>Course:</td><td><?php echo $course; ?></td></tr>
                    <tr><td>Year Level:</td><td><?php echo $year; ?></td></tr>
                    <tr><td>Session:</td><td>30</td></tr> <!-- Placeholder value -->
                </table>
            </div>
        </div>

        <!-- Announcements and Rules Container -->
        <div class="card announcement-card">
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

        <!-- Rules & Regulations Card -->
<div class="card rules-card">
    <h3>Rules and Regulations</h3>
    <div class="rules-list">
        <p><strong>COLLEGE OF INFORMATION & COMPUTER STUDIES</strong></p>
        <p>To avoid embarrassment and maintain camaraderie with your friends and superiors at our laboratories, please observe the following:</p>
        <ol>
            <li>Maintain silence, proper decorum, and discipline inside the laboratory. Mobile phones, walkmans, and other personal equipment must be switched off.</li>
            <li>Games are not allowed inside the lab. This includes computer-related games, card games, and other games that may disturb the operation of the lab.</li>
            <li>Surfing the Internet is allowed only with the permission of the instructor. Downloading and installing software are strictly prohibited.</li>
            <li>Getting access to websites not related to the course (especially pornographic and illicit sites) is strictly prohibited.</li>
            <li>Deleting computer files and changing the computer's setup is a major offense.</li>
            <li>Observe computer time usage carefully. A fifteen-minute allowance is given for each use; otherwise, the unit will be given to those who wish to "sit in".</li>
            <li>Do not enter the lab unless the instructor is present.</li>
            <li>All bags and knapsacks must be deposited at the counter.</li>
            <li>Follow the seating arrangement of your instructor.</li>
            <li>At the end of class, all software programs must be closed.</li>
            <li>Return all chairs to their proper places after use.</li>
            <li>Chewing gum, eating, drinking, smoking, and vandalism are prohibited inside the lab.</li>
            <li>Anyone causing a disturbance will be asked to leave the lab.</li>
            <li>Persons exhibiting hostile or threatening behavior (yelling, swearing, etc.) will be removed.</li>
            <li>For serious offenses, lab personnel may call security for assistance.</li>
            <li>Any technical issues must be reported to the lab supervisor, student assistant, or instructor immediately.</li>
        </ol>

        <p><strong>DISCIPLINARY ACTION</strong></p>
        <p><strong>First Offense:</strong> The Head, Dean, or OIC recommends suspension from classes.</p>
        <p><strong>Second & Subsequent Offenses:</strong> A recommendation for heavier sanctions will be endorsed to the Guidance Center.</p>
    </div>
</div>

    </div>

    <script>
        alert("Welcome! <?php echo addslashes($fullname); ?>");
    </script>
</body>
</html>
