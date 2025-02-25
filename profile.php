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
    <title>Edit Profile</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color:rgb(230, 233, 241);
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
            justify-content: center;
            align-items: center;
            height: 80vh;
            flex-direction: column;
            padding: 20px;
        }
        .card {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            width: 50%;
            text-align: center;
            flex-grow: 1;
        }
        .profile-img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #144c94;
        }
        input, select {
            width: 100%;
            padding: 8px;
            margin: 5px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        button {
            background-color: #144c94;
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            border-radius: 5px;
        }
        button:hover {
            background-color: #0f3a6d;
        }
        .success-message {
            color: green;
            margin-bottom: 10px;
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
            <a href="login.php" style="color: orange;">Log out</a>
        </div>
    </div>

    <div class="container">
        <div class="card">
            <h3>Edit Profile</h3>
            
            <!-- Success Message -->
            <?php if (isset($_GET["success"])): ?>
                <p class="success-message">Profile updated successfully!</p>
            <?php endif; ?>

            <form action="profile.php" method="POST" enctype="multipart/form-data">
                <img src="<?php echo htmlspecialchars($profile_img); ?>" alt="Profile Picture" class="profile-img"><br>
                <input type="file" name="profile_img" accept="image/*"><br>

                <input type="text" name="firstname" value="<?php echo htmlspecialchars($firstname); ?>" required>
                <input type="text" name="midname" value="<?php echo htmlspecialchars($midname); ?>">
                <input type="text" name="lastname" value="<?php echo htmlspecialchars($lastname); ?>" required>
                
                <select name="course">
                    <option value="BSCS" <?php if ($course == "BSCS") echo "selected"; ?>>BSCS</option>
                    <option value="BSIT" <?php if ($course == "BSIT") echo "selected"; ?>>BSIT</option>
                    <option value="BSIS" <?php if ($course == "BSIS") echo "selected"; ?>>BSIS</option>
                </select>

                <select name="year">
                    <option value="1" <?php if ($year == "1") echo "selected"; ?>>1st Year</option>
                    <option value="2" <?php if ($year == "2") echo "selected"; ?>>2nd Year</option>
                    <option value="3" <?php if ($year == "3") echo "selected"; ?>>3rd Year</option>
                    <option value="4" <?php if ($year == "4") echo "selected"; ?>>4th Year</option>
                </select>

                <button type="submit">Save Changes</button>
            </form>
        </div>
    </div>
</body>
</html>
