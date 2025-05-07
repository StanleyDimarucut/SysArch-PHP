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
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .page-header h2 {
            color: #1a5dba;
            font-size: 24px;
            margin: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-container {
            margin-bottom: 24px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            width: 100%;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-label {
            font-size: 14px;
            font-weight: 500;
            color: #444;
        }

        .form-control {
            padding: 10px;
            border: 1px solid #e5e9ef;
            border-radius: 8px;
            font-size: 14px;
            width: 100%;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #1a5dba;
            box-shadow: 0 0 0 3px rgba(26,93,186,0.1);
        }

        .button-group {
            display: flex;
            flex-direction: row;
            gap: 8px;
            align-items: flex-end;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-search {
            background: linear-gradient(135deg, #144c94 0%, #1a5dba 100%);
            color: white;
        }

        .btn-search:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(26,93,186,0.2);
        }

        .btn-reset {
            background-color: #6c757d;
            color: white;
        }

        .btn-reset:hover {
            background-color: #5a6268;
            transform: translateY(-1px);
        }

        .btn-export {
            background-color: #28a745;
            color: white;
        }

        .btn-export:hover {
            background-color: #218838;
            transform: translateY(-1px);
        }

        .export-buttons {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }

        th, td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid #e5e9ef;
            font-size: 14px;
        }

        th {
            background-color: #f8fafc;
            color: #1a5dba;
            font-weight: 600;
            white-space: nowrap;
        }

        td {
            color: #444;
        }

        tbody tr:hover {
            background-color: #f8fafc;
        }

        .no-records {
            text-align: center;
            padding: 40px;
            color: #666;
            font-size: 15px;
            background: #f8fafc;
            border-radius: 8px;
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 15px;
            }

            .nav-menu {
                display: none;
            }

            .container {
                width: 90%;
                padding: 16px;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .button-group {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }

            th, td {
                padding: 12px 8px;
                font-size: 13px;
            }

            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
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
        <div class="page-header">
            <h2><i class="fas fa-history"></i> Generate Reports</h2>
        </div>
        
        <div class="filter-container">
            <form method="GET" class="filter-form">
                <div class="form-grid">
                    <div class="filter-group">
                        <label class="filter-label" for="date-filter">Date</label>
                        <input type="date" id="date-filter" name="date" class="form-control" value="<?php echo $selected_date; ?>">
                    </div>
                    <div class="filter-group">
                        <label class="filter-label" for="lab-filter">Laboratory</label>
                        <select id="lab-filter" name="lab" class="form-control">
                            <option value="">All Labs</option>
                            <?php while ($lab = mysqli_fetch_assoc($labs_result)): ?>
                                <option value="<?php echo htmlspecialchars($lab['lab']); ?>" 
                                    <?php echo $lab_filter == $lab['lab'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($lab['lab']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label" for="purpose-filter">Purpose</label>
                        <select id="purpose-filter" name="purpose" class="form-control">
                            <option value="">All Purposes</option>
                            <?php while ($purpose = mysqli_fetch_assoc($purposes_result)): ?>
                                <option value="<?php echo htmlspecialchars($purpose['purpose']); ?>" 
                                    <?php echo $purpose_filter == $purpose['purpose'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($purpose['purpose']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="filter-group button-group">
                        <button type="submit" class="btn btn-search">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <button type="button" class="btn btn-reset" onclick="location.href='session_history.php'">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="export-buttons">
            <button onclick="exportToCSV()" class="btn btn-export">
                <i class="fas fa-file-csv"></i> Export to CSV
            </button>
            <button onclick="exportToExcel()" class="btn btn-export">
                <i class="fas fa-file-excel"></i> Export to Excel
            </button>
            <button onclick="exportToPDF()" class="btn btn-export">
                <i class="fas fa-file-pdf"></i> Export to PDF
            </button>
            <button onclick="printTable()" class="btn btn-export">
                <i class="fas fa-print"></i> Print
            </button>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Purpose</th>
                        <th>Laboratory</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['purpose']); ?></td>
                                <td><?php echo htmlspecialchars($row['lab']); ?></td>
                                <td><?php echo htmlspecialchars($row['time_in']); ?></td>
                                <td><?php echo htmlspecialchars($row['time_out']); ?></td>
                                <td><?php echo htmlspecialchars($row['date']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">
                                <div class="no-records">
                                    <i class="fas fa-info-circle"></i> No records found
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
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
    </script>
</body>
</html>