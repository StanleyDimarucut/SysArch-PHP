<?php
session_start();
if (!isset($_SESSION["admin_username"])) {
    header("Location: login.php");
    exit();
}

include("db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["session_id"])) {
    $session_id = $_POST["session_id"];
    
    // Update the session status to 'absent'
    $update_query = "UPDATE sit_in_records SET status = 'absent' WHERE id = ? AND date = CURDATE()";
    $stmt = mysqli_prepare($con, $update_query);
    mysqli_stmt_bind_param($stmt, "i", $session_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => "Failed to end session"]);
    }
} else {
    echo json_encode(["success" => false, "error" => "Invalid request"]);
}
?> 