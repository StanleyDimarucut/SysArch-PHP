<?php
    $con = mysqli_connect("localhost", "root", "", "sitin") or die(mysqli_error($con));

// Create or update notifications table
$sql = "CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    message TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_read TINYINT(1) DEFAULT 0,
    for_admin TINYINT(1) DEFAULT 0
)";

if (!$con->query($sql)) {
    die("Error creating notifications table: " . $con->error);
}

// Add for_admin column if it doesn't exist
$check_column = "SHOW COLUMNS FROM notifications LIKE 'for_admin'";
$result = $con->query($check_column);
if ($result->num_rows == 0) {
    $alter_sql = "ALTER TABLE notifications ADD COLUMN for_admin TINYINT(1) DEFAULT 0";
    $con->query($alter_sql);
}
?>
