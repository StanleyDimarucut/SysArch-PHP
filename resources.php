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

// Fetch existing resources
$sql = "SELECT * FROM resources ORDER BY upload_date DESC";
$result = $con->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Resources</title>
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
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .navbar a {
            color: white;
            text-decoration: none;
            margin: 0 15px;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 8px 12px;
            border-radius: 6px;
        }
        .navbar a:hover {
            background: rgba(255,255,255,0.1);
            color: #ffd700;
            transform: translateY(-1px);
        }
        .main-container {
            width: 95%;
            max-width: 1400px;
            margin: 20px auto;
            padding: 0;
        }
        .card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.05);
            margin-bottom: 24px;
        }
        .card-header {
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            padding: 16px 24px;
            border-radius: 12px 12px 0 0;
        }
        .card-header h4 {
            margin: 0;
            color: #1a5dba;
            font-size: 20px;
            font-weight: 600;
        }
        .alert-info {
            background: #e7f1ff;
            color: #144c94;
            border: 1px solid #b6d4fe;
            border-radius: 8px;
            padding: 12px 20px;
            margin-bottom: 20px;
        }
        .table-responsive {
            overflow-x: auto;
        }
        table.table {
            width: 100%;
            border-collapse: collapse;
        }
        table.table th, table.table td {
            padding: 12px 10px;
            border-bottom: 1px solid #e9ecef;
            text-align: left;
        }
        table.table th {
            background: #f8f9fa;
            color: #1a5dba;
            font-weight: 600;
        }
        table.table tr:last-child td {
            border-bottom: none;
        }
        .btn-primary {
            background: #1a5dba;
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            transition: background 0.2s;
        }
        .btn-primary:hover {
            background: #144c94;
        }
        .btn-warning {
            background: #ffc107;
            color: #212529;
            border: none;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
            border: none;
        }
        .btn-sm {
            padding: 5px 12px;
            font-size: 14px;
        }
    </style>
</head>
<body>
<div class="navbar">
        <a href="admin_dashboard.php" style="font-size: 1.2rem; font-weight: 600;"><i class="fas fa-chart-line"></i> Admin Dashboard</a>
        <div>
            <a href="announcement.php"><i class="fas fa-bullhorn"></i> Announcements</a>
            <a href="student_list.php"><i class="fas fa-users"></i> Student List</a>
            <a href="view_feedback.php"><i class="fas fa-comments"></i> Feedback</a>
            <a href="students.php"><i class="fas fa-user-check"></i> Sit-in</a>
            <a href="sitin_view.php"><i class="fas fa-clock"></i> Current Sit-in</a>
            <a href="session_history.php"><i class="fas fa-history"></i> Sit-in Reports</a>
            <a href="sitin_history.php"><i class="fas fa-calendar-alt"></i> Sit-in History</a>
            <a href="leaderboards.php"><i class="fas fa-trophy"></i> Leaderboards</a>
            <a href="resources.php"><i class="fas fa-book"></i> Resources</a>
            <a href="login.php" style="color: #ffd700;"><i class="fas fa-sign-out-alt"></i> Log out</a>
        </div>
    </div>
    </div>
    <div class="main-container">
        <h2 style="margin-top: 24px; color: #1a5dba; font-weight: 700;">Manage Resources</h2>
        <?php if ($message): ?>
            <div class="alert alert-info"><?php echo $message; ?></div>
        <?php endif; ?>
        <!-- Add Resource Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h4>Add New Resource</h4>
            </div>
            <div class="card-body">
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="mb-3" style="margin-bottom: 18px;">
                        <label for="title" class="form-label" style="font-weight: 500;">Title</label>
                        <input type="text" class="form-control" id="title" name="title" required style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #e0e0e0;">
                    </div>
                    <div class="mb-3" style="margin-bottom: 18px;">
                        <label for="description" class="form-label" style="font-weight: 500;">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" required style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #e0e0e0;"></textarea>
                    </div>
                    <div class="mb-3" style="margin-bottom: 18px;">
                        <label for="resource_file" class="form-label" style="font-weight: 500;">File</label>
                        <input type="file" class="form-control" id="resource_file" name="resource_file" required style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid #e0e0e0;">
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-top: 8px;">Add Resource</button>
                </form>
            </div>
        </div>
        <!-- Display Existing Resources -->
        <div class="card">
            <div class="card-header">
                <h4>Existing Resources</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" style="width: 100%;">
                        <thead>
                            <tr>
                                <th style="min-width: 120px;">Title</th>
                                <th style="min-width: 180px;">Description</th>
                                <th style="min-width: 100px;">File</th>
                                <th style="min-width: 120px;">Date Added</th>
                                <th style="min-width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                <td><?php echo htmlspecialchars($row['description']); ?></td>
                                <td>
                                    <a href="<?php echo htmlspecialchars($row['file_path']); ?>" target="_blank">
                                        Download
                                    </a>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($row['upload_date'])); ?></td>
                                <td>
                                    <a href="edit_resource.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                    <a href="delete_resource.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this resource?')">Delete</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
