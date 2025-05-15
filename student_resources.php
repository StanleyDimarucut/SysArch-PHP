<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

include("db.php");

// Get the logged-in user's ID for notifications
$user_id = $_SESSION['IDNO'] ?? ($_SESSION['user_id'] ?? null);
$notif_result = false;
if ($user_id) {
    $notif_query = "SELECT * FROM notifications WHERE user_id = $user_id AND is_read = 0 ORDER BY created_at DESC";
    $notif_result = mysqli_query($con, $notif_query);
}

$notif_count = 0;
if ($notif_result) {
    $notif_count = mysqli_num_rows($notif_result);
}

// Handle marking notification as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'], $_POST['notif_id'])) {
    $notif_id = intval($_POST['notif_id']);
    mysqli_query($con, "UPDATE notifications SET is_read = 1 WHERE id = $notif_id");
    header('Location: student_resources.php');
    exit();
}

// Fetch resources from database
$sql = "SELECT * FROM resources ORDER BY upload_date DESC";
$result = $con->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS | Resources</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .navbar a:hover {
            background: rgba(255,255,255,0.15);
            color: #ffd700;
            transform: translateY(-1px);
        }

        .navbar a.logout {
            background: rgba(255,217,0,0.15);
            color: #ffd700;
            border: 1px solid rgba(255,217,0,0.3);
        }

        .navbar a.logout:hover {
            background: rgba(255,217,0,0.25);
        }

        .notification-bell {
            position: relative;
            display: inline-block;
            margin-left: 18px;
            cursor: pointer;
            font-size: 22px;
            color: #fff;
            transition: color 0.2s;
        }

        .notification-bell:hover {
            color: #ffd700;
        }

        .notif-badge {
            position: absolute;
            top: -6px;
            right: -8px;
            background: #dc3545;
            color: #fff;
            border-radius: 50%;
            padding: 2px 7px;
            font-size: 12px;
            font-weight: bold;
        }

        .notif-dropdown {
            position: absolute;
            right: 30px;
            top: 60px;
            background: #fffbe6;
            border: 1px solid #ffe58f;
            border-radius: 8px;
            min-width: 300px;
            z-index: 2000;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            padding: 16px 12px 12px 12px;
        }

        .notif-dropdown h4 {
            margin: 0 0 10px 0;
            color: #d48806;
            font-size: 16px;
        }

        .notif-dropdown ul {
            list-style: none;
            padding: 0;
            margin: 0;
            max-height: 250px;
            overflow-y: auto;
        }

        .notif-dropdown li {
            margin-bottom: 8px;
            font-size: 15px;
            padding: 8px;
            border-bottom: 1px solid #ffe58f;
        }

        .notif-dropdown li:last-child {
            border-bottom: none;
        }

        .btn-mark-read {
            background: #1a5dba;
            color: white;
            border: none;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            margin-left: 8px;
        }

        .btn-mark-read:hover {
            background: #144c94;
        }

        @media (max-width: 1024px) {
            .notification-bell {
                top: 18px;
                right: 18px;
            }
        }

        .main-container {
            margin-top: 80px;
            padding: 2rem;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
            width: 95%;
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

        .resources-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 24px;
        }

        .resource-card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }

        .resource-card:hover {
            transform: translateY(-4px);
        }

        .resource-card h3 {
            color: #1a5dba;
            font-size: 18px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .resource-card p {
            color: #666;
            font-size: 14px;
            margin-bottom: 16px;
            line-height: 1.6;
        }

        .resource-card .meta {
            color: #888;
            font-size: 13px;
            margin-bottom: 16px;
        }

        .download-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #144c94 0%, #1a5dba 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .download-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(26,93,186,0.2);
        }

        @media (max-width: 768px) {
            .resources-grid {
                grid-template-columns: 1fr;
            }
            .main-container {
                padding: 1rem;
            }
            .navbar {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="#" style="font-size: 1.2rem; font-weight: 600;"><i class="fas fa-book"></i> Student Resources</a>
        <div>
            <a href="dashboard.php"><i class="fas fa-home"></i> Home</a>
            <a href="profile.php"><i class="fas fa-user-edit"></i> Edit Profile</a>
            <a href="student_lab_schedule.php"><i class="fas fa-calendar-alt"></i> Lab Schedule</a>
            <a href="history.php"><i class="fas fa-history"></i> History</a>
            <a href="student_resources.php" class="active"><i class="fas fa-book"></i>Student Resources</a>
            <a href="Reservation.php"><i class="fas fa-calendar-plus"></i> Reservation</a>
            <a href="login.php" class="logout"><i class="fas fa-sign-out-alt"></i> Log out</a>
        </div>
        <div class="notification-bell" onclick="toggleNotifDropdown()">
            <i class="fas fa-bell"></i>
            <?php if ($notif_count > 0): ?>
                <span class="notif-badge"><?php echo $notif_count; ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div id="notifDropdown" class="notif-dropdown" style="display:none;">
        <h4>Notifications</h4>
        <ul>
            <?php
            if ($notif_result && mysqli_num_rows($notif_result) > 0) {
                mysqli_data_seek($notif_result, 0);
                while($notif = mysqli_fetch_assoc($notif_result)): ?>
                    <li>
                        <?php echo htmlspecialchars($notif['message']); ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="notif_id" value="<?php echo $notif['id']; ?>">
                            <button type="submit" name="mark_read" class="btn-mark-read">Mark as read</button>
                        </form>
                    </li>
                <?php endwhile;
            } else {
                echo '<li>No new notifications.</li>';
            }
            ?>
        </ul>
    </div>

    <div class="main-container">
        <div class="page-header">
            <h1><i class="fas fa-book"></i> Learning Resources</h1>
        </div>

        <div class="resources-grid">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="resource-card">
                        <h3><i class="fas fa-file-alt"></i> <?php echo htmlspecialchars($row['title']); ?></h3>
                        <p><?php echo htmlspecialchars($row['description']); ?></p>
                        <div class="meta">
                            <i class="fas fa-calendar-alt"></i> 
                            Added on <?php echo date('F j, Y', strtotime($row['upload_date'])); ?>
                        </div>
                        <a href="<?php echo htmlspecialchars($row['file_path']); ?>" class="download-btn" target="_blank">
                            <i class="fas fa-download"></i> Download Resource
                        </a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="resource-card">
                    <h3><i class="fas fa-info-circle"></i> No Resources Available</h3>
                    <p>There are currently no resources available. Please check back later.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function toggleNotifDropdown() {
        var dropdown = document.getElementById('notifDropdown');
        dropdown.style.display = (dropdown.style.display === 'none' || dropdown.style.display === '') ? 'block' : 'none';
    }
    document.addEventListener('click', function(event) {
        var bell = document.querySelector('.notification-bell');
        var dropdown = document.getElementById('notifDropdown');
        if (!bell.contains(event.target) && !dropdown.contains(event.target)) {
            dropdown.style.display = 'none';
        }
    });
    </script>
</body>
</html>