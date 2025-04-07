<?php
session_start();
if (!isset($_SESSION["admin_username"])) {
    header("Location: login.php");
    exit();
}

// Connect to the database
include("db.php");

// Get filter parameters
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$student_filter = isset($_GET['student']) ? $_GET['student'] : '';

// Build the query with filters
$query = "SELECT f.*, CONCAT(r.FIRSTNAME, ' ', r.LASTNAME) as student_name, r.COURSE, r.YEARLEVEL 
          FROM feedback f
          JOIN register r ON f.student_id = r.IDNO
          WHERE 1=1";

$params = [];
$types = "";

if (!empty($date_filter)) {
    $query .= " AND DATE(f.date_submitted) = ?";
    $params[] = $date_filter;
    $types .= "s";
}

if (!empty($student_filter)) {
    $query .= " AND (r.IDNO LIKE ? OR CONCAT(r.FIRSTNAME, ' ', r.LASTNAME) LIKE ?)";
    $params[] = "%$student_filter%";
    $params[] = "%$student_filter%";
    $types .= "ss";
}

$query .= " ORDER BY f.date_submitted DESC";

// Prepare and execute the query
$stmt = mysqli_prepare($con, $query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS | View Feedback</title>
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
        #feedbackTable {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        #feedbackTable th {
            background-color: #144c94;
            color: white;
            padding: 14px 12px;
            text-align: left;
            font-weight: 500;
        }
        #feedbackTable td {
            padding: 16px 12px;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
        }
        #feedbackTable tr:hover {
            background-color: #f5f5f5;
        }
        #feedbackTable tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .feedback-message {
            max-width: 400px;
            white-space: pre-wrap;
            word-wrap: break-word;
            background-color: #f8f9fa;
            padding: 12px;
            border-radius: 6px;
            border-left: 4px solid #144c94;
            margin: 4px 0;
            font-size: 14px;
            line-height: 1.5;
            color: #444;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .filter-container {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            align-items: center;
        }
        .date-input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
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
        .subject-tag {
            display: inline-block;
            background-color: #e9ecef;
            color: #495057;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 500;
        }
        .date-cell {
            white-space: nowrap;
            color: #666;
            font-size: 13px;
        }
        .student-name {
            font-weight: 500;
            color: #144c94;
        }
        .student-details {
            color: #666;
            font-size: 13px;
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
            <h3>Student Feedback</h3>
            <div class="filter-container">
                <input type="date" id="dateFilter" class="date-input" onchange="filterFeedback()">
                <input type="text" id="studentFilter" class="search-box" onkeyup="filterFeedback()" placeholder="Search by student name or ID...">
            </div>
            <table id="feedbackTable">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>ID Number</th>
                        <th>Course</th>
                        <th>Year Level</th>
                        <th>Subject</th>
                        <th>Message</th>
                        <th>Date Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($feedback = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td>
                                    <div class="student-name"><?php echo htmlspecialchars($feedback["student_name"]); ?></div>
                                </td>
                                <td>
                                    <div class="student-details"><?php echo htmlspecialchars($feedback["student_id"]); ?></div>
                                </td>
                                <td>
                                    <div class="student-details"><?php echo htmlspecialchars($feedback["COURSE"]); ?></div>
                                </td>
                                <td>
                                    <div class="student-details"><?php echo htmlspecialchars($feedback["YEARLEVEL"]); ?></div>
                                </td>
                                <td>
                                    <div class="subject-tag"><?php echo htmlspecialchars($feedback["subject"]); ?></div>
                                </td>
                                <td>
                                    <div class="feedback-message"><?php echo nl2br(htmlspecialchars($feedback["message"])); ?></div>
                                </td>
                                <td class="date-cell">
                                    <?php echo date("F j, Y, g:i a", strtotime($feedback["date_submitted"])); ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">No feedback found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function filterFeedback() {
            var dateFilter = document.getElementById("dateFilter").value;
            var studentFilter = document.getElementById("studentFilter").value.toLowerCase();
            var table = document.getElementById("feedbackTable");
            var tr = table.getElementsByTagName("tr");

            for (var i = 1; i < tr.length; i++) {
                var td = tr[i].getElementsByTagName("td");
                var found = false;
                var rowDate = td[6].textContent || td[6].innerText;
                var rowDateFormatted = new Date(rowDate).toISOString().split('T')[0];

                if (dateFilter && rowDateFormatted !== dateFilter) {
                    tr[i].style.display = "none";
                    continue;
                }

                for (var j = 0; j < td.length; j++) {
                    var cell = td[j];
                    if (cell) {
                        var text = cell.textContent || cell.innerText;
                        if (text.toLowerCase().indexOf(studentFilter) > -1) {
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