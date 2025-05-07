<?php
session_start();
if (!isset($_SESSION["admin_username"])) {
    header("Location: login.php");
    exit();
}

// Connect to the database
include("db.php");

// Handle sit-in status updates
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["student_id"]) && isset($_POST["status"])) {
    $student_id = $_POST["student_id"];
    $status = $_POST["status"];
    $date = date('Y-m-d');
    
    // Check if record exists for today
    $check_query = "SELECT * FROM sit_in_records WHERE student_id = ? AND date = ?";
    $check_stmt = mysqli_prepare($con, $check_query);
    mysqli_stmt_bind_param($check_stmt, "ss", $student_id, $date);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) > 0) {
        // Update existing record
        $update_query = "UPDATE sit_in_records SET status = ? WHERE student_id = ? AND date = ?";
        $update_stmt = mysqli_prepare($con, $update_query);
        mysqli_stmt_bind_param($update_stmt, "sss", $status, $student_id, $date);
        mysqli_stmt_execute($update_stmt);
    } else {
        // Insert new record
        $insert_query = "INSERT INTO sit_in_records (student_id, status, date) VALUES (?, ?, ?)";
        $insert_stmt = mysqli_prepare($con, $insert_query);
        mysqli_stmt_bind_param($insert_stmt, "sss", $student_id, $status, $date);
        mysqli_stmt_execute($insert_stmt);
    }
    
    header("Location: students.php");
    exit();
}

// Handle sit-in form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["sitin_submit"])) {
    $student_id = $_POST["id_number"];
    $purpose = $_POST["purpose"];
    $lab = $_POST["lab"];
    
    // Check if student has remaining sessions
    $sessions_query = "SELECT remaining_sessions FROM register WHERE IDNO = ?";
    $sessions_stmt = mysqli_prepare($con, $sessions_query);
    mysqli_stmt_bind_param($sessions_stmt, "s", $student_id);
    mysqli_stmt_execute($sessions_stmt);
    $sessions_result = mysqli_stmt_get_result($sessions_stmt);
    $sessions_row = mysqli_fetch_assoc($sessions_result);
    
    if ($sessions_row['remaining_sessions'] <= 0) {
        header("Location: students.php?error=No remaining sessions available");
        exit();
    }

    // Check if student already has a session today
    $check_session = "SELECT * FROM sit_in_records WHERE student_id = ? AND date = CURDATE() AND time_out IS NULL";
    $check_stmt = mysqli_prepare($con, $check_session);
    mysqli_stmt_bind_param($check_stmt, "s", $student_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);

    if (mysqli_num_rows($check_result) > 0) {
        header("Location: students.php?error=Student already has an active session today");
        exit();
    }

    // Begin transaction
    mysqli_begin_transaction($con);
    
    try {
        // Insert sit-in record with current time
        $insert_query = "INSERT INTO sit_in_records (student_id, date, purpose, lab, time_in) VALUES (?, CURDATE(), ?, ?, CURRENT_TIME())";
        $insert_stmt = mysqli_prepare($con, $insert_query);
        mysqli_stmt_bind_param($insert_stmt, "sss", $student_id, $purpose, $lab);
        
        if (!mysqli_stmt_execute($insert_stmt)) {
            throw new Exception("Failed to create sit-in record");
        }

        mysqli_commit($con);
        header("Location: students.php?success=Student successfully logged in");
        exit();
    } catch (Exception $e) {
        mysqli_rollback($con);
        header("Location: students.php?error=" . urlencode($e->getMessage()));
        exit();
    }
}

// Handle session reset
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["reset_sessions"])) {
    $student_id = $_POST["student_id"];
    
    // Reset sessions back to 30
    $reset_query = "UPDATE register SET remaining_sessions = 30 WHERE IDNO = ?";
    $reset_stmt = mysqli_prepare($con, $reset_query);
    mysqli_stmt_bind_param($reset_stmt, "s", $student_id);
    mysqli_stmt_execute($reset_stmt);
    
    header("Location: students.php?success=Sessions reset successfully");
    exit();
}

// Get Statistics
$total_students_query = "SELECT COUNT(*) as total FROM register WHERE USERNAME != 'admin'";
$total_result = mysqli_query($con, $total_students_query);
$total_students = mysqli_fetch_assoc($total_result)['total'];

