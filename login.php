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
    <title>CCS | Login</title>
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
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .main {
            background: white;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            width: 90%;
            max-width: 400px;
            text-align: center;
        }

        .logo-container {
            margin-bottom: 1.5rem;
        }

        .logo-container img {
            margin: 0 10px;
            height: 80px;
            width: auto;
        }

        h1 {
            color: #144c94;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 2rem;
        }

        .error-message {
            color: #dc3545;
            background-color: #ffe5e7;
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
            text-align: left;
        }

        label {
            display: block;
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.95rem;
            transition: border-color 0.3s ease;
        }

        input:focus {
            outline: none;
            border-color: #144c94;
        }

        .wrap {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        button {
            flex: 1;
            background-color: #144c94;
            color: white;
            padding: 0.75rem;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #0d3a7d;
        }

        .register-link {
            flex: 1;
            display: inline-block;
            background-color: #28a745;
            color: white;
            padding: 0.75rem;
            text-decoration: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            transition: background-color 0.3s ease;
        }

        .register-link:hover {
            background-color: #218838;
        }

        @media (max-width: 480px) {
            .main {
                padding: 1.5rem;
            }

            .wrap {
                flex-direction: column;
            }

            .logo-container img {
                height: 60px;
            }
        }
    </style>
</head>
<body>
    <div class="main">
        <div class="logo-container">
            <img src="uclogo.jpg" alt="UC Logo">
            <img src="css.png" alt="CSS Logo">
        </div>
        <h1>CCS Sitin Monitoring System</h1>

        <?php if (isset($_GET['error'])): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>

            <div class="wrap">
                <button type="submit">Login</button>
                <a href="register.php" class="register-link">Register</a>
            </div>
        </form>
    </div>
</body>
</html>
