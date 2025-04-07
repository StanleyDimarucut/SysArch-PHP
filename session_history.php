<?php
session_start();
if (!isset($_SESSION["admin_username"])) {
    header("Location: login.php");
    exit();
}

include("db.php");

// Get the selected date (default to today)
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Query to get session history with student details
$query = "SELECT s.*, CONCAT(r.FIRSTNAME, ' ', r.LASTNAME) as full_name 
          FROM sit_in_records s 
          JOIN register r ON s.student_id = r.IDNO 
          ORDER BY s.date DESC, s.time_in DESC";

$result = mysqli_query($con, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS | Generate Reports</title>
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
        
        .btn-end-session {
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 4px 8px;
            font-size: 12px;
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
            <a href="login.php" style="color: #ffd700;">Log out</a>
        </div>
    </div>

    <div class="container">
        <h2>Generate Reports</h2>
        
        <div class="filter-container">
            <form method="GET" style="display: flex; gap: 10px; align-items: center;">
                <input type="date" name="date" class="date-input" value="<?php echo $selected_date; ?>">
                <button type="submit" class="btn btn-search">Search</button>
                <button type="button" class="btn btn-reset" onclick="location.href='session_history.php'">Reset</button>
            </form>
        </div>

        <div class="export-buttons">
            <button class="export-btn" onclick="exportToCSV()">CSV</button>
            <button class="export-btn" onclick="exportToExcel()">Excel</button>
            <button class="export-btn" onclick="exportToPDF()">PDF</button>
            <button class="export-btn" onclick="window.print()">Print</button>
            <input type="text" class="filter-box" id="filterInput" placeholder="Filter..." onkeyup="filterTable()">
        </div>

        <table id="historyTable">
            <thead>
                <tr>
                    <th>ID Number</th>
                    <th>Name</th>
                    <th>Purpose</th>
                    <th>Laboratory</th>
                    <th>Login</th>
                    <th>Logout</th>
                    <th>Date</th>
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
                        <td><?php echo htmlspecialchars($row['time_out']); ?></td>
                        <td><?php echo htmlspecialchars($row['date']); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <script>
        function filterTable() {
            var input = document.getElementById("filterInput");
            var filter = input.value.toLowerCase();
            var table = document.getElementById("historyTable");
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
            // Add CSV export functionality
            alert('CSV export functionality will be implemented');
        }

        function exportToExcel() {
            // Add Excel export functionality
            alert('Excel export functionality will be implemented');
        }

        function exportToPDF() {
            // Add PDF export functionality
            alert('PDF export functionality will be implemented');
        }
    </script>
</body>
</html> 