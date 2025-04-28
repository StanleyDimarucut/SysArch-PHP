<?php
session_start();
include("db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $subject = trim($_POST["subject"]);
    $message = trim($_POST["message"]);
    $session_id = $_POST["session_id"];
    
    // First get the session details
    $session_query = "SELECT lab, purpose FROM sit_in_records WHERE id = ?";
    $stmt = mysqli_prepare($con, $session_query);
    mysqli_stmt_bind_param($stmt, "i", $session_id);
    mysqli_stmt_execute($stmt);
    $session_result = mysqli_stmt_get_result($stmt);
    $session = mysqli_fetch_assoc($session_result);

    if (!empty($subject) && !empty($message) && !empty($session['lab']) && !empty($session['purpose'])) {
        $student_id = $_SESSION["username"];
        
        // Get actual student ID from username
        $id_query = "SELECT IDNO FROM register WHERE USERNAME = ?";
        $stmt = mysqli_prepare($con, $id_query);
        mysqli_stmt_bind_param($stmt, "s", $student_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $student_id = $row['IDNO'];

        // Insert feedback with session details
        $insert_query = "INSERT INTO feedback (student_id, subject, message, room, purpose) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($con, $insert_query);
        mysqli_stmt_bind_param($stmt, "issss", $student_id, $subject, $message, $session['lab'], $session['purpose']);
        
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