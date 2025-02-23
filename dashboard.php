<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

// Connect to the database
include("db.php");
$username = $_SESSION["username"];

// Fetch the user's full name, course, and year level (using correct column names)
$query = "SELECT CONCAT(FIRSTNAME, ' ', MIDNAME, ' ', LASTNAME) AS full_name, COURSE, YEARLEVEL FROM register WHERE USERNAME = ?";
$stmt = mysqli_prepare($con, $query);
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    $fullname = $row["full_name"];
    $course = $row["COURSE"];
    $year = $row["YEARLEVEL"];
} else {
    $fullname = "User";
    $course = "Unknown";
    $year = "Unknown";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS | Home</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
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
        justify-content: space-between; /* Ensures the items are spread apart */
        align-items: stretch; /* Aligns them at the top */
        padding: 20px;
        gap: 20px; /* Space between student info and announcements */
        flex-wrap: nowrap; /* Prevents wrapping */
        }

        .card {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            width: 48%; /* Each card takes up 48% of the space */
            text-align: center;
            flex-direction: column;
            justify-content: space-between;
        }

        /* Media Query for small screens */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                align-items: center;
            }
            .card {
                width: 100%;
            }
        }

    </style>
</head>
<body>
    <div class="navbar">
        <a href="Homepage.php">Dashboard</a>
        <div>
            <a href="Homepage.php">Home</a>
            <a href="Profile.php">Edit Profile</a>
            <a href="history.php">History</a>
            <a href="Reservation.php">Reservation</a>
            <a href="../php/login.php" style="color: orange;">Log out</a>
        </div>
    </div>

    <div class="container">
        <!-- Student Information -->
        <div class="card">
            <h3>Student Information</h3>
            <img src="../../images/hutao.webp" alt="Profile Picture">
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