$current_sitin_query = "SELECT COUNT(*) as current FROM sit_in_records WHERE date = CURDATE() AND time_in IS NOT NULL AND time_out IS NULL";
$current_result = mysqli_query($con, $current_sitin_query);
$current_sitin = mysqli_fetch_assoc($current_result)['current'];

$total_sitin_query = "SELECT COUNT(*) as total FROM sit_in_records WHERE time_out IS NOT NULL";
$total_sitin_result = mysqli_query($con, $total_sitin_query);
$total_sitin = mysqli_fetch_assoc($total_sitin_result)['total'];

// Get course statistics for pie chart
$course_stats_query = "SELECT COURSE, COUNT(*) as count FROM register WHERE USERNAME != 'admin' GROUP BY COURSE";
$course_stats_result = mysqli_query($con, $course_stats_query);
$course_data = [];
$course_labels = [];
$course_counts = [];
while($row = mysqli_fetch_assoc($course_stats_result)) {
    $course_labels[] = $row['COURSE'];
    $course_counts[] = $row['count'];
}

// Initialize variables
$search_query = "";
$search_result = null;

// Modify search query to get full student details
if (isset($_GET["search"]) && !empty(trim($_GET["search"]))) {
    $search_query = trim($_GET["search"]);
    $search_sql = "SELECT *, remaining_sessions FROM register WHERE USERNAME != 'admin' AND IDNO LIKE ?";
    $search_stmt = mysqli_prepare($con, $search_sql);
    $search_param = "%$search_query%";
    mysqli_stmt_bind_param($search_stmt, "s", $search_param);
    mysqli_stmt_execute($search_stmt);
    $search_result = mysqli_stmt_get_result($search_stmt);
}

