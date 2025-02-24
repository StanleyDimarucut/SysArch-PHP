<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

// Connect to the database
include("db.php");
$username = $_SESSION["username"];

// Fetch user details including profile image
$query = "SELECT CONCAT(FIRSTNAME, ' ', MIDNAME, ' ', LASTNAME) AS full_name, COURSE, YEARLEVEL, PROFILE_IMG FROM register WHERE USERNAME = ?";
$stmt = mysqli_prepare($con, $query);
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    $fullname = $row["full_name"];
    $course = $row["COURSE"];
    $year = $row["YEARLEVEL"];
    $profile_img = !empty($row["PROFILE_IMG"]) ? $row["PROFILE_IMG"] : "images/default.jpg"; // Use default if empty
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
            background-color: #f4f4f4;
        }
        td {
            padding: 10px;
            text-align: left;
        }
        .navbar {
            background-color: #144c94;
            padding: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar a {
            color: white;
            text-decoration: none;
            margin: 0 10px;
        }
        .navbar a:hover {
            color: yellow;
        }
        .container {
            display: flex;
            justify-content: space-between; 
            align-items: stretch;
            padding: 20px;
            gap: 20px;
            flex-wrap: nowrap;
            height: 80vh;
        }
        .card {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            width: 48%;
            text-align: center;
            flex-direction: column;
            justify-content: space-between;
            flex-grow: 1;
        }
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                align-items: center;
            }
            .card {
                width: 100%;
            }
        }
        .profile-img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #144c94;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="Homepage.php">Dashboard</a>
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
            <h3>Student Information</h3>
            <img src="<?php echo htmlspecialchars($profile_img); ?>" alt="Profile Picture" class="profile-img">
            <table class="info-table">
                <tr><td>Name:</td><td><?php echo htmlspecialchars($fullname); ?></td></tr>
                <tr><td>Course:</td><td><?php echo htmlspecialchars($course); ?></td></tr>
                <tr><td>Year Level:</td><td><?php echo htmlspecialchars($year); ?></td></tr>
                <tr><td>Session:</td><td>30</td></tr> <!-- Placeholder value -->
            </table>
        </div>

        <!-- Announcements -->
        <div class="card">
            <h3>Announcement</h3>
            <p><strong>CCS Admin | 2025-Feb-03</strong></p>
            <p>The College of Computer Studies will open the registration of students for the Sit-in privilege starting tomorrow.</p>
            <hr>
            <p><strong>CCS Admin | 2024-May-08</strong></p>
            <p>We are excited to announce the launch of our new website! 🎉</p>
        </div>
    </div>

    <script>
        alert("Welcome! <?php echo addslashes($fullname); ?>");
    </script>
</body>
</html>
