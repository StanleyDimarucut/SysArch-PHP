<?php
session_start();
include("db.php");

// Check if user is logged in
if (!isset($_SESSION['admin_username'])) {
    header('Location: login.php');
    exit();
}

// Create resources table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    upload_date DATETIME NOT NULL
)";

if (!$con->query($sql)) {
    die("Error creating table: " . $con->error);
}

$message = '';

// Fetch admin notifications
$admin_notif_query = "SELECT * FROM notifications WHERE for_admin = 1 AND is_read = 0 ORDER BY created_at DESC";
$admin_notif_result = mysqli_query($con, $admin_notif_query);
$admin_notif_count = $admin_notif_result ? mysqli_num_rows($admin_notif_result) : 0;

// Handle marking notification as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'], $_POST['notif_id'])) {
    $notif_id = intval($_POST['notif_id']);
    mysqli_query($con, "UPDATE notifications SET is_read = 1 WHERE id = $notif_id");
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    
    // File upload handling
    if (isset($_FILES['resource_file']) && $_FILES['resource_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['resource_file'];
        $fileName = time() . '_' . basename($file['name']);
        $uploadDir = 'uploads/resources/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Insert into database
            $sql = "INSERT INTO resources (title, description, file_path, upload_date) VALUES (?, ?, ?, NOW())";
            $stmt = $con->prepare($sql);
            $stmt->bind_param("sss", $title, $description, $targetPath);
            
            if ($stmt->execute()) {
                $message = "Resource added successfully!";
                // --- NOTIFICATION LOGIC START ---
                $students_query = "SELECT IDNO FROM register WHERE USERNAME != 'admin'";
                $students_result = mysqli_query($con, $students_query);
                while ($student = mysqli_fetch_assoc($students_result)) {
                    $student_id = $student['IDNO'];
                    $notif_msg = "A new resource has been added: \"$title\"";
                    $notif_query = "INSERT INTO notifications (user_id, message) VALUES ($student_id, '$notif_msg')";
                    mysqli_query($con, $notif_query);
                }
                // --- NOTIFICATION LOGIC END ---
            } else {
                $message = "Error adding resource: " . $con->error;
            }
        } else {
            $message = "Error uploading file.";
        }
    } else {
        $message = "Please select a file to upload.";
    }
}

// Handle resource deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = intval($_POST['delete_id']);

    // Get the file path before deleting
    $query = "SELECT file_path FROM resources WHERE id = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->bind_result($file_path);
    $stmt->fetch();
    $stmt->close();

    // Delete the resource from the database
    $delete_query = "DELETE FROM resources WHERE id = ?";
    $delete_stmt = $con->prepare($delete_query);
    $delete_stmt->bind_param("i", $delete_id);
    $delete_stmt->execute();
    $delete_stmt->close();

    // Optionally, delete the file from the server
    if ($file_path && file_exists($file_path)) {
        unlink($file_path);
    }

    // (Optional) Add notification logic here if you want to notify students about deletion

    header("Location: resources.php?success=Resource deleted successfully!");
    exit();
}

