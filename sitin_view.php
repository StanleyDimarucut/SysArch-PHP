<?php
session_start();
if (!isset($_SESSION["admin_username"])) {
    header("Location: login.php");
    exit();
}

// Connect to the database
include("db.php");

// Query to get active sit-in records with student details
$query = "SELECT s.*, CONCAT(r.FIRSTNAME, ' ', r.LASTNAME) as full_name 
          FROM sit_in_records s 
          JOIN register r ON s.student_id = r.IDNO 
          WHERE s.date = CURDATE() 
          AND s.time_out IS NULL 
          ORDER BY s.time_in DESC";

$result = mysqli_query($con, $query);

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

// Get current active sessions
$active_sessions_query = "SELECT 
    s.id,
    s.student_id,
    CONCAT(r.FIRSTNAME, ' ', r.LASTNAME) as full_name,
    s.purpose,
    s.lab,
    DATE_FORMAT(s.time_in, '%l:%i %p') as time_in,
    r.remaining_sessions,
    s.date
FROM sit_in_records s
JOIN register r ON s.student_id = r.IDNO
WHERE s.date = CURDATE() 
AND s.time_out IS NULL
ORDER BY s.time_in DESC";

$result = mysqli_query($con, $active_sessions_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS | Current Sit-in</title>
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
            width: 95%;
            margin: 20px auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #144c94;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .filter-box {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 20px;
            width: 200px;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        
        .btn-end-session {
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 6px 12px;
            cursor: pointer;
        }
        .btn-end-session:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="admin_dashboard.php" style="font-size: 1.2rem; font-weight: 600;">Admin Dashboard</a>
        <div>
            <a href="announcement.php">Announcements</a>
            <a href="student_list.php">View Student List</a>
            <a href="view_feedback.php">Feedback</a>
            <a href="students.php">Sit-in</a>
            <a href="sitin_view.php">Current Sit-in</a>
            <a href="session_history.php">Sit-in Reports</a>
            <a href="sitin_history.php">Sit-in History</a>
            <a href="leaderboards.php">Leaderboards</a>
            <a href="login.php" style="color: #ffd700;">Log out</a>
        </div>
    </div>

    <div class="container">
        <h2>Current Active Sessions</h2>
        
        <?php if (isset($_GET["error"])): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($_GET["error"]); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET["success"])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_GET["success"]); ?>
            </div>
        <?php endif; ?>
        
        <input type="text" class="filter-box" id="filterInput" placeholder="Filter..." onkeyup="filterTable()">

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
                            <form method="POST">
                                <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($row['student_id']); ?>">
                                <button type="submit" name="end_session" class="btn-end-session">End Session</button>
                            </form>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
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
    </script>
</body>
</html> 