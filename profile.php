<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

// Connect to the database
include("db.php");
$username = $_SESSION["username"];

// Handle Profile Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = $_POST["firstname"];
    $midname = $_POST["midname"];
    $lastname = $_POST["lastname"];
    $course = $_POST["course"];
    $year = $_POST["year"];

    // Handle file upload (profile picture)
    if (isset($_FILES["profile_img"]) && $_FILES["profile_img"]["error"] == 0) {
        $targetDir = "images/";
        $fileName = basename($_FILES["profile_img"]["name"]);
        $targetFilePath = $targetDir . $fileName;
        $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);

        // Allow only image file types
        $allowedTypes = ["jpg", "jpeg", "png", "gif"];
        if (in_array(strtolower($fileType), $allowedTypes)) {
            move_uploaded_file($_FILES["profile_img"]["tmp_name"], $targetFilePath);
        } else {
            echo "Invalid file type.";
            exit();
        }
    } else {
        // Keep existing image if no new file is uploaded
        $query = "SELECT PROFILE_IMG FROM register WHERE USERNAME=?";
        $stmt = mysqli_prepare($con, $query);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $targetFilePath = $row["PROFILE_IMG"];
    }

    // Update user details in the database
    $query = "UPDATE register SET FIRSTNAME=?, MIDNAME=?, LASTNAME=?, COURSE=?, YEARLEVEL=?, PROFILE_IMG=? WHERE USERNAME=?";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "sssssss", $firstname, $midname, $lastname, $course, $year, $targetFilePath, $username);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: profile.php?success=1");
        exit();
    } else {
        echo "Error updating profile.";
    }
}

// Fetch user details for display
$query = "SELECT FIRSTNAME, MIDNAME, LASTNAME, COURSE, YEARLEVEL, PROFILE_IMG FROM register WHERE USERNAME = ?";
$stmt = mysqli_prepare($con, $query);
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    $firstname = $row["FIRSTNAME"];
    $midname = $row["MIDNAME"];
    $lastname = $row["LASTNAME"];
    $course = $row["COURSE"];
    $year = $row["YEARLEVEL"];
    $profile_img = !empty($row["PROFILE_IMG"]) ? $row["PROFILE_IMG"] : "images/default.jpg"; // Default image
} else {
    echo "User not found!";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS | Edit Profile</title>
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
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        .card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .card h2 {
            color: #1a1a1a;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .profile-section {
            text-align: center;
            margin-bottom: 2rem;
        }

        .profile-img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #144c94;
            margin-bottom: 1rem;
        }

        .file-input-container {
            margin: 1rem 0;
        }

        .file-input-container input[type="file"] {
            width: 100%;
            max-width: 300px;
            padding: 0.5rem;
            margin: 0 auto;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-group input,
        .form-group select {
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.95rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #144c94;
        }

        .button-container {
            text-align: center;
            margin-top: 2rem;
        }

        .save-button {
            background-color: #144c94;
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .save-button:hover {
            background-color: #0d3a7d;
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
            margin: 15% auto;
            padding: 2rem;
            border-radius: 12px;
            width: 90%;
            max-width: 400px;
            text-align: center;
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

        .success-message {
            color: #28a745;
            font-size: 1.1rem;
            margin: 1rem 0;
        }

        @media (max-width: 768px) {
            .form-grid {
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
            <a href="feedback.php">Feedback</a>
            <a href="../php/login.php" style="color: #ffd700;">Log out</a>
        </div>
    </div>

    <div class="main-container">
        <div class="card">
            <h2>Edit Profile</h2>
            <form action="profile.php" method="POST" enctype="multipart/form-data">
                <div class="profile-section">
                    <img src="<?php echo htmlspecialchars($profile_img); ?>" alt="Profile Picture" class="profile-img">
                    <div class="file-input-container">
                        <input type="file" name="profile_img" accept="image/*">
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="firstname">First Name</label>
                        <input type="text" id="firstname" name="firstname" value="<?php echo htmlspecialchars($firstname); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="midname">Middle Name</label>
                        <input type="text" id="midname" name="midname" value="<?php echo htmlspecialchars($midname); ?>">
                    </div>

                    <div class="form-group">
                        <label for="lastname">Last Name</label>
                        <input type="text" id="lastname" name="lastname" value="<?php echo htmlspecialchars($lastname); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="course">Course</label>
                        <select id="course" name="course">
                            <option value="BSCS" <?php if ($course == "BSCS") echo "selected"; ?>>BSCS</option>
                            <option value="BSIT" <?php if ($course == "BSIT") echo "selected"; ?>>BSIT</option>
                            <option value="BSIS" <?php if ($course == "BSIS") echo "selected"; ?>>BSIS</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="year">Year Level</label>
                        <select id="year" name="year">
                            <option value="1" <?php if ($year == "1") echo "selected"; ?>>1st Year</option>
                            <option value="2" <?php if ($year == "2") echo "selected"; ?>>2nd Year</option>
                            <option value="3" <?php if ($year == "3") echo "selected"; ?>>3rd Year</option>
                            <option value="4" <?php if ($year == "4") echo "selected"; ?>>4th Year</option>
                        </select>
                    </div>
                </div>

                <div class="button-container">
                    <button type="submit" class="save-button">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div class="success-message">Profile updated successfully!</div>
        </div>
    </div>

    <script>
        var modal = document.getElementById("successModal");
        var span = document.getElementsByClassName("close")[0];

        <?php if (isset($_GET["success"])): ?>
            modal.style.display = "block";
        <?php endif; ?>

        span.onclick = function() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>
