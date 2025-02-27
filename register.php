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
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>HTML Registration Form</title>
    <link rel="stylesheet" href="style.css">   
</head>
<body>
    <div class="main2">
        <h2>Registration Form</h2>
        <form method="POST">
            <label for="idno">ID No:</label>
            <input type="text" id="idno" name="idno" required />

            <label for="last">Last Name:</label>
            <input type="text" id="lastname" name="lastname" required />

            <label for="first">First Name:</label>
            <input type="text" id="firstname" name="firstname" required />

            <label for="middle">Middle Name:</label>
            <input type="text" id="midname" name="midname" required />

            <label for="course">Course:</label>
            <input type="text" id="course" name="course" required />

            <label for="yearlevel">Year Level:</label>
            <input type="text" id="yearlvl" name="yearlvl" required />

            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required />

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required />

            <button type="submit">Register</button>
        </form>
    </div>
</body>
</html>
