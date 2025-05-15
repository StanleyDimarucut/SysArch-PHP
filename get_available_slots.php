<?php
include 'db.php';
header('Content-Type: application/json');

$room = isset($_GET['room']) ? $_GET['room'] : '';
$date = isset($_GET['date']) ? $_GET['date'] : '';

$response = [];

if ($room && $date) {
    // 1. Get all time slots for this room and date where lab is available
    $slots_query = "SELECT time_slot FROM lab_schedule WHERE lab = '$room' AND date = '$date' AND status = 'available'";
    $slots_result = mysqli_query($con, $slots_query);

    $available_slots = [];
    while ($row = mysqli_fetch_assoc($slots_result)) {
        $available_slots[] = $row['time_slot'];
    }

    // 2. Get all available PCs in this room
    $pcs_query = "SELECT pc_number FROM pc_status WHERE room = '$room' AND status = 'available'";
    $pcs_result = mysqli_query($con, $pcs_query);

    $available_pcs = [];
    while ($row = mysqli_fetch_assoc($pcs_result)) {
        $available_pcs[] = $row['pc_number'];
    }

    // 3. Build response: for each available slot, list available PCs
    foreach ($available_slots as $slot) {
        // Optionally, you can also check if a PC is already reserved for this slot
        $pcs_for_slot = [];
        foreach ($available_pcs as $pc) {
            // Check if this PC is already reserved for this slot
            $res_query = "SELECT * FROM reservations WHERE room = '$room' AND pc_number = '$pc' AND reservation_date = '$date' AND time_in = '$slot'";
            $res_result = mysqli_query($con, $res_query);
            if (mysqli_num_rows($res_result) == 0) {
                $pcs_for_slot[] = $pc;
            }
        }
        if (!empty($pcs_for_slot)) {
            $response[$room][$slot] = $pcs_for_slot;
        }
    }
}

echo json_encode($response); 