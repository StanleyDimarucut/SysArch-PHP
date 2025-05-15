<?php
session_start();
if (!isset($_SESSION["admin_username"])) {
    header("Location: login.php");
    exit();
}

// Connect to the database
include("db.php");

// Fetch admin notifications
$admin_notif_query = "SELECT * FROM notifications WHERE for_admin = 1 AND is_read = 0 ORDER BY created_at DESC";
$admin_notif_result = mysqli_query($con, $admin_notif_query);
$admin_notif_count = $admin_notif_result ? mysqli_num_rows($admin_notif_result) : 0;

// Handle marking notification as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'], $_POST['notif_id'])) {
    $notif_id = intval($_POST['notif_id']);
    mysqli_query($con, "UPDATE notifications SET is_read = 1 WHERE id = $notif_id");
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Query to get active sit-in records with student details
$active_sessions_query = "SELECT 
    s.student_id,
    CONCAT(r.FIRSTNAME, ' ', r.LASTNAME) as full_name,
    s.purpose,
    s.lab,
    TIME_FORMAT(s.time_in, '%l:%i %p') as time_in,
    r.remaining_sessions
FROM sit_in_records s
JOIN register r ON s.student_id = r.IDNO
WHERE s.date = CURDATE() 
AND s.time_in IS NOT NULL 
AND s.time_out IS NULL
AND s.purpose IS NOT NULL 
AND s.lab IS NOT NULL
ORDER BY s.time_in DESC";

$result = mysqli_query($con, $active_sessions_query);

// Handle end session
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["end_session"])) {
    $student_id = $_POST["student_id"];
    
    // Start transaction
    mysqli_begin_transaction($con);
    
    try {
        // Update time_out in sit_in_records
        $update_query = "UPDATE sit_in_records 
                        SET time_out = CURRENT_TIME()
                        WHERE student_id = ? 
                        AND date = CURDATE() 
                        AND time_out IS NULL";
        
        $stmt = mysqli_prepare($con, $update_query);
        mysqli_stmt_bind_param($stmt, "s", $student_id);
        $result = mysqli_stmt_execute($stmt);
        
        if (!$result) {
            throw new Exception("Failed to update session status");
        }

        // Deduct one session from remaining_sessions
        $update_sessions = "UPDATE register 
                          SET remaining_sessions = remaining_sessions - 1 
                          WHERE IDNO = ? 
                          AND remaining_sessions > 0";
        $session_stmt = mysqli_prepare($con, $update_sessions);
        mysqli_stmt_bind_param($session_stmt, "s", $student_id);
        $session_result = mysqli_stmt_execute($session_stmt);
        
        if (!$session_result) {
            throw new Exception("Failed to update remaining sessions");
        }

        // Commit transaction
        mysqli_commit($con);
        
        // Redirect with success message
        header("Location: sitin_view.php?success=Session ended successfully");
        exit();
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($con);
        
        // Redirect with error message
        header("Location: sitin_view.php?error=" . urlencode($e->getMessage()));
        exit();
    }
}

// Handle start session
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["start_session"])) {
    $reservation_id = intval($_POST["reservation_id"]);
    // Get reservation details
    $res_query = "SELECT * FROM reservations WHERE id = $reservation_id";
    $res_result = mysqli_query($con, $res_query);
    if ($res_row = mysqli_fetch_assoc($res_result)) {
        // Insert into sit_in_records
        $insert_query = "INSERT INTO sit_in_records (student_id, date, purpose, lab, time_in)
                         VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($con, $insert_query);
        mysqli_stmt_bind_param(
            $stmt,
            "sssss",
            $res_row['user_id'],
            $res_row['reservation_date'],
            $res_row['purpose'],
            $res_row['room'],
            $res_row['time_in']
        );
        mysqli_stmt_execute($stmt);
        // Optionally, update reservation status to 'completed'
        mysqli_query($con, "UPDATE reservations SET status = 'completed' WHERE id = $reservation_id");
        header("Location: sitin_view.php?success=Session started for reservation.");
        exit();
    }
}

