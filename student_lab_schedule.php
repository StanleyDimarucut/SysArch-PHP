<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}
include("db.php");

// Enable error reporting for debugging
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// --- CONFIGURATION ---
$labs = [524, 526, 528, 530, 542, 544, 517];
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$start = strtotime('08:00');
$end = strtotime('21:00');
$time_slots = [];
for ($t = $start; $t < $end; $t += 3600) {
    $time_slots[] = date('H:i:s', $t);
}

// Calculate the Monday of the selected week
$reference_date = isset($_GET['refdate']) ? $_GET['refdate'] : date('Y-m-d');
$ref_ts = strtotime($reference_date);
$monday = date('Y-m-d', strtotime('monday this week', $ref_ts));
if (date('w', $ref_ts) == 0) { // If Sunday, go back 6 days
    $monday = date('Y-m-d', strtotime('-6 days', $ref_ts));
}
$day_dates = [];
foreach ($days as $i => $day) {
    $day_dates[$day] = date('Y-m-d', strtotime("+{$i} days", strtotime($monday)));
}

// --- GET SELECTED ROOM ---
$selected_room = isset($_GET['room']) && in_array($_GET['room'], array_map('strval', $labs)) ? $_GET['room'] : $labs[0];

// --- FETCH SCHEDULE FOR SELECTED ROOM AND WEEK ---
$schedule = [];
foreach ($days as $day) {
    $date_for_day = $day_dates[$day];
    $res = $con->query("SELECT time_slot, status FROM lab_schedule WHERE date='$date_for_day' AND lab='$selected_room'");
    while ($row = $res->fetch_assoc()) {
        $schedule[$day][$row['time_slot']] = $row['status'];
    }
    foreach ($time_slots as $slot) {
        if (!isset($schedule[$day][$slot])) {
            $schedule[$day][$slot] = 'available';
        }
    }
}

// Get the logged-in user's ID for notifications
$user_id = $_SESSION['IDNO'] ?? ($_SESSION['user_id'] ?? null);
$notif_result = false;
if ($user_id) {
    $notif_query = "SELECT * FROM notifications WHERE user_id = $user_id AND is_read = 0 ORDER BY created_at DESC";
    $notif_result = mysqli_query($con, $notif_query);
}

$notif_count = 0;
if ($notif_result) {
    $notif_count = mysqli_num_rows($notif_result);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS | Lab Schedule</title>
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
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
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

        .main-container {
            margin-top: 80px;
            padding: 2rem;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
            width: 95%;
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

        .schedule-container {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
        }

        h2 {
            color: #1a5dba;
            font-size: 24px;
            margin-bottom: 24px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .room-selector {
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .room-btn {
            background: #e6f0fa;
            color: #144c94;
            border: none;
            border-radius: 8px;
            padding: 8px 18px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .room-btn.active, .room-btn:hover {
            background: #144c94;
            color: #fff;
        }

        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .schedule-table th, .schedule-table td {
            padding: 12px 10px;
            text-align: center;
        }

        .schedule-table th {
            background: #e6f0fa;
            color: #1a5dba;
        }

        .slot {
            padding: 8px;
            border-radius: 8px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
        }

        .slot-available {
            background: #e6ffed;
            color: #166534;
        }

        .slot-occupied {
            background: #fee2e2;
            color: #991b1b;
        }

        .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }

        .dot.available {
            background: #22c55e;
        }

        .dot.occupied {
            background: #ef4444;
        }

        .week-selector {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .week-selector input[type="date"] {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }

        .week-selector button {
            background: #144c94;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            cursor: pointer;
            font-weight: 500;
        }

        .week-selector button:hover {
            background: #1a5dba;
        }

        @media (max-width: 1024px) {
            .main-container {
                padding: 1rem;
            }
            .navbar {
                padding: 1rem;
            }
            .navbar a {
                padding: 6px 12px;
                font-size: 0.9rem;
            }
            .schedule-table th, .schedule-table td {
                padding: 7px 2px;
                font-size: 13px;
            }
            .room-selector {
                justify-content: center;
            }
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
        <div class="schedule-container">
            <h2><i class="fas fa-calendar-alt"></i> Lab Schedule</h2>
            
            <form id="weekRoomForm" method="get" class="week-selector">
                <label for="refdate"><b>Week of:</b></label>
                <input type="date" id="refdate" name="refdate" value="<?php echo htmlspecialchars($reference_date); ?>">
                <input type="hidden" name="room" value="<?php echo $selected_room; ?>">
                <button type="submit">View Schedule</button>
            </form>

            <div class="room-selector">
                <?php foreach ($labs as $lab): ?>
                    <a href="?room=<?php echo $lab; ?>&refdate=<?php echo urlencode($reference_date); ?>" 
                       class="room-btn<?php if ($lab == $selected_room) echo ' active'; ?>">
                        Room <?php echo $lab; ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="schedule-table-wrapper">
                <table class="schedule-table">
                    <thead>
                        <tr>
                            <th>Time Slot</th>
                            <?php foreach ($days as $day): ?>
                                <th><?php echo $day; ?><br>
                                    <span style="font-size:12px;color:#1a5dba;">
                                        <?php echo date('M d', strtotime($day_dates[$day])); ?>
                                    </span>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($time_slots as $slot): ?>
                            <tr>
                                <td><?php echo date('g:i A', strtotime($slot)) . ' - ' . date('g:i A', strtotime($slot) + 3600); ?></td>
                                <?php foreach ($days as $day): ?>
                                    <?php $status = $schedule[$day][$slot] ?? 'available'; ?>
                                    <?php
                                    $label = 'Available';
                                    if (trim(strtolower($status)) === 'occupied') $label = 'Occupied';
                                    ?>
                                    <td>
                                        <div class="slot slot-<?php echo trim(strtolower($status)); ?>">
                                            <span class="dot <?php echo trim(strtolower($status)); ?>"></span>
                                            <?php echo $label; ?>
                                        </div>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
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