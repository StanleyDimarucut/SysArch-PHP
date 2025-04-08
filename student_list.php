<?php
session_start();
if (!isset($_SESSION["admin_username"])) {
    header("Location: login.php");
    exit();
}

// Connect to the database
include("db.php");

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
        .table-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .table-container h3 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #144c94;
        }
        .search-box {
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
        .btn-reset {
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 6px 12px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }
        .btn-reset:hover {
            background-color: #218838;
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
        }

        .alert-success {
            background-color: #28a745;
            color: white;
            border: none;
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
    </style>
</head>
<body>
    <div class="navbar">
        <a href="admin_dashboard.php" style="font-size: 1.2rem; font-weight: 600;">Admin Dashboard</a>
        <div>
            <a href="announcement.php">Announcements</a>
            <a href="student_list.php">View Student List</a>
            <a href="view_feedback.php">View Feedback</a>
            <a href="students.php">Sit-in</a>
            <a href="sitin_view.php">Current Sit-in</a>
            <a href="session_history.php">Sit-in Reports</a>
            <a href="sitin_history.php">Sit-in History</a>
            <a href="login.php" style="color: #ffd700;">Log out</a>
        </div>
    </div>

    <div class="container">
        <div class="table-container">
            <h3>Registered Students</h3>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <input type="text" id="studentFilter" class="search-box" onkeyup="filterStudents()" placeholder="Search students...">
                <form method="POST" style="margin: 0;">
                    <button type="submit" name="reset_all_sessions" class="btn-reset" onclick="return confirm('Are you sure you want to reset ALL students\' sessions to 30?')">
                        Reset All Sessions
                    </button>
                </form>
            </div>
            <table id="studentsTable">
                <thead>
                    <tr>
                        <th>ID Number</th>
                        <th>Name</th>
                        <th>Course</th>
                        <th>Year Level</th>
                        <th>Remaining Sessions</th>
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
                        echo "<td style='" . ($student['remaining_sessions'] <= 5 ? 'color: #dc3545; font-weight: bold;' : 'color: #28a745; font-weight: bold;') . "'>" . htmlspecialchars($student['remaining_sessions']) . "</td>";
                        echo "<td>
                                <form method='POST' style='display: inline;'>
                                    <input type='hidden' name='student_id' value='" . htmlspecialchars($student['IDNO']) . "'>
                                    <button type='submit' name='reset_sessions' class='btn-reset' onclick='return confirm(\"Are you sure you want to reset this student's sessions to 30?\")'>Reset Sessions</button>
                                </form>
                              </td>";
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
        // Add Font Awesome for the check icon
        var fontAwesome = document.createElement('link');
        fontAwesome.rel = 'stylesheet';
        fontAwesome.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css';
        document.head.appendChild(fontAwesome);

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
    </script>
</body>
</html> 