// Handle awarding a point
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["award_point"])) {
    $student_id = $_POST["student_id"];
    // Add 1 point
    $update_query = "UPDATE register SET points = points + 1 WHERE IDNO = ?";
    $stmt = mysqli_prepare($con, $update_query);
    mysqli_stmt_bind_param($stmt, "s", $student_id);
    mysqli_stmt_execute($stmt);

    // Check if points is now a multiple of 3
    $select_query = "SELECT points FROM register WHERE IDNO = ?";
    $select_stmt = mysqli_prepare($con, $select_query);
    mysqli_stmt_bind_param($select_stmt, "s", $student_id);
    mysqli_stmt_execute($select_stmt);
    $result = mysqli_stmt_get_result($select_stmt);
    $row = mysqli_fetch_assoc($result);
    $points = (int)$row['points'];

    if ($points >= 3 && $points % 3 == 0) {
        // Add 1 session (do NOT deduct points)
        $convert_query = "UPDATE register SET remaining_sessions = remaining_sessions + 1 WHERE IDNO = ?";
        $convert_stmt = mysqli_prepare($con, $convert_query);
        mysqli_stmt_bind_param($convert_stmt, "s", $student_id);
        mysqli_stmt_execute($convert_stmt);
        header("Location: sitin_view.php?success=1+point+awarded+and+1+session+added");
        exit();
    } else {
        header("Location: sitin_view.php?success=1+point+awarded");
        exit();
    }
}

// Query to get today's approved reservations that are not yet in sit_in_records
$approved_reservations_query = "
    SELECT 
        r.id, r.user_id, CONCAT(reg.FIRSTNAME, ' ', reg.LASTNAME) as full_name,
        r.purpose, r.room, r.pc_number, TIME_FORMAT(r.time_in, '%l:%i %p') as time_in
    FROM reservations r
    JOIN register reg ON r.user_id = reg.IDNO
    WHERE r.reservation_date = CURDATE()
      AND r.status = 'approved'
      AND NOT EXISTS (
          SELECT 1 FROM sit_in_records s
          WHERE s.student_id = r.user_id
            AND s.date = r.reservation_date
            AND s.time_in = r.time_in
      )
    ORDER BY r.time_in ASC
