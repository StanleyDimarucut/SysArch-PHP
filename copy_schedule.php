<?php
session_start();
if (!isset($_SESSION["admin_username"])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

include("db.php");

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['schedule']) || !isset($data['from_date']) || !isset($data['to_date'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

$from_date = mysqli_real_escape_string($con, $data['from_date']);
$to_date = mysqli_real_escape_string($con, $data['to_date']);
$schedule = $data['schedule'];

// Begin transaction
$con->begin_transaction();

try {
    // Delete existing schedule for target date
    $con->query("DELETE FROM lab_schedule WHERE date='$to_date'");
    
    // Insert new schedule
    foreach ($schedule as $key => $status) {
        // Parse the key format: schedule[lab][time]
        preg_match('/schedule\[(\d+)\]\[(\d+:\d+)\]/', $key, $matches);
        if (count($matches) === 3) {
            $lab = mysqli_real_escape_string($con, $matches[1]);
            $time = mysqli_real_escape_string($con, $matches[2]);
            $status = mysqli_real_escape_string($con, $status);
            
            $con->query("INSERT INTO lab_schedule (lab, date, time_slot, status) 
                        VALUES ('$lab', '$to_date', '$time', '$status')");
        }
    }
    
    // If target date has maintenance slots, update PC statuses
    $maintenance_query = "SELECT lab FROM lab_schedule WHERE date='$to_date' AND status='maintenance'";
    $result = $con->query($maintenance_query);
    $maintenance_labs = [];
    while ($row = $result->fetch_assoc()) {
        $maintenance_labs[] = $row['lab'];
    }
    
    if (!empty($maintenance_labs)) {
        $labs_str = "'" . implode("','", $maintenance_labs) . "'";
        $con->query("INSERT INTO pc_status (room, pc_number, status) 
                    SELECT room, pc_number, 'maintenance' 
                    FROM (SELECT 1 as pc_number UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 
                          UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
                          UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15
                          UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION SELECT 20
                          UNION SELECT 21 UNION SELECT 22 UNION SELECT 23 UNION SELECT 24 UNION SELECT 25
                          UNION SELECT 26 UNION SELECT 27 UNION SELECT 28 UNION SELECT 29 UNION SELECT 30
                          UNION SELECT 31 UNION SELECT 32 UNION SELECT 33 UNION SELECT 34 UNION SELECT 35
                          UNION SELECT 36 UNION SELECT 37 UNION SELECT 38 UNION SELECT 39 UNION SELECT 40
                          UNION SELECT 41 UNION SELECT 42 UNION SELECT 43 UNION SELECT 44 UNION SELECT 45
                          UNION SELECT 46 UNION SELECT 47 UNION SELECT 48 UNION SELECT 49 UNION SELECT 50) as pcs
                    CROSS JOIN (SELECT DISTINCT room FROM pc_status WHERE room IN ($labs_str)) as rooms
                    ON DUPLICATE KEY UPDATE status='maintenance'");
    }
    
    $con->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $con->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 