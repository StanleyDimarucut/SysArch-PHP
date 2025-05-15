<?php
session_start();
include 'db.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$message = '';

// Get the user's IDNO from the register table
$user_query = "SELECT IDNO, CONCAT(FIRSTNAME, ' ', MIDNAME, ' ', LASTNAME) AS full_name, COURSE, YEARLEVEL, PROFILE_IMG FROM register WHERE USERNAME = '$username'";
$user_result = mysqli_query($con, $user_query);
$user_row = mysqli_fetch_assoc($user_result);
$user_id = $user_row['IDNO'];
$fullname = $user_row['full_name'];
$course = $user_row['COURSE'];
$year = $user_row['YEARLEVEL'];
$profile_img = !empty($user_row['PROFILE_IMG']) ? htmlspecialchars($user_row['PROFILE_IMG']) : "images/default.jpg";

// Get notifications for the user
$notif_result = false;
if ($user_id) {
    $notif_query = "SELECT * FROM notifications WHERE user_id = $user_id AND is_read = 0 ORDER BY created_at DESC";
    $notif_result = mysqli_query($con, $notif_query);
}

$notif_count = 0;
if ($notif_result) {
    $notif_count = mysqli_num_rows($notif_result);
}

// Handle marking notification as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'], $_POST['notif_id'])) {
    $notif_id = intval($_POST['notif_id']);
    mysqli_query($con, "UPDATE notifications SET is_read = 1 WHERE id = $notif_id");
    header('Location: reservation.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room = mysqli_real_escape_string($con, $_POST['room']);
    $pc_number = mysqli_real_escape_string($con, $_POST['pc_number']);
    $reservation_date = mysqli_real_escape_string($con, $_POST['reservation_date']);
    $time_in = mysqli_real_escape_string($con, $_POST['time_in']);
    $purpose = mysqli_real_escape_string($con, $_POST['purpose']);

    // Check if PC is in maintenance or in use
    $pc_status_query = "SELECT status FROM pc_status WHERE room = '$room' AND pc_number = '$pc_number'";
    $pc_status_result = mysqli_query($con, $pc_status_query);
    $pc_status = mysqli_fetch_assoc($pc_status_result);

    if ($pc_status && ($pc_status['status'] === 'maintenance' || $pc_status['status'] === 'in_use')) {
        $message = "This PC is currently " . $pc_status['status'] . " and cannot be reserved.";
    } else {
        // Check if lab is scheduled for maintenance or occupied
        $lab_schedule_query = "SELECT status FROM lab_schedule 
                             WHERE lab = '$room' 
                             AND date = '$reservation_date' 
                             AND time_slot = '$time_in'";
        $lab_schedule_result = mysqli_query($con, $lab_schedule_query);
        $lab_schedule = mysqli_fetch_assoc($lab_schedule_result);

        if ($lab_schedule && ($lab_schedule['status'] === 'maintenance' || $lab_schedule['status'] === 'occupied')) {
            $message = "The lab is scheduled for " . $lab_schedule['status'] . " during this time.";
        } else {
            // Check if PC is already reserved for the selected time
            $check_query = "SELECT * FROM reservations 
                           WHERE room = '$room' 
                           AND pc_number = '$pc_number' 
                           AND reservation_date = '$reservation_date' 
                           AND time_in = '$time_in'";
            
            $check_result = mysqli_query($con, $check_query);

            if (mysqli_num_rows($check_result) > 0) {
                $message = "This PC is already reserved for the selected time.";
            } else {
                // Insert new reservation
                $insert_query = "INSERT INTO reservations (user_id, room, pc_number, reservation_date, time_in, purpose, status) 
                                VALUES ('$user_id', '$room', '$pc_number', '$reservation_date', '$time_in', '$purpose', 'pending')";
                
                if (mysqli_query($con, $insert_query)) {
                    $message = "Reservation submitted successfully!";
                    
                    // Add notification for admin
                    $notif_msg = "A new reservation has been made by " . $_SESSION['username'];
                    $notif_query = "INSERT INTO notifications (user_id, message, for_admin) VALUES (0, '$notif_msg', 1)";
                    mysqli_query($con, $notif_query);
                    
                } else {
                    $message = "Error submitting reservation: " . mysqli_error($con);
                }
            }
        }
    }
}

