<?php
session_start();
include("db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $subject = trim($_POST["subject"]);
    $message = trim($_POST["message"]);
    $room = trim($_POST["room"]);
    $purpose = trim($_POST["purpose"]);
    
    if (!empty($subject) && !empty($message) && !empty($room) && !empty($purpose)) {
        $username = $_SESSION["username"];
        
        // Get student ID from username
        $id_query = "SELECT IDNO FROM register WHERE USERNAME = ?";
        $stmt = mysqli_prepare($con, $id_query);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $student_id = $row['IDNO'];

        // Insert feedback with room and purpose
        $insert_query = "INSERT INTO feedback (student_id, subject, message, room, purpose) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($con, $insert_query);
        mysqli_stmt_bind_param($stmt, "issss", $student_id, $subject, $message, $room, $purpose);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(["success" => "Feedback submitted successfully"]);
        } else {
            echo json_encode(["error" => "Failed to submit feedback"]);
        }
    } else {
        echo json_encode(["error" => "All fields are required"]);
    }
} else {
    echo json_encode(["error" => "Invalid request method"]);
}
?>