<?php
session_start();
if (!isset($_SESSION["admin_username"])) {
    header("Location: login.php");
    exit();
}

// Connect to the database
include("db.php");

// Handle session end (time out)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["end_session"])) {
    $student_id = $_POST["student_id"];
    $record_id = $_POST["record_id"];
    
    // Get current remaining sessions
    $session_query = "SELECT remaining_session FROM sit_in_records WHERE id = ? AND student_id = ?";
    $session_stmt = mysqli_prepare($con, $session_query);
    mysqli_stmt_bind_param($session_stmt, "is", $record_id, $student_id);
    mysqli_stmt_execute($session_stmt);
    $session_result = mysqli_stmt_get_result($session_stmt);
    $session_data = mysqli_fetch_assoc($session_result);
    
    if ($session_data) {
        $new_remaining = max(0, $session_data['remaining_session'] - 1);
        
        // Update the record with time_out and deduct one session
        $update_query = "UPDATE sit_in_records 
                        SET time_out = CURRENT_TIME(),
                            remaining_session = ?,
                            status = 'absent'
                        WHERE id = ? AND student_id = ?";
        $update_stmt = mysqli_prepare($con, $update_query);
        mysqli_stmt_bind_param($update_stmt, "iis", $new_remaining, $record_id, $student_id);
        mysqli_stmt_execute($update_stmt);
    }
    
    header("Location: sitin_view.php");
    exit();
}

// Get current active sessions
$active_sessions_query = "SELECT 
    s.id,
    s.student_id,
    CONCAT(r.FIRSTNAME, ' ', r.LASTNAME) as full_name,
    s.purpose,
    s.lab,
    DATE_FORMAT(s.time_in, '%l:%i %p') as time_in,
    s.remaining_session,
    s.date
FROM sit_in_records s
JOIN register r ON s.student_id = r.IDNO
WHERE s.date = CURDATE() 
AND s.status = 'present'
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
        .filter-box {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 20px;
            width: 200px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="admin_dashboard.php">College of Computer Studies Admin</a>
        <div>
            <a href="announcement.php">Create Announcements</a>
            <a href="students.php">Sit-in</a>
            <a href="sitin_view.php">Current Sit-in</a>
            <a href="session_history.php">Sit-in Reports</a>
            <a href="../php/login.php" style="color: orange;">Log out</a>
        </div>
    </div>

    <div class="container">
        <h2>Current Active Sessions</h2>
        
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
                        <td><?php echo htmlspecialchars($row['remaining_session']); ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($row['student_id']); ?>">
                                <input type="hidden" name="record_id" value="<?php echo htmlspecialchars($row['id']); ?>">
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