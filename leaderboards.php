<?php
session_start();
if (!isset($_SESSION["admin_username"])) {
    header("Location: login.php");
    exit();
}

include("db.php");

// Handle point updates
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_points'])) {
    $student_id = $_POST['student_id'];
    $points = $_POST['points'];
    
    $update_query = "UPDATE register SET points = points + ? WHERE IDNO = ?";
    $stmt = mysqli_prepare($con, $update_query);
    mysqli_stmt_bind_param($stmt, "is", $points, $student_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $success_message = "Points updated successfully!";
    } else {
        $error_message = "Error updating points!";
    }
}

// Fetch all students with their points
$query = "SELECT IDNO, FIRSTNAME, LASTNAME, COURSE, YEARLEVEL, points 
          FROM register 
          WHERE USERNAME != 'admin'
          ORDER BY points DESC";
$result = mysqli_query($con, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS | Leaderboards</title>
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
        }

        .card {
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

        .rank {
            font-weight: 600;
            color: #1a5dba;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .rank-1 {
            color: #ffd700;
        }

        .rank-2 {
            color: #c0c0c0;
        }

        .rank-3 {
            color: #cd7f32;
        }

        .points-container {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .points-badge {
            background: #e0e7ff;
            color: #1a5dba;
            padding: 4px 12px;
            border-radius: 999px;
            font-weight: 600;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .points-form {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .points-input {
            padding: 8px 12px;
            border: 1px solid #e5e9ef;
            border-radius: 8px;
            width: 80px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .points-input:focus {
            outline: none;
            border-color: #1a5dba;
            box-shadow: 0 0 0 3px rgba(26,93,186,0.1);
        }

        .btn {
            padding: 8px 16px;
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

        .btn-award {
            background: linear-gradient(135deg, #144c94 0%, #1a5dba 100%);
            color: white;
        }

        .btn-award:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(26,93,186,0.2);
        }

        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-success {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid rgba(22,101,52,0.1);
        }

        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid rgba(153,27,27,0.1);
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
                gap: 16px;
                align-items: flex-start;
            }

            .points-form {
                flex-wrap: wrap;
            }

            .points-input, .btn {
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
        <div class="card">
            <div class="page-header">
                <h2><i class="fas fa-trophy"></i> Student Leaderboards</h2>
            </div>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Student Name</th>
                            <th>ID Number</th>
                            <th>Course</th>
                            <th>Year Level</th>
                            <th>Points</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $rank = 1;
                        while ($row = mysqli_fetch_assoc($result)): 
                        ?>
                            <tr>
                                <td>
                                    <span class="rank <?php echo $rank <= 3 ? 'rank-' . $rank : ''; ?>">
                                        <?php if ($rank <= 3): ?>
                                            <i class="fas fa-trophy"></i>
                                        <?php endif; ?>
                                        #<?php echo $rank++; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="student-name">
                                        <i class="fas fa-user"></i>
                                        <?php echo htmlspecialchars($row['FIRSTNAME'] . ' ' . $row['LASTNAME']); ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($row['IDNO']); ?></td>
                                <td><?php echo htmlspecialchars($row['COURSE']); ?></td>
                                <td><?php echo htmlspecialchars($row['YEARLEVEL']); ?></td>
                                <td>
                                    <span class="points-badge">
                                        <i class="fas fa-star"></i>
                                        <?php echo htmlspecialchars($row['points'] ?? 0); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" class="points-form">
                                        <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($row['IDNO']); ?>">
                                        <input type="number" name="points" class="points-input" placeholder="Points" required min="1">
                                        <button type="submit" name="update_points" class="btn btn-award">
                                            <i class="fas fa-plus-circle"></i>
                                            Award
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>