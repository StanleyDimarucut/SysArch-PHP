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

// Fetch user details including profile image and remaining sessions
$query = "SELECT IDNO, CONCAT(FIRSTNAME, ' ', MIDNAME, ' ', LASTNAME) AS full_name, COURSE, YEARLEVEL, PROFILE_IMG, remaining_sessions FROM register WHERE USERNAME = ?";
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
    $remaining_sessions = $row["remaining_sessions"];
} else {
    $fullname = "User";
    $course = "Unknown";
    $year = "Unknown";
    $profile_img = "images/default.jpg"; // Default profile image
    $remaining_sessions = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS | Dashboard</title>
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
            max-width: 1400px;
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

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .profile-card {
            text-align: center;
        }

        .profile-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #144c94;
            margin: 1rem 0;
        }

        .info-table {
            width: 100%;
            margin-top: 1.5rem;
        }

        .info-table td {
            padding: 0.75rem;
            border-bottom: 1px solid #f0f2f5;
        }

        .info-table td:first-child {
            color: #666;
            font-weight: 500;
        }

        .info-table td:last-child {
            font-weight: 600;
            color: #1a1a1a;
        }

        .card h2 {
            color: #1a1a1a;
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f0f2f5;
        }

        .announcement-list {
            max-height: 300px;
            overflow-y: auto;
            padding-right: 0.5rem;
        }

        .announcement {
            padding: 1rem;
            background: #f8f9fa;
            margin-bottom: 1rem;
            border-radius: 8px;
            border-left: 4px solid #144c94;
        }

        .announcement strong {
            display: block;
            font-size: 1rem;
            color: #1a1a1a;
            margin-bottom: 0.3rem;
        }

        .announcement small {
            display: block;
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 0.5rem;
        }

        .announcement p {
            font-size: 0.9rem;
            color: #444;
            line-height: 1.5;
        }

        .rules-card {
            margin-top: 1.5rem;
        }

        .rules-list {
            max-height: 400px;
            overflow-y: auto;
            padding-right: 0.5rem;
        }

        .rules-list p {
            margin-bottom: 1rem;
            line-height: 1.6;
        }

        .rules-list ol {
            padding-left: 1.5rem;
            margin-bottom: 1rem;
        }

        .rules-list li {
            margin-bottom: 0.5rem;
            color: #444;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="#" style="font-size: 1.2rem; font-weight: 600;">Student Dashboard</a>
        <div>
            <a href="dashboard.php">Home</a>
            <a href="profile.php">Edit Profile</a>
            <a href="history.php">History</a>
            <a href="Reservation.php">Reservation</a>
            <a href="login.php" style="color: #ffd700;">Log out</a>
        </div>
    </div>

    <div class="main-container">
        <div class="dashboard-header">
            <h1>Welcome, <?php echo $fullname; ?>!</h1>
        </div>

        <div class="dashboard-grid">
            <div class="card profile-card">
                <h2>Student Information</h2>
                <img src="<?php echo $profile_img; ?>" alt="Profile Picture" class="profile-img">
                <table class="info-table">
                    <tr><td>ID Number:</td><td><?php echo $idno; ?></td></tr>
                    <tr><td>Name:</td><td><?php echo $fullname; ?></td></tr>
                    <tr><td>Course:</td><td><?php echo $course; ?></td></tr>
                    <tr><td>Year Level:</td><td><?php echo $year; ?></td></tr>
                    <tr>
                        <td>Remaining Sessions:</td>
                        <td style="<?php echo $remaining_sessions <= 5 ? 'color: #dc3545; font-weight: bold;' : 'color: #28a745; font-weight: bold;'; ?>">
                            <?php echo $remaining_sessions; ?>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="card">
                <h2>Recent Announcements</h2>
                <div class="announcement-list">
                    <?php while ($announcement = mysqli_fetch_assoc($announcement_result)) { ?>
                        <div class="announcement">
                            <strong><?php echo htmlspecialchars($announcement["title"]); ?></strong>
                            <small><?php echo date("F j, Y", strtotime($announcement["date_posted"])); ?></small>
                            <p><?php echo nl2br(htmlspecialchars($announcement["content"])); ?></p>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>

        <div class="card rules-card">
            <h2>Laboratory Rules and Regulations</h2>
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
</body>
</html>

