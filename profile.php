<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

// Connect to the database
include("db.php");
$username = $_SESSION["username"];

// Handle Profile Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = $_POST["firstname"];
    $midname = $_POST["midname"];
    $lastname = $_POST["lastname"];
    $course = $_POST["course"];
    $year = $_POST["year"];

    // Handle file upload (profile picture)
    if (isset($_FILES["profile_img"]) && $_FILES["profile_img"]["error"] == 0) {
        $targetDir = "images/";
        $fileName = basename($_FILES["profile_img"]["name"]);
        $targetFilePath = $targetDir . $fileName;
        $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);

        // Allow only image file types
        $allowedTypes = ["jpg", "jpeg", "png", "gif"];
        if (in_array(strtolower($fileType), $allowedTypes)) {
            move_uploaded_file($_FILES["profile_img"]["tmp_name"], $targetFilePath);
        } else {
            echo "Invalid file type.";
            exit();
        }
    } else {
        // Keep existing image if no new file is uploaded
        $query = "SELECT PROFILE_IMG FROM register WHERE USERNAME=?";
        $stmt = mysqli_prepare($con, $query);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $targetFilePath = $row["PROFILE_IMG"];
    }

    // Update user details in the database
    $query = "UPDATE register SET FIRSTNAME=?, MIDNAME=?, LASTNAME=?, COURSE=?, YEARLEVEL=?, PROFILE_IMG=? WHERE USERNAME=?";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "sssssss", $firstname, $midname, $lastname, $course, $year, $targetFilePath, $username);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: profile.php?success=1");
        exit();
    } else {
        echo "Error updating profile.";
    }
}

// Fetch user details for display
$query = "SELECT FIRSTNAME, MIDNAME, LASTNAME, COURSE, YEARLEVEL, PROFILE_IMG FROM register WHERE USERNAME = ?";
$stmt = mysqli_prepare($con, $query);
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    $firstname = $row["FIRSTNAME"];
    $midname = $row["MIDNAME"];
    $lastname = $row["LASTNAME"];
    $course = $row["COURSE"];
    $year = $row["YEARLEVEL"];
    $profile_img = !empty($row["PROFILE_IMG"]) ? $row["PROFILE_IMG"] : "images/default.jpg"; // Default image
} else {
    echo "User not found!";
    exit();
}

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
    header('Location: profile.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS | Edit Profile</title>
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
            max-width: 800px;
            margin: 80px auto 30px;
            padding: 20px;
        }

        .card {
            background: white;
            padding: 32px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
        }

        .card h2 {
            color: #1a5dba;
            font-size: 24px;
            margin-bottom: 24px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .profile-section {
            text-align: center;
            margin-bottom: 24px;
        }

        .profile-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 16px;
            border: 3px solid #1a5dba;
        }

        .file-input-container {
            margin: 12px 0;
        }

        .file-input-container input[type="file"] {
            display: none;
        }

        .file-input-container label {
            background-color: #1a5dba;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        .file-input-container label:hover {
            background-color: #144c94;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 24px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-weight: 500;
            color: #444;
            font-size: 14px;
        }

        .form-group input,
        .form-group select {
            padding: 10px;
            border: 1px solid #e5e9ef;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #1a5dba;
            box-shadow: 0 0 0 3px rgba(26,93,186,0.1);
        }

        .button-container {
            text-align: center;
        }

        .save-button {
            background-color: #1a5dba;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .save-button:hover {
            background-color: #144c94;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1001;
        }

        .modal-content {
            background-color: white;
            padding: 2rem;
            border-radius: 12px;
            width: 90%;
            max-width: 400px;
            text-align: center;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .close {
            position: absolute;
            right: 1.5rem;
            top: 1rem;
            font-size: 1.5rem;
            color: #666;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .close:hover {
            color: #000;
        }

        .success-message {
            color: #28a745;
            font-size: 1.1rem;
            margin: 1rem 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .main-container {
                padding: 15px;
            }
            
            .card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="#" style="font-size: 1.2rem; font-weight: 600;"><i class="fas fa-home"></i> Student Dashboard</a>
        <div>
            <a href="dashboard.php"><i class="fas fa-home"></i> Home</a>
            <a href="profile.php"><i class="fas fa-user-edit"></i> Edit Profile</a>
            <a href="student_lab_schedule.php"><i class="fas fa-calendar-alt"></i> Lab Schedule</a>
            <a href="history.php"><i class="fas fa-history"></i> History</a>
            <a href="student_resources.php"><i class="fas fa-book"></i> Student Resources</a>
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
        <div class="card">
            <h2><i class="fas fa-user-edit"></i> Edit Profile</h2>
            <form action="profile.php" method="POST" enctype="multipart/form-data">
                <div class="profile-section">
                    <img src="<?php echo htmlspecialchars($profile_img); ?>" alt="Profile Picture" class="profile-img">
                    <div class="file-input-container">
                        <label for="profile_img">
                            <i class="fas fa-camera"></i> Change Profile Picture
                        </label>
                        <input type="file" id="profile_img" name="profile_img" accept="image/*">
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="firstname">First Name</label>
                        <input type="text" id="firstname" name="firstname" value="<?php echo htmlspecialchars($firstname); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="midname">Middle Name</label>
                        <input type="text" id="midname" name="midname" value="<?php echo htmlspecialchars($midname); ?>">
                    </div>

                    <div class="form-group">
                        <label for="lastname">Last Name</label>
                        <input type="text" id="lastname" name="lastname" value="<?php echo htmlspecialchars($lastname); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="course">Course</label>
                        <select id="course" name="course">
                            <option value="BSCS" <?php if ($course == "BSCS") echo "selected"; ?>>BSCS</option>
                            <option value="BSIT" <?php if ($course == "BSIT") echo "selected"; ?>>BSIT</option>
                            <option value="BSIS" <?php if ($course == "BSIS") echo "selected"; ?>>BSIS</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="year">Year Level</label>
                        <select id="year" name="year">
                            <option value="1" <?php if ($year == "1") echo "selected"; ?>>1st Year</option>
                            <option value="2" <?php if ($year == "2") echo "selected"; ?>>2nd Year</option>
                            <option value="3" <?php if ($year == "3") echo "selected"; ?>>3rd Year</option>
                            <option value="4" <?php if ($year == "4") echo "selected"; ?>>4th Year</option>
                        </select>
                    </div>
                </div>

                <div class="button-container">
                    <button type="submit" class="save-button">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                Profile updated successfully!
            </div>
        </div>
    </div>

    <script>
        // Add Font Awesome
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css';
        document.head.appendChild(link);

        // Show filename when file is selected
        document.getElementById('profile_img').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            if (fileName) {
                const label = document.querySelector('.file-input-container label');
                label.innerHTML = `<i class="fas fa-camera"></i> ${fileName}`;
            }
        });

        var modal = document.getElementById("successModal");
        var span = document.getElementsByClassName("close")[0];

        // Show modal if success parameter is present
        <?php if(isset($_GET['success'])): ?>
        modal.style.display = "block";
        <?php endif; ?>

        span.onclick = function() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

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