";
$approved_reservations_result = mysqli_query($con, $approved_reservations_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS | Current Sit-in</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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

        .nav-link.logout {
            background: rgba(255,217,0,0.15);
            color: #ffd700;
            border: 1px solid rgba(255,217,0,0.3);
        }

        .nav-link.logout:hover {
            background: rgba(255,217,0,0.25);
        }

        .container {
            width: 95%;
            max-width: 1400px;
            margin: 20px auto;
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .page-header h2 {
            color: #1a5dba;
            font-size: 24px;
            margin: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-box {
            padding: 12px;
            border: 1px solid #e5e9ef;
            border-radius: 8px;
            font-size: 14px;
            width: 250px;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .filter-box:focus {
            outline: none;
            border-color: #1a5dba;
            box-shadow: 0 0 0 3px rgba(26,93,186,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }

        th, td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid #e5e9ef;
            font-size: 14px;
        }

        th {
            background-color: #f8fafc;
            color: #1a5dba;
            font-weight: 600;
            white-space: nowrap;
        }

        td {
            color: #444;
        }

        tbody tr:hover {
            background-color: #f8fafc;
        }

        .btn-end-session {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 8px 20px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 8px rgba(220,53,69,0.08);
        }
        .btn-end-session:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(220,53,69,0.15);
        }

        .alert {
            padding: 16px 24px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 15px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-success {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .alert-danger {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .btn-approve {
            background: linear-gradient(135deg, #28a745 0%, #218838 100%);
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 8px 20px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 8px rgba(40,167,69,0.08);
        }
        .btn-approve:hover {
            background: linear-gradient(135deg, #218838 0%, #28a745 100%);
            box-shadow: 0 4px 12px rgba(40,167,69,0.15);
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 15px;
            }

            .nav-menu {
                display: none;
            }

            .container {
                width: 90%;
                padding: 16px;
            }

            .page-header {
                flex-direction: column;
                gap: 16px;
                align-items: flex-start;
            }

            .filter-box {
                width: 100%;
            }

            th, td {
                padding: 12px;
            }
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .notification-bell {
            position: absolute;
            top: 18px;
            right: 40px;
            display: inline-block;
            cursor: pointer;
            font-size: 22px;
            color: #fff;
            transition: color 0.2s;
            padding: 8px;
            border-radius: 8px;
            z-index: 1100;
        }

        .notification-bell:hover {
            color: #ffd700;
            background: rgba(255,255,255,0.1);
        }

        .notif-badge {
            position: absolute;
            top: 0;
            right: 0;
            background: #dc3545;
            color: #fff;
            border-radius: 50%;
            padding: 2px 7px;
            font-size: 12px;
            font-weight: bold;
        }

        .notif-dropdown {
            position: absolute;
            right: 15px;
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
            padding: 8px;
            border-bottom: 1px solid #ffe58f;
        }

        .notif-dropdown li:last-child {
            border-bottom: none;
        }

        .btn-mark-read {
            background: #1a5dba;
            color: white;
            border: none;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            margin-left: 8px;
        }

        .btn-mark-read:hover {
            background: #144c94;
        }

        @media (max-width: 1024px) {
            .notification-bell {
                top: 18px;
                right: 18px;
            }
        }

        .action-btns {
            display: flex;
            gap: 10px;
            align-items: center;
            justify-content: flex-start;
        }
        .btn-end-session, .btn-approve {
            min-width: 150px;
            text-align: center;
            font-size: 15px;
            padding: 10px 0;
        }
        @media (max-width: 600px) {
            .action-btns {
                flex-direction: column;
                gap: 8px;
            }
            .btn-end-session, .btn-approve {
                min-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="admin_dashboard.php" class="navbar-brand">
            <i class="fas fa-chart-line"></i>
            Admin Dashboard
        </a>
        <div class="notification-bell" onclick="toggleAdminNotifDropdown()">
            <i class="fas fa-bell"></i>
            <?php if ($admin_notif_count > 0): ?>
                <span class="notif-badge"><?php echo $admin_notif_count; ?></span>
            <?php endif; ?>
        </div>
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

    <div id="adminNotifDropdown" class="notif-dropdown" style="display:none;">
        <h4>Admin Notifications</h4>
        <ul>
            <?php
            if ($admin_notif_result && mysqli_num_rows($admin_notif_result) > 0) {
                while($notif = mysqli_fetch_assoc($admin_notif_result)): ?>
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
        <div class="container">
            <div class="page-header">
                <h2><i class="fas fa-clock"></i> Current Active Sessions</h2>
            </div>
            
            <?php if (isset($_GET["error"])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_GET["error"]); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET["success"])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET["success"]); ?>
                </div>
            <?php endif; ?>
            
            <input type="text" class="filter-box" id="filterInput" placeholder="Filter by name, ID, or purpose..." onkeyup="filterTable()">

            <table id="activeSessionsTable">
                <thead>
                    <tr>
                        <th>ID Number</th>
                        <th>Name</th>
                        <th>Purpose</th>
                        <th>Laboratory</th>
                        <th>Login Time</th>
                        <th>Remaining Sessions</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['purpose']); ?></td>
                            <td><?php echo htmlspecialchars($row['lab']); ?></td>
                            <td><?php echo htmlspecialchars($row['time_in']); ?></td>
                            <td><?php echo htmlspecialchars($row['remaining_sessions']); ?></td>
                            <td>
                                <div class="action-btns">
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($row['student_id']); ?>">
                                        <button type="submit" name="end_session" class="btn-end-session">
                                            <i class="fas fa-sign-out-alt"></i> End Session
                                        </button>
                                    </form>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($row['student_id']); ?>">
                                        <button type="submit" name="award_point" class="btn-approve" title="Award 1 point to this student" onclick="return confirm('Are you sure you want to award 1 point to this student?');">
                                            <i class="fas fa-plus-circle"></i> Award 1 Point
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>

            <?php if (mysqli_num_rows($approved_reservations_result) > 0): ?>
                <h3>Today's Approved Reservations</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Room</th>
                            <th>PC Number</th>
                            <th>Time In</th>
                            <th>Purpose</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($approved_reservations_result)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['room']); ?></td>
                                <td><?php echo htmlspecialchars($row['pc_number']); ?></td>
                                <td><?php echo htmlspecialchars($row['time_in']); ?></td>
                                <td><?php echo htmlspecialchars($row['purpose']); ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="reservation_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" name="start_session" class="btn-approve">Start Session</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function filterTable() {
            var input = document.getElementById("filterInput");
            var filter = input.value.toLowerCase();
            var table = document.getElementById("activeSessionsTable");
            var tr = table.getElementsByTagName("tr");

            for (var i = 1; i < tr.length; i++) {
                var td = tr[i].getElementsByTagName("td");
                var found = false;
                for (var j = 0; j < td.length; j++) {
                    var cell = td[j];
                    if (cell) {
                        var text = cell.textContent || cell.innerText;
                        if (text.toLowerCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                }
                tr[i].style.display = found ? "" : "none";
            }
        }

        function toggleAdminNotifDropdown() {
            var dropdown = document.getElementById('adminNotifDropdown');
            dropdown.style.display = (dropdown.style.display === 'none' || dropdown.style.display === '') ? 'block' : 'none';
        }

        document.addEventListener('click', function(event) {
            var bell = document.querySelector('.notification-bell');
            var dropdown = document.getElementById('adminNotifDropdown');
            if (!bell.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.style.display = 'none';
            }
        });
    </script>
</body>
</html>