// Fetch existing resources
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

        .card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            margin-bottom: 24px;
        }

        .card-header {
            padding-bottom: 16px;
            margin-bottom: 20px;
            border-bottom: 1px solid #e5e9ef;
        }

        .card-header h2 {
            color: #1a5dba;
            font-size: 18px;
            margin: 0;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #444;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #e5e9ef;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #1a5dba;
            box-shadow: 0 0 0 3px rgba(26,93,186,0.1);
        }

        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid #e5e9ef;
            font-size: 14px;
        }

        .table th {
            background-color: #f8fafc;
            color: #1a5dba;
            font-weight: 600;
            white-space: nowrap;
        }

        .table td {
            color: #444;
        }

        .table tbody tr:hover {
            background-color: #f8fafc;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #144c94 0%, #1a5dba 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(26,93,186,0.2);
        }

        .btn-warning {
            background-color: #ffd700;
            color: #000;
        }

        .btn-danger {
            background-color: #dc3545;
            color: white;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
        }

        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-info {
            background-color: #e7f1ff;
            color: #144c94;
            border: 1px solid #b6d4fe;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .notification-bell {
            position: absolute;
            top: 18px;
            right: 40px;
            display: inline-block;
            cursor: pointer;
            font-size: 22px;
            color: #fff;
            transition: color 0.2s;
            padding: 8px;
            border-radius: 8px;
            z-index: 1100;
        }

        .notification-bell:hover {
            color: #ffd700;
            background: rgba(255,255,255,0.1);
        }

        .notif-badge {
            position: absolute;
            top: 0;
            right: 0;
            background: #dc3545;
            color: #fff;
            border-radius: 50%;
            padding: 2px 7px;
            font-size: 12px;
            font-weight: bold;
        }

        .notif-dropdown {
            position: absolute;
            right: 15px;
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

            .notification-bell {
                top: 18px;
                right: 18px;
            }
        }

        @media (max-width: 768px) {
            .main-container {
                width: 90%;
                padding: 16px;
            }

            .card {
                padding: 16px;
            }

            .table th,
            .table td {
                padding: 12px 8px;
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
        <div class="notification-bell" onclick="toggleAdminNotifDropdown()">
            <i class="fas fa-bell"></i>
            <?php if ($admin_notif_count > 0): ?>
                <span class="notif-badge"><?php echo $admin_notif_count; ?></span>
            <?php endif; ?>
        </div>
        <div class="mobile-menu-toggle" onclick="toggleMenu()">
            <i class="fas fa-bars"></i>
        </div>
        <div class="nav-menu">
            <a href="announcement.php" class="nav-link"><i class="fas fa-bullhorn"></i> Announcements</a>
            <a href="student_list.php" class="nav-link"><i class="fas fa-users"></i> Student List</a>
            <a href="view_feedback.php" class="nav-link"><i class="fas fa-comments"></i> Feedback</a>
            <a href="view_reservations.php" class="nav-link"><i class="fas fa-calendar-alt"></i> View Reservations</a>
            <a href="students.php" class="nav-link"><i class="fas fa-user-check"></i> Sit-in</a>
            <a href="sitin_view.php" class="nav-link"><i class="fas fa-clock"></i> Current Sit-in</a>
            <a href="session_history.php" class="nav-link"><i class="fas fa-history"></i> Reports</a>
            <a href="sitin_history.php" class="nav-link"><i class="fas fa-calendar-alt"></i> History</a>
            <a href="leaderboards.php" class="nav-link"><i class="fas fa-trophy"></i> Leaderboards</a>
            <a href="resources.php" class="nav-link"><i class="fas fa-book"></i> Resources</a>
            <a href="PC_management.php" class="nav-link"><i class="fas fa-desktop"></i> PC Management</a>
            <a href="lab_schedule.php" class="nav-link"><i class="fas fa-calendar-alt"></i> Lab Schedule</a>
            <a href="login.php" class="nav-link logout"><i class="fas fa-sign-out-alt"></i> Log out</a>
        </div>
    </div>

    <div id="adminNotifDropdown" class="notif-dropdown" style="display:none;">
        <h4>Admin Notifications</h4>
        <ul>
            <?php
            if ($admin_notif_result && mysqli_num_rows($admin_notif_result) > 0) {
                while($notif = mysqli_fetch_assoc($admin_notif_result)): ?>
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
            <h1><i class="fas fa-book"></i> Manage Resources</h1>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2>Add New Resource</h2>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" class="form-control" id="title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label for="resource_file">File</label>
                    <input type="file" class="form-control" id="resource_file" name="resource_file" required>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    Add Resource
                </button>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Existing Resources</h2>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Description</th>
                            <th>File</th>
                            <th>Date Added</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                            <td>
                                <a href="<?php echo htmlspecialchars($row['file_path']); ?>" class="btn btn-sm btn-primary" target="_blank">
                                    <i class="fas fa-download"></i>
                                    Download
                                </a>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($row['upload_date'])); ?></td>
                            <td>
                                <a href="edit_resource.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                    Edit
                                </a>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this resource?');" style="display:inline;">
                                    <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i> Delete
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

    <script>
        function toggleMenu() {
            const navMenu = document.querySelector('.nav-menu');
            navMenu.classList.toggle('active');
        }

        function toggleAdminNotifDropdown() {
            var dropdown = document.getElementById('adminNotifDropdown');
            dropdown.style.display = (dropdown.style.display === 'none' || dropdown.style.display === '') ? 'block' : 'none';
        }

        document.addEventListener('click', function(event) {
            var bell = document.querySelector('.notification-bell');
            var dropdown = document.getElementById('adminNotifDropdown');
            if (!bell.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.style.display = 'none';
            }
        });
    </script>
</body>
</html>
