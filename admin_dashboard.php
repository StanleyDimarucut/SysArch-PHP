<?php
session_start();
if (!isset($_SESSION["admin_username"])) {
    header("Location: login.php");
    exit();
}

// Connect to the database
include("db.php");
$admin_username = $_SESSION["admin_username"];

// Get total students
$total_students_query = "SELECT COUNT(*) as total FROM register WHERE USERNAME != 'admin'";
$total_result = mysqli_query($con, $total_students_query);
$total_students = mysqli_fetch_assoc($total_result)['total'];

// Get currently active sit-ins (students who are logged in but haven't logged out)
$current_sitin_query = "SELECT COUNT(*) as current 
                       FROM sit_in_records 
                       WHERE date = CURDATE() 
                       AND time_in IS NOT NULL 
                       AND time_out IS NULL";
$current_result = mysqli_query($con, $current_sitin_query);
$current_sitin = mysqli_fetch_assoc($current_result)['current'];

// Get total sit-ins (all completed sessions)
$total_sitin_query = "SELECT COUNT(*) as total 
                      FROM sit_in_records 
                      WHERE time_out IS NOT NULL";
$total_sitin_result = mysqli_query($con, $total_sitin_query);
$total_sitin = mysqli_fetch_assoc($total_sitin_result)['total'];

// Get course statistics for pie chart
$course_stats_query = "SELECT COURSE, COUNT(*) as count FROM register WHERE USERNAME != 'admin' GROUP BY COURSE";
$course_stats_result = mysqli_query($con, $course_stats_query);
$course_labels = [];
$course_counts = [];
while($row = mysqli_fetch_assoc($course_stats_result)) {
    $course_labels[] = $row['COURSE'];
    $course_counts[] = $row['count'];
}

// Get recent sit-in activities
$recent_activity_query = "SELECT 
    s.student_id,
    CONCAT(r.FIRSTNAME, ' ', r.LASTNAME) as student_name,
    s.purpose,
    s.lab,
    DATE_FORMAT(s.time_in, '%h:%i %p') as time_in,
    DATE_FORMAT(s.time_out, '%h:%i %p') as time_out,
    s.date
FROM sit_in_records s
JOIN register r ON s.student_id = r.IDNO
ORDER BY s.date DESC, s.time_in DESC
LIMIT 10";
$recent_activity_result = mysqli_query($con, $recent_activity_query);

// Fetch latest announcements
$announcement_query = "SELECT title, content, date_posted FROM announcements ORDER BY date_posted DESC LIMIT 5";
$announcement_result = mysqli_query($con, $announcement_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS | Admin Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .main-container {
            width: 95%;
            margin: 20px auto;
            padding: 0;
        }

        .dashboard-header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .dashboard-header h1 {
            color: #144c94;
            font-size: 24px;
            margin: 0;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .stat-label {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .stat-value {
            color: #144c94;
            font-size: 24px;
            font-weight: bold;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .card h2 {
            color: #144c94;
            font-size: 18px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }

        .recent-activity {
            margin-top: 15px;
        }

        .activity-item {
            padding: 15px;
            background: #f9f9f9;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .activity-item h3 {
            font-size: 14px;
            color: #144c94;
            margin-bottom: 5px;
        }

        .activity-item p {
            font-size: 13px;
            color: #666;
            margin: 0;
        }

        .announcement-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .announcement {
            padding: 15px;
            background: #f9f9f9;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .announcement strong {
            display: block;
            font-size: 14px;
            color: #144c94;
            margin-bottom: 5px;
        }

        .announcement small {
            display: block;
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }

        .announcement p {
            font-size: 13px;
            color: #444;
            line-height: 1.4;
            margin: 0;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            .main-container {
                width: 95%;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="#" style="font-size: 1.2rem; font-weight: 600;">Admin Dashboard</a>
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

    <div class="main-container">
        <div class="dashboard-header">
            <h1>Dashboard Overview</h1>
        </div>

        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-label">Total Students</div>
                <div class="stat-value"><?php echo $total_students; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Currently Sitting In</div>
                <div class="stat-value"><?php echo $current_sitin; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Sessions</div>
                <div class="stat-value"><?php echo $total_sitin; ?></div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="card">
                <h2>Recent Activities</h2>
                <div class="recent-activity">
                    <?php while ($activity = mysqli_fetch_assoc($recent_activity_result)) { ?>
                        <div class="activity-item">
                            <h3><?php echo htmlspecialchars($activity["student_name"]); ?></h3>
                            <p>
                                Purpose: <?php echo htmlspecialchars($activity["purpose"]); ?> | 
                                Lab: <?php echo htmlspecialchars($activity["lab"]); ?><br>
                                Time: <?php echo $activity["time_in"]; ?> - <?php echo $activity["time_out"] ? $activity["time_out"] : 'Ongoing'; ?>
                            </p>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <div class="card">
                <h2>Course Distribution</h2>
                <canvas id="courseChart"></canvas>
            </div>
        </div>

        <div class="card">
            <h2>Recent Announcements</h2>
            <div class="announcement-list">
                <?php while ($announcement = mysqli_fetch_assoc($announcement_result)) { ?>
                    <div class="announcement">
                        <strong><?php echo htmlspecialchars($announcement["title"]); ?></strong>
                        <small><?php echo date("F j, Y", strtotime($announcement["date_posted"])); ?></small>
                        <p><?php echo nl2br(htmlspecialchars($announcement["content"])); ?></p>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('courseChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($course_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($course_counts); ?>,
                    backgroundColor: [
                        '#007bff',
                        '#28a745',
                        '#dc3545',
                        '#ffc107',
                        '#17a2b8'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            font: {
                                size: 12,
                                family: 'Arial'
                            }
                        }
                    }
                },
                cutout: '60%'
            }
        });
    </script>
</body>
</html>
