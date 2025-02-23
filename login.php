<?php
session_start();
include("db.php"); // Ensure this correctly connects to your database

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize user input to prevent XSS
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    // Check if username and password are provided
    if (empty($username) || empty($password)) {
        header("Location: login.php?error=" . urlencode("Both fields are required."));
        exit();
    }

    // Use prepared statement to prevent SQL injection
    $query = "SELECT * FROM register WHERE USERNAME = ?";
    $stmt = mysqli_prepare($con, $query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            // Debugging output (Uncomment for testing)
            // echo "Stored Password (Hashed): " . $row["PASSWORD"] . "<br>";
            // echo "Entered Password: " . $password . "<br>";

            // Verify password using password_verify()
            if (password_verify($password, $row["PASSWORD"])) {
                $_SESSION["username"] = $username;
                header("Location: dashboard.php"); // Redirect to dashboard
                exit();
            } else {
                header("Location: login.php?error=" . urlencode("Invalid username or password."));
                exit();
            }
        } else {
            header("Location: login.php?error=" . urlencode("Invalid username or password."));
            exit();
        }
    } else {
        header("Location: login.php?error=" . urlencode("Database error. Try again later."));
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
