<?php
session_start();
include("db.php"); // Ensure this correctly connects to your database

// Ensure the admin account exists
$adminUsername = "admin";
$adminPassword = "admin123"; // Default admin password
$hashedAdminPassword = password_hash($adminPassword, PASSWORD_DEFAULT);

// Check if admin exists
$checkAdminQuery = "SELECT * FROM register WHERE USERNAME = ?";
$stmt = mysqli_prepare($con, $checkAdminQuery);
mysqli_stmt_bind_param($stmt, "s", $adminUsername);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    // Admin does not exist, create it
    $insertAdminQuery = "INSERT INTO register (USERNAME, PASSWORD, LASTNAME, FIRSTNAME, MIDNAME, COURSE, YEARLEVEL) 
                         VALUES (?, ?, 'Admin', 'Administrator', 'Admin', 'N/A', 'N/A')";
    $stmt = mysqli_prepare($con, $insertAdminQuery);
    mysqli_stmt_bind_param($stmt, "ss", $adminUsername, $hashedAdminPassword);
    mysqli_stmt_execute($stmt);
}

// Handle Login Request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    if (empty($username) || empty($password)) {
        header("Location: login.php?error=" . urlencode("Both fields are required."));
        exit();
    }

    // Fetch user
    $query = "SELECT * FROM register WHERE USERNAME = ?";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $row["PASSWORD"])) {
            // Store session based on role
            if ($username === "admin") {
                $_SESSION["admin_username"] = $username;
                echo "Login Successful! Redirecting...";
                header("refresh:2; url=admin_dashboard.php"); // Redirect after 2 seconds
            } else {
                $_SESSION["username"] = $username;
                echo "Login Successful! Redirecting...";
                header("refresh:2; url=dashboard.php"); // Redirect after 2 seconds
            }
            exit();
        } else {
            echo "Invalid password!";
            exit();
        }
    } else {
        echo "Invalid username!";
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="main">
        <img src="uclogo.jpg" alt="uclogo" style="width: 150px;">
        <img src="css.png" alt="css" style="width: 130px;">
        <h1>CCS Sitin Monitoring System</h1>

        <!-- Display error message if exists -->
        <?php
        if (isset($_GET['error'])) {
            echo "<p style='color: red;'>" . htmlspecialchars($_GET['error']) . "</p>";
        }
        ?>

        <form action="login.php" method="POST">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" placeholder="Enter your Username" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" placeholder="Enter your Password" required>

            <div class="wrap">
                <button type="submit">Login</button>
                <a href="register.php" class="register-link">Register</a>
            </div>
        </form>
    </div>
</body>
</html>
