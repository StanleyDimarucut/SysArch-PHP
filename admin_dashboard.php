<?php
session_start();
if (!isset($_SESSION["admin_username"])) {
    header("Location: login.php");
    exit();
}

// Connect to the database
include("db.php");
$admin_username = $_SESSION["admin_username"];

// Get course statistics for pie chart
$course_stats_query = "SELECT COURSE, COUNT(*) as count FROM register WHERE USERNAME != 'admin' GROUP BY COURSE";
$course_stats_result = mysqli_query($con, $course_stats_query);
$course_labels = [];
$course_counts = [];
while($row = mysqli_fetch_assoc($course_stats_result)) {
    $course_labels[] = $row['COURSE'];
    $course_counts[] = $row['count'];
}

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
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar a {
            color: white;
            text-decoration: none;
            margin: 0 10px;
            font-size: larger;
        }
        .navbar a:hover {
            color: yellow;
        }
        .container {
            display: flex;
            justify-content: space-between;
            padding: 20px;
            gap: 20px;
            width: 100%;
            max-width: 1400px;
            margin: auto;
        }

        .card {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            min-height: 400px;
            display: flex;
            flex-direction: column;
        }

        /* Statistics Card */
        .statistics-card {
            flex: 0.3;
            height: 400px;
        }

        /* Announcements Card */
        .announcement-card {
            flex: 0.7;
        }

        /* Style for announcements */
        .announcement-list {
            text-align: left;
            max-height: 450px;
            overflow-y: auto;
            width: 100%;
            padding: 10px;
        }

        .announcement {
            background: #f9f9f9;
            padding: 16px;
            margin-bottom: 12px;
            border-radius: 6px;
            border-left: 5px solid #144c94;
            word-wrap: break-word;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            width: 95%;
        }

        .announcement strong {
            font-size: 18px;
            color: #144c94;
        }

        .announcement small {
            display: block;
            font-size: 12px;
            color: gray;
            margin-top: 4px;
        }

        .announcement p {
            margin: 6px 0 0;
            font-size: 14px;
            line-height: 1.4;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a>Admin Dashboard</a>
        <div>
            <a href="announcement.php">Create Announcements</a>
            <a href="students.php">Sit-in</a>
            <a href="sitin_view.php">Current Sit-in</a>
            <a href="session_history.php">Sit-in Reports</a>
            <a href="login.php" style="color: orange;">Log out</a>
        </div>
    </div>

    <div class="container">
        <!-- Statistics -->
        <div class="card statistics-card">
            <h3>Statistics</h3>
            <canvas id="courseChart"></canvas>
        </div>

        <!-- Announcements -->
        <div class="card announcement-card">
            <h3>Recent Announcements</h3>
            <div class="announcement-list">
                <?php while ($announcement = mysqli_fetch_assoc($announcement_result)) { ?>
                    <div class="announcement">
                        <strong><?php echo htmlspecialchars($announcement["title"]); ?></strong>
                        <small><?php echo date("Y-M-d", strtotime($announcement["date_posted"])); ?></small>
                        <p><?php echo nl2br(htmlspecialchars($announcement["content"])); ?></p>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <script>
        // Initialize pie chart
        const ctx = document.getElementById('courseChart').getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($course_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($course_counts); ?>,
                    backgroundColor: [
                        '#0D6EFD',  // Blue for CS
                        '#DC3545',  // Red for C
                        '#FFC107',  // Yellow/Orange for BsIT
                        '#198754',  // Green for MSSW
                        '#0DCAF0'   // Light Blue for Phd
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 15,
                            padding: 15,
                            font: {
                                size: 12
                            }
                        }
                    },
                    title: {
                        display: false
                    }
                },
                layout: {
                    padding: {
                        left: 10,
                        right: 10,
                        top: 10,
                        bottom: 10
                    }
                }
            }
        });
    </script>
</body>
</html>
