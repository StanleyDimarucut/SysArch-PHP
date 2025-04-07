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
$lab_filter = isset($_GET['lab']) ? $_GET['lab'] : '';

// Build the query with filters
$query = "SELECT 
    s.*,
    CONCAT(r.FIRSTNAME, ' ', r.LASTNAME) as student_name,
    r.COURSE,
    r.YEARLEVEL
FROM sit_in_records s
JOIN register r ON s.student_id = r.IDNO
WHERE 1=1";

$params = [];
$types = "";

if (!empty($date_filter)) {
    $query .= " AND s.date = ?";
    $params[] = $date_filter;
    $types .= "s";
}

if (!empty($student_filter)) {
    $query .= " AND (r.IDNO LIKE ? OR CONCAT(r.FIRSTNAME, ' ', r.LASTNAME) LIKE ?)";
    $params[] = "%$student_filter%";
    $params[] = "%$student_filter%";
    $types .= "ss";
}

if (!empty($lab_filter)) {
    $query .= " AND s.lab = ?";
    $params[] = $lab_filter;
    $types .= "s";
}

$query .= " ORDER BY s.date DESC, s.time_in DESC";

// Prepare and execute the query
$stmt = mysqli_prepare($con, $query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Get unique labs for filter dropdown
$labs_query = "SELECT DISTINCT lab FROM sit_in_records WHERE lab IS NOT NULL ORDER BY lab";
$labs_result = mysqli_query($con, $labs_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS | Sit-in History</title>
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
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-search {
            background-color: #007bff;
            color: white;
        }
        .btn-reset {
            background-color: #dc3545;
            color: white;
        }
        .export-buttons {
            margin-bottom: 20px;
        }
        .export-btn {
            padding: 6px 12px;
            margin-right: 5px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 4px;
            cursor: pointer;
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
            margin-left: 10px;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-present {
            background-color: #28a745;
            color: white;
        }
        
        .status-absent {
            background-color: #dc3545;
            color: white;
        }

        @media (max-width: 768px) {
            .filter-container {
                flex-direction: column;
                align-items: stretch;
            }

            .export-buttons {
                flex-wrap: wrap;
            }

            .filter-box {
                width: 100%;
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
            <a href="view_feedback.php">Feedback</a>
            <a href="students.php">Sit-in</a>
            <a href="sitin_view.php">Current Sit-in</a>
            <a href="session_history.php">Sit-in Reports</a>
            <a href="sitin_history.php">Sit-in History</a>
            <a href="login.php" style="color: #ffd700;">Log out</a>
        </div>
    </div>

    <div class="container">
        <h2>Sit-in History</h2>
        
        <div class="filter-container">
            <form method="GET" style="display: flex; gap: 10px; align-items: center;">
                <input type="date" name="date" class="date-input" value="<?php echo $date_filter; ?>">
                <input type="text" name="student" class="date-input" placeholder="Search by ID or name" value="<?php echo $student_filter; ?>">
                <select name="lab" class="date-input">
                    <option value="">All Labs</option>
                    <?php while ($lab = mysqli_fetch_assoc($labs_result)): ?>
                        <option value="<?php echo htmlspecialchars($lab['lab']); ?>" <?php echo $lab_filter == $lab['lab'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($lab['lab']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" class="btn btn-search">Search</button>
                <button type="button" class="btn btn-reset" onclick="location.href='sitin_history.php'">Reset</button>
            </form>
        </div>

        <table id="historyTable">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Course & Year</th>
                    <th>Laboratory</th>
                    <th>Purpose</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                            <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['COURSE'] . ' ' . $row['YEARLEVEL']); ?></td>
                            <td><?php echo htmlspecialchars($row['lab']); ?></td>
                            <td><?php echo htmlspecialchars($row['purpose']); ?></td>
                            <td><?php echo date('h:i A', strtotime($row['time_in'])); ?></td>
                            <td><?php echo $row['time_out'] ? date('h:i A', strtotime($row['time_out'])) : 'Ongoing'; ?></td>
                            <td>
                                <span class="status-badge <?php echo $row['time_out'] ? 'status-present' : 'status-absent'; ?>">
                                    <?php echo $row['time_out'] ? 'Completed' : 'Active'; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align: center;">No records found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
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

        function exportToCSV() {
            window.location.href = 'export_sitin.php?format=csv';
        }

        function exportToExcel() {
            window.location.href = 'export_sitin.php?format=excel';
        }

        function exportToPDF() {
            window.location.href = 'export_sitin.php?format=pdf';
        }

        function printTable() {
            window.print();
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