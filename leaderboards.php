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
            width: 80%;
            margin: 30px auto;
        }
        .table-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .table-container h3 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #144c94;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #144c94;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .points-form {
            display: inline-block;
        }
        .points-input {
            width: 60px;
            padding: 5px;
            margin-right: 5px;
        }
        .submit-btn {
            background-color: #144c94;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
        }
        .submit-btn:hover {
            background-color: #0d3a75;
        }
        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .rank {
            font-weight: bold;
            color: #144c94;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="admin_dashboard.php" style="font-size: 1.2rem; font-weight: 600;">Admin Dashboard</a>
        <div>
            <a href="announcement.php">Announcements</a>
            <a href="student_list.php">View Student List</a>
            <a href="view_feedback.php">View Feedback</a>
            <a href="students.php">Sit-in</a>
            <a href="sitin_view.php">Current Sit-in</a>
            <a href="session_history.php">Sit-in Reports</a>
            <a href="sitin_history.php">Sit-in History</a>
            <a href="leaderboards.php">Leaderboards</a>
            <a href="login.php" style="color: #ffd700;">Log out</a>
        </div>
    </div>

    <div class="container">
        <div class="table-container">
            <h3>Student Leaderboards</h3>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-error"><?php echo $error_message; ?></div>
            <?php endif; ?>

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
                            <td><span class="rank">#<?php echo $rank++; ?></span></td>
                            <td><?php echo htmlspecialchars($row['FIRSTNAME'] . ' ' . $row['LASTNAME']); ?></td>
                            <td><?php echo htmlspecialchars($row['IDNO']); ?></td>
                            <td><?php echo htmlspecialchars($row['COURSE']); ?></td>
                            <td><?php echo htmlspecialchars($row['YEARLEVEL']); ?></td>
                            <td><?php echo htmlspecialchars($row['points'] ?? 0); ?></td>
                            <td>
                                <form method="POST" class="points-form">
                                    <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($row['IDNO']); ?>">
                                    <input type="number" name="points" class="points-input" placeholder="Points" required>
                                    <button type="submit" name="update_points" class="submit-btn">Award Points</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>