<?php
session_start();
if (!isset($_SESSION["admin_username"])) {
    header("Location: login.php");
    exit();
}

include("db.php");

// Get filter parameters
$selected_date = isset($_GET['date']) ? $_GET['date'] : '';
$show_all = isset($_GET['show_all']) ? true : false;
$lab_filter = isset($_GET['lab']) ? $_GET['lab'] : '';
$purpose_filter = isset($_GET['purpose']) ? $_GET['purpose'] : '';

// Get unique labs and purposes for dropdowns
$labs_query = "SELECT DISTINCT lab FROM sit_in_records WHERE lab IS NOT NULL ORDER BY lab";
$labs_result = mysqli_query($con, $labs_query);

$purposes_query = "SELECT DISTINCT purpose FROM sit_in_records WHERE purpose IS NOT NULL ORDER BY purpose";
$purposes_result = mysqli_query($con, $purposes_query);

// Query to get session history with student details
$query = "SELECT s.*, CONCAT(r.FIRSTNAME, ' ', r.LASTNAME) as full_name 
          FROM sit_in_records s 
          JOIN register r ON s.student_id = r.IDNO
          WHERE 1=1";

if (!$show_all && !empty($selected_date)) {
    $query .= " AND s.date = ?";
}

if (!empty($lab_filter)) {
    $query .= " AND s.lab = ?";
}

if (!empty($purpose_filter)) {
    $query .= " AND s.purpose = ?";
}

$query .= " ORDER BY s.date DESC, s.time_in DESC";

// Prepare and execute the query with parameters
$stmt = mysqli_prepare($con, $query);
$types = "";
$params = [];

if (!$show_all && !empty($selected_date)) {
    $types .= "s";
    $params[] = $selected_date;
}

if (!empty($lab_filter)) {
    $types .= "s";
    $params[] = $lab_filter;
}

if (!empty($purpose_filter)) {
    $types .= "s";
    $params[] = $purpose_filter;
}

if (!empty($types)) {
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
            <form method="GET" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <input type="date" name="date" class="date-input" value="<?php echo $selected_date; ?>">
                <select name="lab" class="date-input">
                    <option value="">All Labs</option>
                    <?php while ($lab = mysqli_fetch_assoc($labs_result)): ?>
                        <option value="<?php echo htmlspecialchars($lab['lab']); ?>" <?php echo $lab_filter == $lab['lab'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($lab['lab']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <select name="purpose" class="date-input">
                    <option value="">All Purposes</option>
                    <?php while ($purpose = mysqli_fetch_assoc($purposes_result)): ?>
                        <option value="<?php echo htmlspecialchars($purpose['purpose']); ?>" <?php echo $purpose_filter == $purpose['purpose'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($purpose['purpose']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" class="btn btn-search">Search</button>
                <button type="button" class="btn btn-reset" onclick="location.href='session_history.php'">Reset</button>
                <button type="button" class="btn btn-search" onclick="location.href='session_history.php?show_all=1'">Show All</button>
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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
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
            var table = document.getElementById("historyTable");
            var rows = table.getElementsByTagName("tr");
            var csv = [];
            
            // Get headers
            var headerRow = rows[0];
            var headers = [];
            for (var i = 0; i < headerRow.cells.length; i++) {
                headers.push(headerRow.cells[i].innerText);
            }
            csv.push(headers.join(","));
            
            // Get data rows
            for (var i = 1; i < rows.length; i++) {
                var row = rows[i];
                if (row.style.display !== "none") {
                    var rowData = [];
                    for (var j = 0; j < row.cells.length; j++) {
                        var cell = row.cells[j];
                        var text = cell.innerText;
                        // Escape commas and quotes
                        text = text.replace(/"/g, '""');
                        if (text.includes(",") || text.includes('"')) {
                            text = '"' + text + '"';
                        }
                        rowData.push(text);
                    }
                    csv.push(rowData.join(","));
                }
            }
            
            // Create and download file
            var csvContent = csv.join("\n");
            var blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
            var link = document.createElement("a");
            var url = URL.createObjectURL(blob);
            link.setAttribute("href", url);
            link.setAttribute("download", "session_history.csv");
            link.style.visibility = "hidden";
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        function exportToExcel() {
            var table = document.getElementById("historyTable");
            var wb = XLSX.utils.table_to_book(table, { sheet: "Session History" });
            XLSX.writeFile(wb, "session_history.xlsx");
        }

        function exportToPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            doc.autoTable({
                html: '#historyTable',
                theme: 'grid',
                headStyles: {
                    fillColor: [20, 76, 148],
                    textColor: 255,
                    fontSize: 10
                },
                bodyStyles: {
                    fontSize: 9
                },
                margin: { top: 20 },
                didDrawPage: function(data) {
                    doc.setFontSize(16);
                    doc.text("Session History", 14, 15);
                }
            });
            
            doc.save("session_history.pdf");
        }
    </script>
</body>
</html> 