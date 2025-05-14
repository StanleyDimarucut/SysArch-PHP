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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["reset_sessions"])) {
    $student_id = $_POST["student_id"];
    
    // Reset sessions back to 30
    $reset_query = "UPDATE register SET remaining_sessions = 30 WHERE IDNO = ?";
    $reset_stmt = mysqli_prepare($con, $reset_query);
    mysqli_stmt_bind_param($reset_stmt, "s", $student_id);
    mysqli_stmt_execute($reset_stmt);
    
    header("Location: student_list.php?success=Sessions reset successfully");
    exit();
}

// Add handler for resetting all sessions
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["reset_all_sessions"])) {
    // Reset all students' sessions to 30
    $reset_all_query = "UPDATE register SET remaining_sessions = 30 WHERE USERNAME != 'admin'";
    mysqli_query($con, $reset_all_query);
    
    header("Location: student_list.php?success=All sessions reset successfully");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS | Student List</title>
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

        .main-container {
            width: 95%;
            max-width: 1400px;
            margin: 20px auto;
            padding: 0;
        }

        .page-header {
            background: white;
            padding: 25px 30px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .page-header h1 {
            color: #1a5dba;
            font-size: 24px;
            margin: 0;
            font-weight: 600;
        }

        .search-container {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            margin-bottom: 24px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            padding: 12px;
            border: 1px solid #e5e9ef;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            min-width: 200px;
        }

        .search-box:focus {
            outline: none;
            border-color: #1a5dba;
            box-shadow: 0 0 0 3px rgba(26,93,186,0.1);
        }

        .filter-select {
            padding: 12px;
            border: 1px solid #e5e9ef;
            border-radius: 8px;
            font-size: 14px;
            min-width: 150px;
            background: white;
        }

        .card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.05);
            margin-bottom: 24px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e9ef;
        }

        th {
            background-color: #f8fafc;
            color: #1a5dba;
            font-weight: 600;
            font-size: 14px;
        }

        td {
            color: #444;
            font-size: 14px;
        }

        tr:hover {
            background-color: #f8fafc;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .status-active {
            background-color: #dcfce7;
            color: #166534;
        }

        .status-inactive {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .alert {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            z-index: 1000;
            animation: slideIn 0.5s ease-out forwards, fadeOut 0.5s ease-out 3s forwards;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-success {
            background-color: #28a745;
            color: white;
        }

        .btn-reset {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: none;
        }

        .btn-reset-individual {
            background-color: #1a5dba;
            color: white;
        }

        .btn-reset-individual:hover {
            background-color: #144c94;
            transform: translateY(-1px);
        }

        .btn-reset-all {
            background: linear-gradient(135deg, #144c94 0%, #1a5dba 100%);
            color: white;
            padding: 10px 20px;
            font-size: 14px;
            margin-bottom: 16px;
        }

        .btn-reset-all:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(26,93,186,0.2);
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
            }
            to {
                opacity: 0;
                visibility: hidden;
            }
        }

        @media (max-width: 1024px) {
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

            .nav-link {
                justify-content: center;
            }

            .search-container {
                flex-direction: column;
            }

            .search-box, .filter-select {
                width: 100%;
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
        <div class="page-header">
            <h1><i class="fas fa-users"></i> Student List</h1>
            <form method="POST" onsubmit="return confirmResetAll()">
                <button type="submit" name="reset_all_sessions" class="btn-reset btn-reset-all">
                    <i class="fas fa-sync-alt"></i> Reset All Sessions
                </button>
            </form>
        </div>

        <div class="search-container">
            <input type="text" id="searchInput" class="search-box" placeholder="Search by name, ID, or course...">
            <select class="filter-select" id="courseFilter">
                <option value="">All Courses</option>
                <option value="BSCS">BSCS</option>
                <option value="BSIT">BSIT</option>
                <option value="BSIS">BSIS</option>
            </select>
            <select class="filter-select" id="yearFilter">
                <option value="">All Years</option>
                <option value="1ST YEAR">1st Year</option>
                <option value="2ND YEAR">2nd Year</option>
                <option value="3RD YEAR">3rd Year</option>
                <option value="4TH YEAR">4th Year</option>
            </select>
        </div>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>ID Number</th>
                        <th>Name</th>
                        <th>Course</th>
                        <th>Year Level</th>
                        <th>Remaining Sessions</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $students_query = "SELECT IDNO, FIRSTNAME, LASTNAME, COURSE, YEARLEVEL, remaining_sessions FROM register WHERE USERNAME != 'admin' ORDER BY LASTNAME";
                    $students_result = mysqli_query($con, $students_query);
                    while ($student = mysqli_fetch_assoc($students_result)) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($student['IDNO']) . "</td>";
                        echo "<td>" . htmlspecialchars($student['FIRSTNAME'] . ' ' . $student['LASTNAME']) . "</td>";
                        echo "<td>" . htmlspecialchars($student['COURSE']) . "</td>";
                        echo "<td>" . htmlspecialchars($student['YEARLEVEL']) . "</td>";
                        echo "<td>" . htmlspecialchars($student['remaining_sessions']) . "</td>";
                        echo "<td>";
                        if ($student['remaining_sessions'] > 0) {
                            echo "<span class='status-badge status-active'><i class='fas fa-check-circle'></i> Active</span>";
                        } else {
                            echo "<span class='status-badge status-inactive'><i class='fas fa-times-circle'></i> Inactive</span>";
                        }
                        echo "</td>";
                        echo "<td>";
                        echo "<form method='POST' style='display: inline;' onsubmit='return confirmReset(\"" . htmlspecialchars($student['FIRSTNAME'] . ' ' . $student['LASTNAME']) . "\")'>";
                        echo "<input type='hidden' name='student_id' value='" . htmlspecialchars($student['IDNO']) . "'>";
                        echo "<button type='submit' name='reset_sessions' class='btn-reset btn-reset-individual'>";
                        echo "<i class='fas fa-sync-alt'></i> Reset Sessions";
                        echo "</button>";
                        echo "</form>";
                        echo "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if (isset($_GET["success"])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET["success"]); ?>
        </div>
    <?php endif; ?>

    <script>
        function confirmReset(studentName) {
            return confirm(`