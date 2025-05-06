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
        }

        .main-container {
            width: 95%;
            max-width: 1400px;
            margin: 20px auto;
            padding: 0;
        }

        .dashboard-header {
            background: white;
            padding: 25px 30px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .dashboard-header h1 {
            color: #1a5dba;
            font-size: 24px;
            margin: 0;
            font-weight: 600;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            transition: transform 0.2s ease;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .stat-label {
            color: #666;
            font-size: 15px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            font-weight: 500;
        }

        .stat-label i {
            margin-right: 8px;
            color: #1a5dba;
        }

        .stat-value {
            color: #1a5dba;
            font-size: 32px;
            font-weight: 700;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }

        .card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.05);
        }

        .card h2 {
            color: #1a5dba;
            font-size: 18px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f2f5;
            font-weight: 600;
            display: flex;
            align-items: center;
        }

        .card h2 i {
            margin-right: 10px;
        }

        .recent-activity {
            margin-top: 15px;
        }

        .activity-item {
            padding: 16px;
            background: #f8fafc;
            margin-bottom: 12px;
            border: 1px solid #e5e9ef;
            border-radius: 8px;
            transition: transform 0.2s ease;
        }

        .activity-item:hover {
            transform: translateX(4px);
            border-left: 3px solid #1a5dba;
        }

        .activity-item h3 {
            font-size: 15px;
            color: #1a5dba;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .activity-item p {
            font-size: 14px;
            color: #666;
            margin: 0;
            line-height: 1.5;
        }

        .announcement-list {
            max-height: 500px;
            overflow-y: auto;
            padding-right: 8px;
        }

        .announcement {
            padding: 16px;
            background: #f8fafc;
            margin-bottom: 12px;
            border: 1px solid #e5e9ef;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .announcement:hover {
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .announcement strong {
            display: block;
            font-size: 15px;
            color: #1a5dba;
            margin-bottom: 6px;
            font-weight: 600;
        }

        .announcement small {
            display: block;
            font-size: 13px;
            color: #666;
            margin-bottom: 8px;
        }

        .announcement p {
            font-size: 14px;
            color: #444;
            line-height: 1.6;
            margin: 0;
        }

        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
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
        <a href="#" class="navbar-brand">
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
        <div class="dashboard-header">
            <h1><i class="fas fa-tachometer-alt"></i> Dashboard Overview</h1>
        </div>

        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-label"><i class="fas fa-user-graduate"></i> Total Students</div>
                <div class="stat-value"><?php echo $total_students; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label"><i class="fas fa-user-clock"></i> Currently Sitting In</div>
                <div class="stat-value"><?php echo $current_sitin; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label"><i class="fas fa-calendar-check"></i> Total Sessions</div>
                <div class="stat-value"><?php echo $total_sitin; ?></div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="card">
                <h2><i class="fas fa-stream"></i> Recent Activities</h2>
                <div class="recent-activity">
                    <?php while ($activity = mysqli_fetch_assoc($recent_activity_result)) { ?>
                        <div class="activity-item">
                            <h3><i class="fas fa-user"></i> <?php echo htmlspecialchars($activity["student_name"]); ?></h3>
                            <p>
                                <i class="fas fa-tasks"></i> Purpose: <?php echo htmlspecialchars($activity["purpose"]); ?> | 
                                <i class="fas fa-laptop"></i> Lab: <?php echo htmlspecialchars($activity["lab"]); ?><br>
                                <i class="far fa-clock"></i> Time: <?php echo $activity["time_in"]; ?> - <?php echo $activity["time_out"] ? $activity["time_out"] : 'Ongoing'; ?>
                            </p>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <div class="card">
                <h2><i class="fas fa-chart-pie"></i> Course Distribution</h2>
                <canvas id="courseChart"></canvas>
            </div>
        </div>

        <div class="card">
            <h2><i class="fas fa-bullhorn"></i> Recent Announcements</h2>
            <div class="announcement-list">
                <?php while ($announcement = mysqli_fetch_assoc($announcement_result)) { ?>
                    <div class="announcement">
                        <strong><i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($announcement["title"]); ?></strong>
                        <small><i class="far fa-calendar-alt"></i> <?php echo date("F j, Y", strtotime($announcement["date_posted"])); ?></small>
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
                        '#4299e1',
                        '#48bb78',
                        '#ed64a6',
                        '#ecc94b',
                        '#667eea'
                    ],
                    borderWidth: 2
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
                                family: 'Inter'
                            }
                        }
                    }
                },
                cutout: '65%'
            }
        });

        function toggleMenu() {
            const menu = document.querySelector('.nav-menu');
            menu.classList.toggle('active');
        }
    </script>
</body>
</html>
