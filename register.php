<?php
session_start();
include("db.php");

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    // Sanitize user input to prevent SQL injection
    $idno = mysqli_real_escape_string($con, $_POST["idno"]);
    $lastname = mysqli_real_escape_string($con, $_POST["lastname"]);
    $firstname = mysqli_real_escape_string($con, $_POST["firstname"]);
    $midname = mysqli_real_escape_string($con, $_POST["midname"]);
    $course = mysqli_real_escape_string($con, $_POST["course"]);
    $yearLevel = mysqli_real_escape_string($con, $_POST["yearlvl"]);
    $username = mysqli_real_escape_string($con, $_POST["username"]);
    $password = $_POST["password"];

    // Check if IDNO already exists
    $check_query = "SELECT * FROM register WHERE IDNO = ?";
    $stmt = mysqli_prepare($con, $check_query);
    mysqli_stmt_bind_param($stmt, "s", $idno);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        echo "<script>alert('Error: ID Number already exists!'); window.history.back();</script>";
        exit();
    }
    mysqli_stmt_close($stmt);

    // Encrypt password before storing
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Use a prepared statement to insert data
    $query = "INSERT INTO register (IDNO, LASTNAME, FIRSTNAME, MIDNAME, COURSE, YEARLEVEL, USERNAME, PASSWORD) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "ssssssss", $idno, $lastname, $firstname, $midname, $course, $yearLevel, $username, $hashedPassword);

    if (mysqli_stmt_execute($stmt)) {
        echo "<script>alert('Successfully Registered'); window.location.href = 'login.php';</script>";
    } else {
        echo "<script>alert('Registration Failed: " . mysqli_error($con) . "');</script>";
    }
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS | Registration</title>
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
            padding: 2rem 0;
        }

        .main2 {
            background: white;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            width: 90%;
            max-width: 500px;
        }

        h2 {
            color: #144c94;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 2rem;
            text-align: center;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
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

        button {
            width: 100%;
            background-color: #144c94;
            color: white;
            padding: 0.75rem;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 1rem;
        }

        button:hover {
            background-color: #0d3a7d;
        }

        .login-link {
            display: block;
            text-align: center;
            margin-top: 1rem;
            color: #144c94;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .login-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 640px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .main2 {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="main2">
        <h2>Student Registration</h2>
        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label for="idno">ID Number</label>
                    <input type="text" id="idno" name="idno" required>
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>

                <div class="form-group">
                    <label for="firstname">First Name</label>
                    <input type="text" id="firstname" name="firstname" required>
                </div>

                <div class="form-group">
                    <label for="midname">Middle Name</label>
                    <input type="text" id="midname" name="midname" required>
                </div>

                <div class="form-group">
                    <label for="lastname">Last Name</label>
                    <input type="text" id="lastname" name="lastname" required>
                </div>

                <div class="form-group">
                    <label for="course">Course</label>
                    <input type="text" id="course" name="course" required>
                </div>

                <div class="form-group">
                    <label for="yearlvl">Year Level</label>
                    <input type="text" id="yearlvl" name="yearlvl" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
            </div>

            <button type="submit">Register</button>
            <a href="login.php" class="login-link">Already have an account? Login here</a>
        </form>
    </div>
</body>
</html>
