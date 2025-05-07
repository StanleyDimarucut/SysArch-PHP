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

        .nav-link i {
            font-size: 14px;
        }

        .nav-link.logout {
            background: rgba(255,217,0,0.15);
            color: #ffd700;
            border: 1px solid rgba(255,217,0,0.3);
        }

        .nav-link.logout:hover {
            background: rgba(255,217,0,0.25);
        }

        .mobile-menu-toggle {
            display: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .main-container {
            width: 95%;
            max-width: 1400px;
            margin: 20px auto;
            padding: 0;
        }

        .page-header {
            background: white;
            padding: 25px 30px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            margin-bottom: 24px;
        }

        .page-header h1 {
            color: #1a5dba;
            font-size: 24px;
            margin: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .search-container {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            margin-bottom: 24px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            padding: 12px;
            border: 1px solid #e5e9ef;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            min-width: 200px;
        }

        .search-box:focus {
            outline: none;
            border-color: #1a5dba;
            box-shadow: 0 0 0 3px rgba(26,93,186,0.1);
        }

        .date-input {
            padding: 12px;
            border: 1px solid #e5e9ef;
            border-radius: 8px;
            font-size: 14px;
            min-width: 150px;
            transition: all 0.3s ease;
        }

        .date-input:focus {
            outline: none;
            border-color: #1a5dba;
            box-shadow: 0 0 0 3px rgba(26,93,186,0.1);
        }

        .card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.05);
            margin-bottom: 24px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            text-align: left;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e9ef;
            vertical-align: top;
        }

        th {
            text-align: left;
            background-color: #f8fafc;
            color: #1a5dba;
            font-weight: 600;
            font-size: 14px;
        }

        td .feedback-message {
            text-align: left;
        }

        .feedback-message {
            max-width: 400px;
            white-space: pre-wrap;
            word-wrap: break-word;
            background-color: #f8fafc;
            padding: 12px;
            border-radius: 8px;
            border-left: 4px solid #1a5dba;
            margin: 4px 0;
            font-size: 14px;
            line-height: 1.6;
            color: #444;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            text-align: left;
            margin-left: 0;
            margin-right: auto;
            display: block;
        }

        .subject-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background-color: #e0e7ff;
            color: #1a5dba;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 500;
        }

        .student-name {
            font-weight: 600;
            color: #1a5dba;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .student-details {
            color: #666;
            font-size: 13px;
            margin-top: 4px;
        }

        .date-cell {
            white-space: nowrap;
            color: #666;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        @media (max-width: 1024px) {
            .mobile-menu-toggle {
                display: block;
            }

            .nav-menu {
                display: none;
                width: 100%;
                padding: 15px 0;
                margin-top: 15px;
                border-top: 1px solid rgba(255,255,255,0.1);
            }

            .nav-menu.active {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 8px;
            }

            .navbar {
                flex-wrap: wrap;
            }

            .navbar-brand {
                flex: 1;
            }

            .nav-link {
                justify-content: center;
            }

            .search-container {
                flex-direction: column;
            }

            .search-box, .date-input {
                width: 100%;
            }

            .feedback-message {
                max-width: 100%;
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
        <div class="mobile-menu-toggle" onclick="toggleMenu()">
            <i class="fas fa-bars"></i>
        </div>
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

    <div class="main-container">
        <div class="page-header">
            <h1><i class="fas fa-comments"></i> Student Feedback</h1>
        </div>

        <div class="search-container">
            <input type="date" id="dateFilter" class="date-input" onchange="filterFeedback()">
            <input type="text" id="studentFilter" class="search-box" onkeyup="filterFeedback()" placeholder="Search by student name or ID...">
        </div>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Course</th>
                        <th>Year Level</th>
                        <th>Room</th>
                        <th>Purpose</th>
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
                                    <div class="student-name">
                                        <i class="fas fa-user"></i>
                                        <?php echo htmlspecialchars($feedback["student_name"]); ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($feedback["COURSE"]); ?></td>
                                <td><?php echo htmlspecialchars($feedback["YEARLEVEL"]); ?></td>
                                <td><?php echo isset($feedback["room"]) ? htmlspecialchars($feedback["room"]) : 'N/A'; ?></td>
                                <td><?php echo isset($feedback["purpose"]) ? htmlspecialchars($feedback["purpose"]) : 'N/A'; ?></td>
                                <td><span class="subject-tag"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($feedback["subject"]); ?></span></td>
                                <td style="text-align: left; vertical-align: top;">
                                    <div style="text-align: left !important; display: block; position: relative;">
                                        <?php echo nl2br(htmlspecialchars($feedback["message"])); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="date-cell">
                                        <i class="far fa-calendar-alt"></i>
                                        <?php echo date('M d, Y h:i A', strtotime($feedback["date_submitted"])); ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 24px;">
                                <i class="fas fa-inbox" style="font-size: 24px; color: #666; margin-bottom: 8px;"></i>
                                <p style="margin: 0; color: #666;">No feedback found.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function toggleMenu() {
            const menu = document.querySelector('.nav-menu');
            menu.classList.toggle('active');
        }

        function filterFeedback() {
            var dateFilter = document.getElementById("dateFilter").value;
            var studentFilter = document.getElementById("studentFilter").value.toLowerCase();
            var table = document.querySelector("table");
            var tr = table.getElementsByTagName("tr");

            for (var i = 1; i < tr.length; i++) {
                var td = tr[i].getElementsByTagName("td");
                if (td.length === 0) continue;
                
                var found = true;
                var rowDate = td[7].textContent || td[7].innerText;
                var rowDateFormatted = new Date(rowDate).toISOString().split('T')[0];

                if (dateFilter && rowDateFormatted !== dateFilter) {
                    found = false;
                }

                if (studentFilter) {
                    var nameMatch = false;
                    // Check name and course columns
                    for (var j = 0; j < 3; j++) {
                        var text = td[j].textContent || td[j].innerText;
                        if (text.toLowerCase().indexOf(studentFilter) > -1) {
                            nameMatch = true;
                            break;
                        }
                    }
                    if (!nameMatch) found = false;
                }

                tr[i].style.display = found ? "" : "none";
            }
        }
    </script>
</body>
</html>