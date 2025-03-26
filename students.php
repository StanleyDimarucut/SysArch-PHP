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
    $date = date('Y-m-d');
    
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

    // Insert sit-in record with current time
    $insert_query = "INSERT INTO sit_in_records (student_id, date, purpose, lab, time_in) 
                    VALUES (?, ?, ?, ?, CURRENT_TIME())";
    $insert_stmt = mysqli_prepare($con, $insert_query);
    mysqli_stmt_bind_param($insert_stmt, "ssss", $student_id, $date, $purpose, $lab);
    
    if (mysqli_stmt_execute($insert_stmt)) {
        header("Location: students.php?success=Student successfully logged in");
    } else {
        header("Location: students.php?error=Failed to create sit-in record");
    }
    exit();
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: rgb(230, 233, 241);
        }
        .navbar {
            background-color: #144c94;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar a {
            color: white;
            text-decoration: none;
            margin: 0 15px;
            font-size: 18px;
        }
        .navbar a:hover {
            color: yellow;
        }
        .container {
            width: 80%;
            margin: 30px auto;
        }
        .btn-modal {
            background-color: #144c94;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin-bottom: 15px;
        }
        .btn-modal:hover {
            background-color: #0f3c7a;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            width: 350px;
            text-align: center;
            position: relative;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
        }

        .modal-close {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
        }

        .modal-close:hover {
            color: red;
        }

        .search-box {
            width: 90%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            margin-bottom: 10px;
        }

        .btn-search {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }

        .btn-search:hover {
            background-color: #0056b3;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        th {
            background-color: #144c94;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .status-present {
            color: green;
            font-weight: bold;
        }
        .status-absent {
            color: red;
            font-weight: bold;
        }
        .btn-status {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            margin: 2px;
        }
        .btn-present {
            background-color: #28a745;
            color: white;
        }
        .btn-absent {
            background-color: #dc3545;
            color: white;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #144c94;
            margin: 10px 0;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        .chart-container {
            width: 100%;
            max-width: 500px;
            height: 300px;
            margin: 20px auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .table-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-top: 30px;
        }

        .table-container h3 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #144c94;
        }

        .table-container .search-box {
            width: 300px;
            margin-bottom: 20px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        #studentsTable {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        #studentsTable th {
            background-color: #144c94;
            color: white;
            padding: 12px;
            text-align: left;
        }

        #studentsTable td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }

        #studentsTable tr:hover {
            background-color: #f5f5f5;
        }

        #studentsTable tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        /* Sit In Form Styles */
        .sitin-form {
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
            text-align: left;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .btn-container {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-sitin {
            background-color: #007bff;
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .btn-close {
            background-color: #6c757d;
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>
<body>

    <div class="navbar">
        <a href="admin_dashboard.php">Admin Dashboard</a>
        <div>
            <a href="announcement.php">Announcements</a>
            <a href="student_list.php">View Student List</a>
            <a href="students.php">Sit-in</a>
            <a href="sitin_view.php">Current Sit-in</a>
            <a href="session_history.php">Sit-in Reports</a>
            <a href="../php/login.php" style="color: orange;">Log out</a>
        </div>
    </div>

    <div class="container">
        <div class="stats-container">
            <div class="stat-box">
                <div class="stat-label">Students Registered</div>
                <div class="stat-number"><?php echo $total_students; ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Currently Sit-in</div>
                <div class="stat-number"><?php echo $current_sitin; ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Total Sit-in</div>
                <div class="stat-number"><?php echo $total_sitin; ?></div>
            </div>
        </div>

        <div style="text-align: center; margin: 20px 0;">
            <button class="btn-modal" onclick="openModal()">Search Student for Sit-in</button>
        </div>
    </div>

    <!-- Search Modal -->
    <div id="searchModal" class="modal" style="display: <?php echo isset($_GET['search']) ? 'flex' : 'none'; ?>">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal()">&times;</span>
            <?php if (!isset($search_result) || !$search_result || mysqli_num_rows($search_result) == 0): ?>
                <h3>Search Student</h3>
                <form method="GET">
                    <input type="text" name="search" class="search-box" placeholder="Search ID Number..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit" class="btn-search">Search</button>
                </form>
                <?php if (isset($_GET["search"])): ?>
                    <p>No student found with that ID number.</p>
                <?php endif; ?>
            <?php else: ?>
                <?php 
                $student = mysqli_fetch_assoc($search_result);
                // Check if student has an active session today
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
                                <small style="color: red; display: block; margin-top: 5px;">No sessions remaining</small>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label>Session Status:</label>
                            <?php if (mysqli_num_rows($session_result) > 0): ?>
                                <input type="text" value="Already has an active session today" readonly style="color: red;">
                            <?php elseif ($student['remaining_sessions'] <= 0): ?>
                                <input type="text" value="No sessions remaining" readonly style="color: red;">
                            <?php else: ?>
                                <input type="text" value="Available for session" readonly style="color: green;">
                            <?php endif; ?>
                        </div>
                        <?php if ($student['remaining_sessions'] > 0 && mysqli_num_rows($session_result) == 0): ?>
                            <div class="form-group">
                                <label>Purpose:</label>
                                <select name="purpose" required>
                                    <option value="">Select Purpose</option>
                                    <option value="C#">C#</option>
                                    <option value="PHP">PHP</option>
                                    <option value="Java">Java</option>
                                    <option value="HTML">HTML</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Lab:</label>
                                <select name="lab" required>
                                    <option value="">Select Lab</option>
                                    <option value="524">524</option>
                                    <option value="526">526</option>
                                    <option value="528">528</option>
                                </select>
                            </div>
                        <?php endif; ?>
                        <div class="btn-container">
                            <button type="button" class="btn-close" onclick="closeModal()">Close</button>
                            <?php if ($student['remaining_sessions'] > 0 && mysqli_num_rows($session_result) == 0): ?>
                                <button type="submit" name="sitin_submit" class="btn-sitin">Sit In</button>
                            <?php endif; ?>
                        </div>
                    </form>
                    
                    <!-- Add Reset Sessions Form -->
                    <form method="POST" style="margin-top: 15px; border-top: 1px solid #eee; padding-top: 15px;">
                        <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student['IDNO']); ?>">
                        <button type="submit" name="reset_sessions" class="btn-sitin" style="width: 100%; background-color: #28a745;">
                            Reset Sessions to 30
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function filterStudents() {
            var input = document.getElementById("studentFilter");
            var filter = input.value.toLowerCase();
            var table = document.getElementById("studentsTable");
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

        function openModal() {
            document.getElementById("searchModal").style.display = "flex";
            // Clear any existing search results
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('search')) {
                window.history.replaceState({}, '', window.location.pathname);
            }
        }

        function closeModal() {
            document.getElementById("searchModal").style.display = "none";
            // Clear any existing search results
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

        // Show error message if present
        <?php if (isset($_GET["error"])): ?>
        window.onload = function() {
            alert("<?php echo htmlspecialchars($_GET["error"]); ?>");
        }
        <?php endif; ?>
    </script>

</body>
</html>
