<?php
session_start();
if (!isset($_SESSION["admin_username"])) {
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

// Calculate the Monday of the selected week using ISO week (Monday as start, even if refdate is Sunday)
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

// --- HANDLE SAVE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule']) && isset($_POST['room']) && isset($_POST['refdate'])) {
    $room = mysqli_real_escape_string($con, $_POST['room']);
    $reference_date = $_POST['refdate'];
    $ref_ts = strtotime($reference_date);
    $monday = date('Y-m-d', strtotime('monday this week', $ref_ts));
    if (date('w', $ref_ts) == 0) { // If Sunday, go back 6 days
        $monday = date('Y-m-d', strtotime('-6 days', $ref_ts));
    }
    $schedule = $_POST['schedule'];
    $debug = [];
    try {
        foreach ($schedule as $day => $slots) {
            $date_for_day = date('Y-m-d', strtotime("+" . array_search($day, $days) . " days", strtotime($monday)));
            foreach ($slots as $time => $status) {
                $time = mysqli_real_escape_string($con, $time);
                $status = mysqli_real_escape_string($con, $status);
                $sql = "INSERT INTO lab_schedule (lab, date, time_slot, status) VALUES ('$room', '$date_for_day', '$time', '$status') ON DUPLICATE KEY UPDATE status='$status'";
                $debug[] = $sql;
                $con->query($sql);
            }
        }
        echo json_encode(['success' => true, 'debug' => $debug]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage(), 'debug' => $debug]);
    }
    exit();
}

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lab Schedule Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f2f5;
        }

        .navbar {
            background: linear-gradient(135deg, #144c94 0%, #1a5dba 100%);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-brand {
            color: white;
            text-decoration: none;
            font-size: 1.3rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .navbar-brand:hover {
            background: rgba(255,255,255,0.1);
            transform: translateY(-1px);
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .nav-link {
            color: white;
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 8px 16px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.05);
        }

        .nav-link:hover {
            background: rgba(255,255,255,0.15);
            color: #ffd700;
            transform: translateY(-1px);
        }

        .nav-link i {
            font-size: 14px;
        }

        .nav-link.logout {
            background: rgba(255,217,0,0.15);
            color: #ffd700;
            border: 1px solid rgba(255,217,0,0.3);
        }

        .nav-link.logout:hover {
            background: rgba(255,217,0,0.25);
        }

        .mobile-menu-toggle {
            display: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        @media (max-width: 1024px) {
            .mobile-menu-toggle {
                display: block;
            }

            .nav-menu {
                display: none;
                width: 100%;
                padding: 15px 0;
                margin-top: 15px;
                border-top: 1px solid rgba(255,255,255,0.1);
            }

            .nav-menu.active {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 8px;
            }

            .navbar {
                flex-wrap: wrap;
            }

            .navbar-brand {
                flex: 1;
            }
        }
        .main-container { max-width: 1200px; margin: 30px auto; background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); padding: 32px; }
        h2 { margin-bottom: 18px; color: #144c94; }
        .room-selector { margin-bottom: 20px; }
        .room-btn { background: #e6f0fa; color: #144c94; border: none; border-radius: 8px; padding: 8px 18px; margin-right: 8px; font-weight: 600; cursor: pointer; }
        .room-btn.active, .room-btn:hover { background: #144c94; color: #fff; }
        .schedule-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .schedule-table th, .schedule-table td { padding: 12px 10px; text-align: center; }
        .schedule-table th { background: #e6f0fa; color: #1a5dba; }
        .slot-btn { border: none; border-radius: 8px; padding: 8px 0; width: 100%; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 7px; }
        .slot-available { background: #e6ffed; color: #166534; }
        .slot-occupied { background: #fee2e2; color: #991b1b; }
        .slot-maintenance { background: #fef3c7; color: #92400e; }
        .dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
        .dot.available { background: #22c55e; }
        .dot.occupied { background: #ef4444; }
        .dot.maintenance { background: #f59e0b; }
        .save-btn { background: #144c94; color: #fff; border: none; border-radius: 8px; padding: 14px 36px; font-size: 1.1rem; font-weight: 700; margin-top: 24px; cursor: pointer; }
        .success-msg { color: #22c55e; font-weight: 600; margin-left: 18px; font-size: 1.1rem; }
        @media (max-width: 900px) { .main-container { padding: 8px; } .schedule-table th, .schedule-table td { padding: 7px 2px; font-size: 13px; } }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="admin_dashboard.php" class="navbar-brand">
            <i class="fas fa-chart-line"></i>
            Admin Dashboard
        </a>
        <span class="mobile-menu-toggle" onclick="document.querySelector('.nav-menu').classList.toggle('active')">
            <i class="fas fa-bars"></i>
        </span>
        <div class="nav-menu">
            <a href="announcement.php" class="nav-link"><i class="fas fa-bullhorn"></i> Announcements</a>
            <a href="student_list.php" class="nav-link"><i class="fas fa-users"></i> Student List</a>
            <a href="view_feedback.php" class="nav-link"><i class="fas fa-comments"></i> Feedback</a>
            <a href="view_reservations.php" class="nav-link"><i class="fas fa-calendar-alt"></i> View Reservations</a>
            <a href="students.php" class="nav-link"><i class="fas fa-user-check"></i> Sit-in</a>
            <a href="sitin_view.php" class="nav-link"><i class="fas fa-clock"></i> Current Sit-in</a>
            <a href="session_history.php" class="nav-link"><i class="fas fa-history"></i> Reports</a>
            <a href="sitin_history.php" class="nav-link"><i class="fas fa-calendar-alt"></i> History</a>
            <a href="leaderboards.php" class="nav-link"><i class="fas fa-trophy"></i> Leaderboards</a>
            <a href="resources.php" class="nav-link"><i class="fas fa-book"></i> Resources</a>
            <a href="PC_management.php" class="nav-link"><i class="fas fa-desktop"></i> PC Management</a>
            <a href="lab_schedule.php" class="nav-link"><i class="fas fa-calendar-alt"></i> Lab Schedule</a>
            <a href="login.php" class="nav-link logout"><i class="fas fa-sign-out-alt"></i> Log out</a>
        </div>
    </div>
    <div class="main-container">
        <h2><i class="fas fa-calendar-alt"></i> Lab Schedule Management</h2>
        <form id="weekRoomForm" method="get" style="margin-bottom: 18px; display: flex; align-items: center; gap: 18px;">
            <label for="refdate"><b>Week of:</b></label>
            <input type="date" id="refdate" name="refdate" value="<?php echo htmlspecialchars($reference_date); ?>">
            <input type="hidden" name="room" value="<?php echo $selected_room; ?>">
            <button type="submit" class="save-btn" style="padding: 7px 18px; font-size: 14px;">Go</button>
        </form>
        <div class="room-selector">
            <?php foreach ($labs as $lab): ?>
                <a href="?room=<?php echo $lab; ?>&refdate=<?php echo urlencode($reference_date); ?>" class="room-btn<?php if ($lab == $selected_room) echo ' active'; ?>">Room <?php echo $lab; ?></a>
            <?php endforeach; ?>
        </div>
        <form id="scheduleForm">
            <input type="hidden" name="room" value="<?php echo $selected_room; ?>">
            <input type="hidden" name="refdate" value="<?php echo htmlspecialchars($reference_date); ?>">
            <div class="schedule-table-wrapper">
                <table class="schedule-table">
                    <thead>
                        <tr>
                            <th>Time Slot</th>
                            <?php foreach ($days as $day): ?>
                                <th><?php echo $day; ?><br><span style="font-size:12px;color:#1a5dba;"><?php echo date('M d', strtotime($day_dates[$day])); ?></span></th>
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
                                    <button type="button" class="slot-btn slot-<?php echo trim(strtolower($status)); ?>"
                                            data-day="<?php echo $day; ?>" data-time="<?php echo $slot; ?>" data-status="<?php echo trim(strtolower($status)); ?>">
                                        <span class="dot <?php echo trim(strtolower($status)); ?>"></span>
                                        <?php echo $label; ?>
                                    </button>
                                    <input type="hidden" name="schedule[<?php echo $day; ?>][<?php echo $slot; ?>]" value="<?php echo $status; ?>">
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button type="submit" class="save-btn"><i class="fas fa-save"></i> Save Schedule</button>
            <span id="successMsg" class="success-msg" style="display:none;">Saved!</span>
        </form>
    </div>
    <script>
    document.querySelectorAll('.slot-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var status = btn.getAttribute('data-status');
            var newStatus = (status === 'available') ? 'occupied' : 'available';
            btn.setAttribute('data-status', newStatus);
            btn.className = 'slot-btn slot-' + newStatus;
            let label = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
            btn.innerHTML = `<span class='dot ${newStatus}'></span> ${label}`;
            var input = btn.parentElement.querySelector('input[type="hidden"]');
            input.value = newStatus;
        });
    });
    document.getElementById('scheduleForm').addEventListener('submit', function(e) {
        e.preventDefault();
        var form = e.target;
        var formData = new FormData(form);
        fetch('lab_schedule.php?room=<?php echo $selected_room; ?>&refdate=<?php echo htmlspecialchars($reference_date); ?>', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                var msg = document.getElementById('successMsg');
                msg.style.display = 'inline';
                setTimeout(() => { msg.style.display = 'none'; }, 1200);
            }
        });
    });
    document.getElementById('weekRoomForm').addEventListener('submit', function(e) {
        // Let the form submit normally
    });
    </script>
</body>
</html>