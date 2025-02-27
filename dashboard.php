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
            justify-content: space-between; /* Aligns cards horizontally */
            padding: 20px;
            gap: 20px; /* Space between the cards */
            flex-wrap: nowrap; /* Allows cards to wrap on smaller screens */
        }
        .card {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            width: 48%; /* Cards will take 48% of the available space */
            text-align: center;
        }
        .announcement-card, .rules-card {
            width: 48%;
        }
        .rules-card {
            overflow-y: auto;
            max-height: 489px; /* Adjust the height as needed */
        }
        .rules-card, .p {
            text-align: left;
        }
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                align-items: center;
            }
            .card {
                width: 100%; /* Full width on smaller screens */
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
        <div class="announcement-card card">
            <h3>Announcement</h3>
            <p><strong>CCS Admin | 2025-Feb-03</strong></p>
            <p>Birthday BAHAN</p>
            <hr>
            <p><strong>CCS Admin | 2024-May-08</strong></p>
            <p>Birthday Remart</p>
        </div>

        <div class="rules-card card">
            <h3>Rules and Regulations</h3>
            <p><strong>COLLEGE OF INFORMATION & COMPUTER STUDIES</strong></p>
            <p>To avoid embarrassment and maintain camaraderie with your friends and superiors at our laboratories, please observe the following:</p>
            <ol>
                <p>Maintain silence, proper decorum, and discipline inside the laboratory. Mobile phones, walkmans and other personal pieces of equipment must be switched off.</p>
                <p>Games are not allowed inside the lab. This includes computer-related games, card games and other games that may disturb the operation of the lab.</p>
                <p>Surfing the Internet is allowed only with the permission of the instructor. Downloading and installing of software are strictly prohibited.</p>
                <p>Getting access to other websites not related to the course (especially pornographic and illicit sites) is strictly prohibited.</p>
                <p>Deleting computer files and changing the set-up of the computer is a major offense.</p>
                <p>Observe computer time usage carefully. A fifteen-minute allowance is given for each use. Otherwise, the unit will be given to those who wish to "sit-in".</p>
                <p>Observe proper decorum while inside the laboratory.</p>
                <p>Do not get inside the lab unless the instructor is present.</p>
                <p>All bags, knapsacks, and the likes must be deposited at the counter.</p>
                <p>Follow the seating arrangement of your instructor.</p>
                <p>At the end of class, all software programs must be closed.</p>
                <p>Return all chairs to their proper places after using.</p>
                <p>Chewing gum, eating, drinking, smoking, and other forms of vandalism are prohibited inside the lab.</p>
                <p>Anyone causing a continual disturbance will be asked to leave the lab. Acts or gestures offensive to the members of the community, including public display of physical intimacy, are not tolerated.</p>
                <p>Persons exhibiting hostile or threatening behavior such as yelling, swearing, or disregarding requests made by lab personnel will be asked to leave the lab.</p>
                <p>For serious offenses, the lab personnel may call the Civil Security Office (CSU) for assistance.</p>
                <p>Any technical problem or difficulty must be addressed to the laboratory supervisor, student assistant or instructor immediately.</p>
            </ol>
            <p><strong>DISCIPLINARY ACTION</strong></p>
            <p>First Offense - The Head or the Dean or OIC recommends to the Guidance Center for a suspension from classes for each offender.</p>
            <p>Second and Subsequent Offenses - A recommendation for a heavier sanction will be endorsed to the Guidance Center.</p>
        </div>
    </div>

    <script>
        alert("Welcome! <?php echo addslashes($fullname); ?>");
    </script>
</body>
</html>