// Fetch all students or filter by search
$search_query = "";
if (isset($_GET["search"]) && !empty(trim($_GET["search"]))) {
    $search_query = trim($_GET["search"]);
    $query = "SELECT r.*, COALESCE(s.status, 'absent') as sit_in_status 
              FROM register r 
              LEFT JOIN sit_in_records s ON r.IDNO = s.student_id AND s.date = CURDATE()
              WHERE r.USERNAME != 'admin' AND r.IDNO LIKE ?";
    $stmt = mysqli_prepare($con, $query);
    $search_param = "%$search_query%";
    mysqli_stmt_bind_param($stmt, "s", $search_param);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $query = "SELECT r.*, COALESCE(s.status, 'absent') as sit_in_status 
              FROM register r 
              LEFT JOIN sit_in_records s ON r.IDNO = s.student_id AND s.date = CURDATE()
              WHERE r.USERNAME != 'admin'";
    $result = mysqli_query($con, $query);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS | Students</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .container {
            width: 95%;
            max-width: 1400px;
            margin: 20px auto;
            padding: 0;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-box {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            transition: transform 0.3s ease;
        }

        .stat-box:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #1a5dba;
            margin: 0;
        }

        .stat-label {
            color: #666;
            font-size: 15px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .chart-container {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            margin-bottom: 24px;
            width: 100%;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
            height: 400px;
        }

        .search-section {
            display: flex;
            justify-content: center;
            margin: 24px 0;
        }

        .btn-modal {
            background: linear-gradient(135deg, #144c94 0%, #1a5dba 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-modal:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(26,93,186,0.2);
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }

        .modal-content {
            background: white;
            padding: 32px;
            border-radius: 16px;
            width: 90%;
            max-width: 500px;
            position: relative;
            margin: 50px auto;
            box-shadow: 0 4px 24px rgba(0,0,0,0.1);
        }

        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 24px;
            color: #666;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .modal-close:hover {
            color: #dc3545;
        }

        .search-box {
            width: 100%;
            padding: 12px;
            border: 1px solid #e5e9ef;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .search-box:focus {
            outline: none;
            border-color: #1a5dba;
            box-shadow: 0 0 0 3px rgba(26,93,186,0.1);
        }

        .sitin-form {
            margin-top: 24px;
        }

        .sitin-form h4 {
            color: #1a5dba;
            font-size: 18px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #444;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #e5e9ef;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #1a5dba;
            box-shadow: 0 0 0 3px rgba(26,93,186,0.1);
        }

        .form-group input[readonly] {
            background-color: #f8fafc;
            color: #666;
        }

        .btn-container {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 24px;
        }

        .btn-close {
            background-color: #e5e9ef;
            color: #444;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-close:hover {
            background-color: #d1d5db;
        }

        .btn-sitin {
            background: linear-gradient(135deg, #144c94 0%, #1a5dba 100%);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-sitin:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(26,93,186,0.2);
        }

        .btn-reset {
            background-color: #28a745;
            color: white;
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 16px;
        }

        .btn-reset:hover {
            background-color: #218838;
            transform: translateY(-1px);
        }

        .alert {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 16px 24px;
            border-radius: 8px;
            font-size: 15px;
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

        .alert-error {
            background-color: #dc3545;
            color: white;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; visibility: hidden; }
        }

        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: 1fr;
            }

            .modal-content {
                width: 95%;
                padding: 20px;
                margin: 20px auto;
            }

            .btn-container {
                flex-direction: column;
            }

            .btn-close, .btn-sitin {
                width: 100%;
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
        <div class="nav-menu">
            <a href="announcement.php" class="nav-link"><i class="fas fa-bullhorn"></i> Announcements</a>
            <a href="student_list.php" class="nav-link"><i class="fas fa-users"></i> Student List</a>
            <a href="view_feedback.php" class="nav-link"><i class="fas fa-comments"></i> Feedback</a>
            <a href="students.php" class="nav-link"><i class="fas fa-user-check"></i> Sit-in</a>
            <a href="sitin_view.php" class="nav-link"><i class="fas fa-clock"></i> Current Sit-in</a>
            <a href="session_history.php" class="nav-link"><i class="fas fa-history"></i> Reports</a>
            <a href="sitin_history.php" class="nav-link"><i class="fas fa-calendar-alt"></i> History</a>
            <a href="leaderboards.php" class="nav-link"><i class="fas fa-trophy"></i> Leaderboards</a>
            <a href="resources.php" class="nav-link"><i class="fas fa-book"></i> Resources</a>
            <a href="login.php" class="nav-link logout"><i class="fas fa-sign-out-alt"></i> Log out</a>
        </div>
    </div>

    <div class="container">
        <div class="stats-container">
            <div class="stat-box">
                <div class="stat-label"><i class="fas fa-user-graduate"></i> Students Registered</div>
                <div class="stat-number"><?php echo $total_students; ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label"><i class="fas fa-user-clock"></i> Currently Sit-in</div>
                <div class="stat-number"><?php echo $current_sitin; ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label"><i class="fas fa-calendar-check"></i> Total Sit-in</div>
                <div class="stat-number"><?php echo $total_sitin; ?></div>
            </div>
        </div>

        <div class="search-section">
            <button class="btn-modal" onclick="openModal()">
                <i class="fas fa-search"></i> Search Student for Sit-in
            </button>
        </div>

        <div class="chart-container">
            <canvas id="courseDistribution"></canvas>
        </div>
    </div>

    <!-- Search Modal -->
    <div id="searchModal" class="modal" style="display: <?php echo isset($_GET['search']) ? 'flex' : 'none'; ?>">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal()">&times;</span>
            <?php if (!isset($search_result) || !$search_result || mysqli_num_rows($search_result) == 0): ?>
                <h3>Search Student</h3>
                <form method="GET">
                    <input type="text" name="search" class="search-box" placeholder="Enter ID Number..." value="<?php echo htmlspecialchars($search_query); ?>" required>
                    <div class="btn-container">
                        <button type="submit" class="btn-sitin">Search</button>
                    </div>
                </form>
                <?php if (isset($_GET["search"])): ?>
                    <p style="color: #dc3545; margin-top: 16px; text-align: center;">No student found with that ID number.</p>
                <?php endif; ?>
            <?php else: ?>
                <?php 
                $student = mysqli_fetch_assoc($search_result);
                $session_check = "SELECT * FROM sit_in_records WHERE student_id = ? AND date = CURDATE() AND time_out IS NULL";
                $session_stmt = mysqli_prepare($con, $session_check);
                mysqli_stmt_bind_param($session_stmt, "s", $student['IDNO']);
                mysqli_stmt_execute($session_stmt);
                $session_result = mysqli_stmt_get_result($session_stmt);
                ?>
                <div class="sitin-form">
                    <h4>Sit In Form</h4>
                    <form method="POST">
                        <div class="form-group">
                            <label>ID Number:</label>
                            <input type="text" name="id_number" value="<?php echo htmlspecialchars($student['IDNO']); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Student Name:</label>
                            <input type="text" value="<?php echo htmlspecialchars($student['FIRSTNAME'] . ' ' . $student['LASTNAME']); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Remaining Sessions:</label>
                            <input type="text" value="<?php echo htmlspecialchars($student['remaining_sessions']); ?>" readonly>
                            <?php if ($student['remaining_sessions'] <= 0): ?>
                                <small style="color: #dc3545; display: block; margin-top: 8px;">No sessions remaining</small>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label>Session Status:</label>
                            <?php if (mysqli_num_rows($session_result) > 0): ?>
                                <input type="text" value="Already has an active session today" readonly style="color: #dc3545;">
                            <?php elseif ($student['remaining_sessions'] <= 0): ?>
                                <input type="text" value="No sessions remaining" readonly style="color: #dc3545;">
                            <?php else: ?>
                                <input type="text" value="Available for session" readonly style="color: #28a745;">
                            <?php endif; ?>
                        </div>
                        <?php if ($student['remaining_sessions'] > 0 && mysqli_num_rows($session_result) == 0): ?>
                            <div class="form-group">
                                <label>Purpose:</label>
                                <select name="purpose" required>
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
                            <div class="form-group">
                                <label>Lab:</label>
                                <select name="lab" required>
                                    <option value="">Select Lab</option>
                                    <option value="524">524</option>
                                    <option value="526">526</option>
                                    <option value="528">528</option>
                                    <option value="530">530</option>
                                    <option value="542">542</option>
                                    <option value="544">544</option>
                                    <option value="517">517</option>
                                </select>
                            </div>
                        <?php endif; ?>
                        <div class="btn-container">
                            <button type="button" class="btn-close" onclick="closeModal()">Close</button>
                            <?php if ($student['remaining_sessions'] > 0 && mysqli_num_rows($session_result) == 0): ?>
                                <button type="submit" name="sitin_submit" class="btn-sitin">
                                    <i class="fas fa-sign-in-alt"></i> Sit In
                                </button>
                            <?php endif; ?>
                        </div>

                        <?php if ($student['remaining_sessions'] <= 0): ?>
                            <button type="submit" name="reset_sessions" class="btn-reset">
                                <i class="fas fa-sync-alt"></i> Reset Sessions to 30
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (isset($_GET["success"])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET["success"]); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET["error"])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_GET["error"]); ?>
        </div>
    <?php endif; ?>

    <script>
        // Course Distribution Chart
        var ctx = document.getElementById('courseDistribution').getContext('2d');
        var courseChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($course_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($course_counts); ?>,
                    backgroundColor: [
                        '#4299e1',
                        '#48bb78',
                        '#ed8936',
                        '#ed64a6'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Course Distribution',
                        font: {
                            size: 16
                        }
                    }
                },
                layout: {
                    padding: 20
                }
            }
        });

        // Modal functions
        function openModal() {
            document.getElementById("searchModal").style.display = "flex";
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('search')) {
                window.history.replaceState({}, '', window.location.pathname);
            }
        }

        function closeModal() {
            document.getElementById("searchModal").style.display = "none";
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('search')) {
                window.history.replaceState({}, '', window.location.pathname);
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            var modal = document.getElementById("searchModal");
            if (event.target === modal) {
                closeModal();
            }
        }

        <?php if (isset($_GET["error"])): ?>
        window.onload = function() {
            setTimeout(function() {
                var alerts = document.getElementsByClassName('alert');
                for(var i = 0; i < alerts.length; i++) {
                    alerts[i].style.display = 'none';
                }
            }, 3000);
        }
        <?php endif; ?>
    </script>
</body>
</html>
