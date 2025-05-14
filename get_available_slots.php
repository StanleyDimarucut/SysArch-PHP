<?php
include 'db.php';
header('Content-Type: application/json');

$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$room = isset($_GET['room']) ? $_GET['room'] : null;

// Define labs/rooms and time slots
$labs = [517, 524, 526, 528, 530, 542, 544];
$start = strtotime('08:00');
$end = strtotime('21:00');
$time_slots = [];
for ($t = $start; $t <= $end; $t += 3600) {
    $time_slots[] = date('H:i', $t);
}

// Get available labs (rooms) for the date
$available = [];
foreach ($labs as $lab) {
    if ($room && $lab != $room) continue;
    $available[$lab] = [];
    foreach ($time_slots as $slot) {
        // Check if slot is available in lab_schedule
        $sched_q = "SELECT status FROM lab_schedule WHERE lab='$lab' AND date='$date' AND time_slot='$slot'";
        $sched_r = mysqli_query($con, $sched_q);
        $sched_status = ($sched_r && $row = mysqli_fetch_assoc($sched_r)) ? $row['status'] : 'available';
        
        // Only proceed if the lab is available for this time slot
        if ($sched_status === 'available') {
            // Check if any PC in this room is already reserved at this time
            $pc_q = "SELECT pc_number FROM reservations 
                    WHERE room='$lab' 
                    AND reservation_date='$date' 
                    AND time_in='$slot'
                    AND status != 'rejected'"; // Exclude rejected reservations
            $pc_r = mysqli_query($con, $pc_q);
            $reserved_pcs = [];
            while ($row = mysqli_fetch_assoc($pc_r)) {
                $reserved_pcs[] = $row['pc_number'];
            }
            
            // Get PCs that are not under maintenance
            $maintenance_q = "SELECT pc_number FROM pc_status WHERE room='$lab' AND status='maintenance'";
            $maintenance_r = mysqli_query($con, $maintenance_q);
            $maintenance_pcs = [];
            while ($row = mysqli_fetch_assoc($maintenance_r)) {
                $maintenance_pcs[] = $row['pc_number'];
            }
            
            // Assume each room has PCs 1-50 (customize as needed)
            $pcs = [];
            for ($pc = 1; $pc <= 50; $pc++) {
                if (!in_array($pc, $reserved_pcs) && !in_array($pc, $maintenance_pcs)) {
                    $pcs[] = $pc;
                }
            }
            
            if (!empty($pcs)) {
                $available[$lab][$slot] = $pcs;
            }
        }
    }
}

echo json_encode($available); 