<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

include("db.php");

// Get student's ID from username
$username = $_SESSION["username"];
$query = "SELECT IDNO FROM register WHERE USERNAME = ?";
$stmt = mysqli_prepare($con, $query);
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
$student_id = $row['IDNO'];

// Get filter parameters
$selected_date = isset($_GET['date']) ? $_GET['date'] : '';
$show_all = isset($_GET['show_all']) ? true : false;
$lab_filter = isset($_GET['lab']) ? $_GET['lab'] : '';
$purpose_filter = isset($_GET['purpose']) ? $_GET['purpose'] : '';

// Get unique labs and purposes for dropdowns
$labs_query = "SELECT DISTINCT lab FROM sit_in_records WHERE student_id = ? AND lab IS NOT NULL ORDER BY lab";
$labs_stmt = mysqli_prepare($con, $labs_query);
mysqli_stmt_bind_param($labs_stmt, "s", $student_id);
mysqli_stmt_execute($labs_stmt);
$labs_result = mysqli_stmt_get_result($labs_stmt);

$purposes_query = "SELECT DISTINCT purpose FROM sit_in_records WHERE student_id = ? AND purpose IS NOT NULL ORDER BY purpose";
$purposes_stmt = mysqli_prepare($con, $purposes_query);
mysqli_stmt_bind_param($purposes_stmt, "s", $student_id);
mysqli_stmt_execute($purposes_stmt);
$purposes_result = mysqli_stmt_get_result($purposes_stmt);

// Query to get session history
$query = "SELECT s.*, CONCAT(r.FIRSTNAME, ' ', r.LASTNAME) as full_name 
          FROM sit_in_records s 
          JOIN register r ON s.student_id = r.IDNO
          WHERE s.student_id = ?";

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
$types = "s";
$params = [$student_id];

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

mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS | My Sit-in History</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
        }

        .navbar {
            background-color: #144c94;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            margin: 0 15px;
            font-size: 0.95rem;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .navbar a:hover {
            color: #ffd700;
        }

        .logout-link {
            color: #ffd700 !important;
        }

        .container {
            margin-top: 80px;
            padding: 2rem;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }

        h2 {
            color: #1a1a1a;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        .filter-container {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }

        .filter-form {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .date-input, select {
            padding: 0.6rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .btn {
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 4px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-search {
            background-color: #144c94;
            color: white;
        }

        .btn-search:hover {
            background-color: #0d3a7d;
        }

        .btn-reset {
            background-color: #dc3545;
            color: white;
        }

        .btn-reset:hover {
            background-color: #c82333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        th {
            background-color: #144c94;
            color: white;
            padding: 1rem;
            text-align: left;
            font-weight: 500;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid #f0f2f5;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .no-records {
            text-align: center;
            padding: 2rem;
            color: #666;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }

            .date-input, select {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
    <a href="dashboard.php" style="font-size: 1.2rem; font-weight: 600;">Student Dashboard</a>
        <div>
            <a href="dashboard.php">Home</a>
            <a href="profile.php">Edit Profile</a>
            <a href="history.php">History</a>
            <a href="Reservation.php">Reservation</a>
            <a href="feedback.php">Feedback</a>
            <a href="login.php" style="color: #ffd700;">Log out</a>
        </div>
    </div>

    <div class="container">
        <h2>My Sit-in History</h2>
        
        <div class="filter-container">
            <form method="GET" class="filter-form">
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
                <button type="button" class="btn btn-reset" onclick="location.href='history.php'">Reset</button>
                <button type="button" class="btn btn-search" onclick="location.href='history.php?show_all=1'">Show All</button>
            </form>
        </div>

        <table id="historyTable">
            <thead>
                <tr>
                    <th>Purpose</th>
                    <th>Laboratory</th>
                    <th>Login Time</th>
                    <th>Logout Time</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['purpose']); ?></td>
                            <td><?php echo htmlspecialchars($row['lab']); ?></td>
                            <td><?php echo htmlspecialchars($row['time_in']); ?></td>
                            <td><?php echo htmlspecialchars($row['time_out'] ?? 'Active'); ?></td>
                            <td><?php echo htmlspecialchars($row['date']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="no-records">No sit-in records found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>