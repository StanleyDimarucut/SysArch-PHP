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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .navbar a:hover {
            background: rgba(255,255,255,0.15);
            color: #ffd700;
            transform: translateY(-1px);
        }

        .navbar a.logout {
            background: rgba(255,217,0,0.15);
            color: #ffd700;
            border: 1px solid rgba(255,217,0,0.3);
        }

        .navbar a.logout:hover {
            background: rgba(255,217,0,0.25);
        }

        .main-container {
            margin-top: 80px;
            padding: 2rem;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
            width: 95%;
        }

        .dashboard-header {
            background: white;
            padding: 25px 30px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            margin-bottom: 24px;
        }

        .dashboard-header h1 {
            color: #1a5dba;
            font-size: 24px;
            margin: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 24px;
            margin-bottom: 24px;
        }

        .card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
        }

        .profile-card {
            text-align: center;
        }

        .profile-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #1a5dba;
            margin: 1.5rem auto;
        }

        .info-table {
            width: 100%;
            margin-top: 1.5rem;
        }

        .info-table td {
            padding: 12px;
            border-bottom: 1px solid #e5e9ef;
        }

        .info-table td:first-child {
            color: #666;
            font-weight: 500;
            width: 40%;
        }

        .info-table td:last-child {
            font-weight: 600;
            color: #1a1a1a;
        }

        .card h2 {
            color: #1a5dba;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f0f2f5;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .announcement-list {
            max-height: 400px;
            overflow-y: auto;
            padding-right: 0.5rem;
        }

        .announcement {
            padding: 1rem;
            background: #f8fafc;
            margin-bottom: 1rem;
            border-radius: 8px;
            border-left: 4px solid #1a5dba;
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
            margin: 0;
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
            background: #c1c1c1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            .main-container {
                padding: 1rem;
            }
            .navbar {
                padding: 1rem;
            }
            .navbar a {
                padding: 6px 12px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="#" style="font-size: 1.2rem; font-weight: 600;"><i class="fas fa-home"></i> Student Dashboard</a>
        <div>
            <a href="dashboard.php"><i class="fas fa-home"></i> Home</a>
            <a href="profile.php"><i class="fas fa-user-edit"></i> Edit Profile</a>
            <a href="history.php"><i class="fas fa-history"></i> History</a>
            <a href="student_resources.php"><i class="fas fa-book"></i> Resources</a>
            <a href="Reservation.php"><i class="fas fa-calendar-plus"></i> Reservation</a>
            <a href="login.php" class="logout"><i class="fas fa-sign-out-alt"></i> Log out</a>
        </div>
    </div>

    <div class="main-container">
        <div class="dashboard-header">
            <h1><i class="fas fa-user-circle"></i> Welcome, <?php echo $fullname; ?>!</h1>
        </div>

        <div class="dashboard-grid">
            <div class="card profile-card">
                <h2><i class="fas fa-id-card"></i> Student Information</h2>
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
                <h2><i class="fas fa-bullhorn"></i> Recent Announcements</h2>
                <div class="announcement-list">
                    <?php while ($announcement = mysqli_fetch_assoc($announcement_result)) { ?>
                        <div class="announcement">
                            <strong><?php echo htmlspecialchars($announcement["title"]); ?></strong>
                            <small>Posted on <?php echo date('F j, Y', strtotime($announcement["date_posted"])); ?></small>
                            <p><?php echo nl2br(htmlspecialchars($announcement["content"])); ?></p>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