// Get user's existing reservations
$reservations_query = "SELECT * FROM reservations WHERE user_id = '$user_id' ORDER BY reservation_date DESC, time_in ASC";
$reservations_result = mysqli_query($con, $reservations_query);

// Get PC statuses for the selected room
$selected_room = isset($_GET['room']) ? $_GET['room'] : '';
$pc_status = array();

if ($selected_room) {
    $status_query = "SELECT pc_number, status FROM pc_status WHERE room = '$selected_room'";
    $status_result = mysqli_query($con, $status_query);
    while ($row = mysqli_fetch_assoc($status_result)) {
        $pc_status[$row['pc_number']] = $row['status'];
    }
}

// Get lab schedule for the selected date
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$lab_schedule = array();

if ($selected_room && $selected_date) {
    $schedule_query = "SELECT time_slot, status FROM lab_schedule WHERE lab = '$selected_room' AND date = '$selected_date'";
    $schedule_result = mysqli_query($con, $schedule_query);
    while ($row = mysqli_fetch_assoc($schedule_result)) {
        $lab_schedule[$row['time_slot']] = $row['status'];
    }
}

// Add this after the existing PHP code, before the HTML
// Define time slots
$time_slots = [];
$start = strtotime('08:00');
$end = strtotime('21:00');
for ($t = $start; $t < $end; $t += 3600) {
    $time_slots[] = date('H:i:s', $t);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PC Reservation System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }
        body {
            background-color: #f8fafc;
            color: #1e293b;
        }
        .navbar {
            background-color: #144c94;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }
        .navbar a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .navbar a:hover {
            background: rgba(255,255,255,0.15);
            color: #ffd700;
            transform: translateY(-1px);
        }
        .navbar a.logout {
            background: rgba(255,217,0,0.15);
            color: #ffd700;
            border: 1px solid rgba(255,217,0,0.3);
        }
        .navbar a.logout:hover {
            background: rgba(255,217,0,0.25);
        }
        .notification-bell {
            position: relative;
            display: inline-block;
            margin-left: 18px;
            cursor: pointer;
            font-size: 22px;
            color: #fff;
            transition: color 0.2s;
        }
        .notification-bell:hover {
            color: #ffd700;
        }
        .notif-badge {
            position: absolute;
            top: -6px;
            right: -8px;
            background: #dc3545;
            color: #fff;
            border-radius: 50%;
            padding: 2px 7px;
            font-size: 12px;
            font-weight: bold;
        }
        .notif-dropdown {
            position: absolute;
            right: 30px;
            top: 60px;
            background: #fffbe6;
            border: 1px solid #ffe58f;
            border-radius: 8px;
            min-width: 300px;
            z-index: 2000;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            padding: 16px 12px 12px 12px;
        }
        .notif-dropdown h4 {
            margin: 0 0 10px 0;
            color: #d48806;
            font-size: 16px;
        }
        .notif-dropdown ul {
            list-style: none;
            padding: 0;
            margin: 0;
            max-height: 250px;
            overflow-y: auto;
        }
        .notif-dropdown li {
            margin-bottom: 8px;
            font-size: 15px;
        }
        @media (max-width: 1024px) {
            .navbar {
                padding: 1rem;
            }
            .navbar a {
                padding: 6px 12px;
                font-size: 0.9rem;
            }
        }
        .main-container {
            margin-top: 100px;
            padding: 2rem;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
            width: 95%;
        }
        .card {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            border: 1px solid #e2e8f0;
        }
        .card h2 {
            color: #1a5dba;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #475569;
        }
        .form-group select, .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: #f8fafc;
        }
        .form-group select:focus, .form-group input:focus {
            outline: none;
            border-color: #1a5dba;
            box-shadow: 0 0 0 3px rgba(26,93,186,0.1);
        }
        .submit-btn {
            background: linear-gradient(135deg, #144c94 0%, #1a5dba 100%);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .submit-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(26,93,186,0.2);
        }
        .message {
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 8px;
            font-weight: 500;
        }
        .success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }
        .error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        .pc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        .pc-item {
            padding: 1rem;
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid #e2e8f0;
            background: white;
        }
        .pc-item.available {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            border-color: #22c55e;
            color: #166534;
        }
        .pc-item.in_use {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-color: #f59e0b;
            color: #92400e;
            cursor: not-allowed;
        }
        .pc-item.maintenance {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border-color: #ef4444;
            color: #991b1b;
            cursor: not-allowed;
        }
        .pc-item.selected {
            border-width: 3px;
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .pc-item:hover:not(.in_use):not(.maintenance) {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .pc-number {
            font-weight: 600;
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }
        .pc-status {
            font-size: 0.85rem;
            text-transform: capitalize;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            display: inline-block;
            background: rgba(255,255,255,0.5);
        }
        .profile-bar {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 2rem;
            background: white;
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }
        .profile-img {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #1a5dba;
            box-shadow: 0 4px 12px rgba(26,93,186,0.2);
        }
        .profile-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        .profile-info .name {
            font-weight: 600;
            color: #1a5dba;
            font-size: 1.25rem;
        }
        .profile-info .course {
            color: #64748b;
            font-size: 1rem;
        }
        .reservations-list table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 1rem;
        }
        .reservations-list th {
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            text-align: left;
            padding: 1rem;
            border-bottom: 2px solid #e2e8f0;
        }
        .reservations-list td {
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
            color: #475569;
        }
        .reservations-list tr:hover {
            background: #f8fafc;
        }
        @media (max-width: 768px) {
            .navbar {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
            }
            .navbar > div {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
                justify-content: center;
            }
            .main-container {
                margin-top: 140px;
                padding: 1rem;
            }
            .pc-grid {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            }
            .profile-bar {
                flex-direction: column;
                text-align: center;
                padding: 1rem;
            }
            .reservations-list {
                overflow-x: auto;
            }
        }
        .room-status {
            padding: 8px 12px;
            border-radius: 6px;
            font-weight: 500;
            display: none;
        }
        .room-status.available {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
            display: block;
        }
        .room-status.occupied {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
            display: block;
        }
        .room-status.maintenance {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
            display: block;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="#" style="font-size: 1.2rem; font-weight: 600;"><i class="fas fa-home"></i> Student Dashboard</a>
        <div>
            <a href="dashboard.php"><i class="fas fa-home"></i> Home</a>
            <a href="profile.php"><i class="fas fa-user-edit"></i> Edit Profile</a>
            <a href="student_lab_schedule.php"><i class="fas fa-calendar-alt"></i> Lab Schedule</a>
            <a href="history.php"><i class="fas fa-history"></i> History</a>
            <a href="student_resources.php"><i class="fas fa-book"></i> Student Resources</a>
            <a href="Reservation.php"><i class="fas fa-calendar-plus"></i> Reservation</a>
            <a href="login.php" class="logout"><i class="fas fa-sign-out-alt"></i> Log out</a>
        </div>
        <div class="notification-bell" onclick="toggleNotifDropdown()">
            <i class="fas fa-bell"></i>
            <?php if ($notif_count > 0): ?>
                <span class="notif-badge"><?php echo $notif_count; ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div id="notifDropdown" class="notif-dropdown" style="display:none;">
        <h4>Notifications</h4>
        <ul>
            <?php
            if ($notif_result && mysqli_num_rows($notif_result) > 0) {
                mysqli_data_seek($notif_result, 0);
                while($notif = mysqli_fetch_assoc($notif_result)): ?>
                    <li>
                        <?php echo htmlspecialchars($notif['message']); ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="notif_id" value="<?php echo $notif['id']; ?>">
                            <button type="submit" name="mark_read" class="btn-mark-read">Mark as read</button>
                        </form>
                    </li>
                <?php endwhile;
            } else {
                echo '<li>No new notifications.</li>';
            }
            ?>
        </ul>
    </div>
    <div class="main-container">
        <div class="profile-bar">
            <img src="<?php echo $profile_img; ?>" alt="Profile" class="profile-img">
            <div class="profile-info">
                <span class="name"><?php echo htmlspecialchars($fullname); ?></span>
                <span class="course"><?php echo htmlspecialchars($course); ?> | Year <?php echo htmlspecialchars($year); ?></span>
            </div>
        </div>
        <div class="card">
            <h2><i class="fas fa-desktop"></i> Make a Reservation</h2>
            <?php if ($message && strpos($message, 'already reserved for the selected time') === false): ?>
                <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="room">Select Room:</label>
                    <select name="room" id="room" required onchange="updateTimeSlots()">
                        <option value="">Select a room</option>
                        <option value="524">524</option>
                        <option value="526">526</option>
                        <option value="528">528</option>
                        <option value="530">530</option>
                        <option value="542">542</option>
                        <option value="544">544</option>
                        <option value="517">517</option>
                    </select>
                    <div id="roomStatus" class="room-status" style="margin-top: 8px; font-size: 0.9rem;"></div>
                </div>
                <div class="form-group">
                    <label for="reservation_date">Select Date:</label>
                    <input type="date" name="reservation_date" id="reservation_date" required 
                           min="<?php echo date('Y-m-d'); ?>" onchange="updateTimeSlots()">
                </div>
                <div class="form-group">
                    <label for="time_in">Time In:</label>
                    <select name="time_in" id="time_in" required onchange="updatePCGrid()">
                        <option value="">Select Time</option>
                        <!-- Time slots will be populated dynamically -->
                    </select>
                </div>
                <div class="form-group">
                    <label>Select PC:</label>
                    <div class="pc-grid" id="pcGrid">
                        <!-- PC items will be dynamically populated here -->
                    </div>
                    <input type="hidden" name="pc_number" id="selectedPC" required>
                </div>
                <div class="form-group">
                    <label for="purpose">Purpose of Use:</label>
                    <select name="purpose" id="purpose" required>
                        <option value="">Select Purpose</option>
                        <option value="C Programming">C Programming</option>
                        <option value="C#">C#</option>
                        <option value="Java">Java</option>
                        <option value="PHP">PHP</option>
                        <option value="Database">Database</option>
                        <option value="Digital Logic & Design">Digital Logic & Design</option>
                        <option value="Embeded Systems & IoT">Embeded Systems & IoT</option>
                        <option value="Python Programming">Python Programming</option>
                        <option value="Systems Integration & Architecture">Systems Integration & Architecture</option>
                        <option value="Computer Application">Computer Application</option>
                        <option value="Web Design & Development">Web Design & Development</option>
                        <option value="Project Management">Project Management</option>
                    </select>
                </div>
                <button type="submit" class="submit-btn">Make Reservation</button>
            </form>
        </div>
        <div class="card reservations-list">
            <h2><i class="fas fa-list"></i> Your Reservations</h2>
            <?php if (mysqli_num_rows($reservations_result) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Room</th>
                            <th>PC Number</th>
                            <th>Date</th>
                            <th>Time In</th>
                            <th>Purpose</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($reservations_result)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['room']); ?></td>
                                <td><?php echo htmlspecialchars($row['pc_number']); ?></td>
                                <td><?php echo htmlspecialchars($row['reservation_date']); ?></td>
                                <td><?php echo htmlspecialchars($row['time_in']); ?></td>
                                <td><?php echo htmlspecialchars($row['purpose']); ?></td>
                                <td><?php echo htmlspecialchars($row['status']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>You have no reservations yet.</p>
            <?php endif; ?>
        </div>
    </div>
    <script>
    function updateTimeSlots() {
        const room = document.getElementById('room').value;
        const date = document.getElementById('reservation_date').value;
        const timeSelect = document.getElementById('time_in');
        const roomStatus = document.getElementById('roomStatus');
        
        if (!room || !date) {
            timeSelect.innerHTML = '<option value="">Select Time</option>';
            roomStatus.className = 'room-status';
            return;
        }

        // Fetch available time slots
        fetch(`get_available_slots.php?date=${date}&room=${room}`)
            .then(response => response.json())
            .then(data => {
                timeSelect.innerHTML = '<option value="">Select Time</option>';
                
                // Define time slots
                const timeSlots = [
                    '08:00:00', '09:00:00', '10:00:00', '11:00:00', 
                    '12:00:00', '13:00:00', '14:00:00', '15:00:00', 
                    '16:00:00', '17:00:00', '18:00:00', '19:00:00', 
                    '20:00:00'
                ];

                timeSlots.forEach(time => {
                    const option = document.createElement('option');
                    option.value = time;
                    
                    // Format time for display (e.g., "8:00 AM - 9:00 AM")
                    const startTime = new Date(`2000-01-01T${time}`);
                    const endTime = new Date(startTime.getTime() + 3600000); // Add 1 hour
                    
                    const formattedStart = startTime.toLocaleTimeString('en-US', { 
                        hour: 'numeric', 
                        minute: '2-digit', 
                        hour12: true 
                    });
                    const formattedEnd = endTime.toLocaleTimeString('en-US', { 
                        hour: 'numeric', 
                        minute: '2-digit', 
                        hour12: true 
                    });
                    
                    option.textContent = `${formattedStart} - ${formattedEnd}`;
                    
                    // Check if this time slot is available
                    if (data[room] && data[room][time]) {
                        timeSelect.appendChild(option);
                    } else {
                        option.disabled = true;
                        option.textContent += ' (Not Available)';
                        timeSelect.appendChild(option);
                    }
                });

                // Update room status
                if (Object.keys(data[room] || {}).length > 0) {
                    roomStatus.className = 'room-status available';
                    roomStatus.textContent = 'Room is available for selected date';
                } else {
                    roomStatus.className = 'room-status occupied';
                    roomStatus.textContent = 'Room is not available for selected date';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                roomStatus.className = 'room-status maintenance';
                roomStatus.textContent = 'Unable to check room availability';
            });
    }

    function updatePCGrid() {
        const room = document.getElementById('room').value;
        const date = document.getElementById('reservation_date').value;
        const time = document.getElementById('time_in').value;
        const pcGrid = document.getElementById('pcGrid');
        
        if (!room || !date || !time) {
            pcGrid.innerHTML = '';
            return;
        }

        // Fetch available PCs
        fetch(`get_available_slots.php?date=${date}&room=${room}`)
            .then(response => response.json())
            .then(data => {
                pcGrid.innerHTML = '';
                if (data[room] && data[room][time]) {
                    data[room][time].forEach(pc => {
                        const pcItem = document.createElement('div');
                        pcItem.className = 'pc-item available';
                        pcItem.innerHTML = `
                            <div class="pc-number">PC ${pc}</div>
                            <div class="pc-status">Available</div>
                        `;
                        pcItem.onclick = () => selectPC(pc, pcItem);
                        pcGrid.appendChild(pcItem);
                    });
                }
            })
            .catch(error => console.error('Error:', error));
    }

    function selectPC(pcNumber, element) {
        // Remove selected class from all PC items
        document.querySelectorAll('.pc-item').forEach(item => {
            item.classList.remove('selected');
        });
        
        // Add selected class to clicked PC
        element.classList.add('selected');
        
        // Update hidden input
        document.getElementById('selectedPC').value = pcNumber;
    }

    // Initialize time slots when page loads
    document.addEventListener('DOMContentLoaded', function() {
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('reservation_date').value = today;
        updateTimeSlots();
    });

    function checkAvailability(room, pcNumber, date, time) {
        // Check PC status
        const pcStatus = <?php echo json_encode($pc_status); ?>;
        if (pcStatus[pcNumber] === 'maintenance' || pcStatus[pcNumber] === 'in_use') {
            alert('This PC is currently ' + pcStatus[pcNumber] + ' and cannot be reserved.');
            return false;
        }

        // Check lab schedule
        const labSchedule = <?php echo json_encode($lab_schedule); ?>;
        if (labSchedule[time] === 'maintenance' || labSchedule[time] === 'occupied') {
            alert('The lab is scheduled for ' + labSchedule[time] + ' during this time.');
            return false;
        }

        return true;
    }

    document.getElementById('reservationForm').addEventListener('submit', function(e) {
        const room = document.getElementById('room').value;
        const pcNumber = document.getElementById('pc_number').value;
        const date = document.getElementById('reservation_date').value;
        const time = document.getElementById('time_in').value;

        if (!checkAvailability(room, pcNumber, date, time)) {
            e.preventDefault();
        }
    });

    function toggleNotifDropdown() {
        var dropdown = document.getElementById('notifDropdown');
        dropdown.style.display = (dropdown.style.display === 'none' || dropdown.style.display === '') ? 'block' : 'none';
    }
    document.addEventListener('click', function(event) {
        var bell = document.querySelector('.notification-bell');
        var dropdown = document.getElementById('notifDropdown');
        if (!bell.contains(event.target) && !dropdown.contains(event.target)) {
            dropdown.style.display = 'none';
        }
    });
    </script>
</body>
</html>