<?php
session_start();
include 'db.php';

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    exit('Unauthorized');
}

$room = isset($_GET['room']) ? mysqli_real_escape_string($con, $_GET['room']) : '';

if (empty($room)) {
    http_response_code(400);
    exit('Room parameter is required');
}

// Get PC status for the room
$query = "SELECT pc_number, status FROM pc_status WHERE room = ?";
$stmt = mysqli_prepare($con, $query);
mysqli_stmt_bind_param($stmt, "s", $room);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$pc_status = array();
while ($row = mysqli_fetch_assoc($result)) {
    $pc_status[$row['pc_number']] = $row['status'];
}

// Also check for current reservations
$date = date('Y-m-d');
$time = date('H:i:s');
$reservation_query = "SELECT pc_number FROM reservations 
                     WHERE room = ? 
                     AND reservation_date = ? 
                     AND time_in <= ? 
                     AND status = 'approved'";
$reservation_stmt = mysqli_prepare($con, $reservation_query);
mysqli_stmt_bind_param($reservation_stmt, "sss", $room, $date, $time);
mysqli_stmt_execute($reservation_stmt);
$reservation_result = mysqli_stmt_get_result($reservation_stmt);

while ($row = mysqli_fetch_assoc($reservation_result)) {
    $pc_status[$row['pc_number']] = 'in_use';
}

// If no records, return empty object (frontend will treat all as available)
header('Content-Type: application/json');
echo json_encode($pc_status); 