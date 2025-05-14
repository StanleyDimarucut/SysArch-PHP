<?php

if ($stmt->execute()) {
    $message = "Feedback submitted successfully!";
    
    // Add notification for admin
    $notif_msg = "A new feedback has been submitted.";
    $notif_query = "INSERT INTO notifications (user_id, message, for_admin) VALUES (0, '$notif_msg', 1)";
    mysqli_query($con, $notif_query);
    
} else {
    $message = "Error submitting feedback: " . $con->error;
} 