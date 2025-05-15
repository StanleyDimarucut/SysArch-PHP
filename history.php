<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

include("db.php");

// Get student's ID from username
$username = $_SESSION["username"];
$query = "SELECT IDNO FROM register WHERE USERNAME = ?";
$stmt = mysqli_prepare($con, $query);
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
$student_id = $row['IDNO'];

// Handle feedback submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit_feedback"])) {
    $subject = trim($_POST["subject"]);
    $message = trim($_POST["message"]);
    $session_id = $_POST["session_id"];

    if (!empty($subject) && !empty($message)) {
        // Get session details first
        $session_query = "SELECT lab, purpose FROM sit_in_records WHERE id = ?";
        $stmt = mysqli_prepare($con, $session_query);
        mysqli_stmt_bind_param($stmt, "i", $session_id);
        mysqli_stmt_execute($stmt);
        $session_result = mysqli_stmt_get_result($stmt);
        $session = mysqli_fetch_assoc($session_result);
        
        // Now insert feedback with session details
        $insert_query = "INSERT INTO feedback (student_id, subject, message, room, purpose) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($con, $insert_query);
        mysqli_stmt_bind_param($stmt, "issss", $student_id, $subject, $message, $session['lab'], $session['purpose']);
        
        if (mysqli_stmt_execute($stmt)) {
            header("Location: history.php?success=Feedback submitted successfully!");
        } else {
            header("Location: history.php?error=Failed to submit feedback.");
        }
        exit();
    } else {
        header("Location: history.php?error=All fields are required.");
        exit();
    }
}

// Fetch previous feedback
$feedback_query = "SELECT f.*, CONCAT(r.FIRSTNAME, ' ', r.LASTNAME) as student_name 
                  FROM feedback f 
                  JOIN register r ON f.student_id = r.IDNO 
                  WHERE f.student_id = ? 
                  ORDER BY f.date_submitted DESC";
$stmt = mysqli_prepare($con, $feedback_query);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$feedback_result = mysqli_stmt_get_result($stmt);

// Get filter parameters
$selected_date = isset($_GET['date']) ? $_GET['date'] : '';
$show_all = isset($_GET['show_all']) ? true : false;
$lab_filter = isset($_GET['lab']) ? $_GET['lab'] : '';
$purpose_filter = isset($_GET['purpose']) ? $_GET['purpose'] : '';

// Get unique labs and purposes for dropdowns
$labs_query = "SELECT DISTINCT lab FROM sit_in_records WHERE student_id = ? AND lab IS NOT NULL ORDER BY lab";
$labs_stmt = mysqli_prepare($con, $labs_query);
mysqli_stmt_bind_param($labs_stmt, "s", $student_id);
mysqli_stmt_execute($labs_stmt);
$labs_result = mysqli_stmt_get_result($labs_stmt);

$purposes_query = "SELECT DISTINCT purpose FROM sit_in_records WHERE student_id = ? AND purpose IS NOT NULL ORDER BY purpose";
$purposes_stmt = mysqli_prepare($con, $purposes_query);
mysqli_stmt_bind_param($purposes_stmt, "s", $student_id);
mysqli_stmt_execute($purposes_stmt);
$purposes_result = mysqli_stmt_get_result($purposes_stmt);

// Query to get session history
$query = "SELECT s.*, CONCAT(r.FIRSTNAME, ' ', r.LASTNAME) as full_name 
          FROM sit_in_records s 
          JOIN register r ON s.student_id = r.IDNO
          WHERE s.student_id = ?";

if (!$show_all && !empty($selected_date)) {
    $query .= " AND s.date = ?";
}

if (!empty($lab_filter)) {
    $query .= " AND s.lab = ?";
}

if (!empty($purpose_filter)) {
    $query .= " AND s.purpose = ?";
}

$query .= " ORDER BY s.date DESC, s.time_in DESC";

// Prepare and execute the query with parameters
$stmt = mysqli_prepare($con, $query);
$types = "s";
$params = [$student_id];

if (!$show_all && !empty($selected_date)) {
    $types .= "s";
    $params[] = $selected_date;
}

if (!empty($lab_filter)) {
    $types .= "s";
    $params[] = $lab_filter;
}

if (!empty($purpose_filter)) {
    $types .= "s";
    $params[] = $purpose_filter;
}

mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

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
    header('Location: history.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS | History</title>
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
        }

        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .history-grid {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            padding: 24px;
        }

        .card {
            background: white;
            border-radius: 8px;
        }

        h2 {
            color: #1a1a1a;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        .filter-container {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .filter-form {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        .date-input, select {
            padding: 0.6rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 0.95rem;
            background-color: white;
        }

        .btn {
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 6px;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-primary {
            background-color: #144c94;
            color: white;
        }

        .btn-primary:hover {
            background-color: #1a5dba;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th {
            background-color: #f8fafc;
            font-weight: 600;
            color: #1a1a1a;
        }

        tr:hover {
            background-color: #f8fafc;
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                padding: 15px;
            }

            .nav-menu {
                margin-top: 15px;
                flex-wrap: wrap;
                justify-content: center;
            }

            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }

            .btn {
                width: 100%;
                justify-content: center;
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
        <?php if (isset($_GET["success"])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($_GET["success"]); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET["error"])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($_GET["error"]); ?>
            </div>
        <?php endif; ?>

        <div class="history-grid">
            <div class="card">
                <h2><i class="fas fa-history"></i> My Sit-in History</h2>
                
                <div class="filter-container">
                    <form method="GET" class="filter-form">
                        <input type="date" name="date" class="date-input" value="<?php echo $selected_date; ?>" aria-label="Select date">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i>
                            Filter
                        </button>
                    </form>
                </div>

                <div class="table-responsive">
                    <table id="historyTable">
                        <thead>
                            <tr>
                                <th>Purpose</th>
                                <th>Laboratory</th>
                                <th>Login Time</th>
                                <th>Logout Time</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($result) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['purpose']); ?></td>
                                        <td><?php echo htmlspecialchars($row['lab']); ?></td>
                                        <td><?php echo htmlspecialchars($row['time_in']); ?></td>
                                        <td><?php echo htmlspecialchars($row['time_out'] ?? 'Active'); ?></td>
                                        <td><?php echo htmlspecialchars($row['date']); ?></td>
                                        <td>
                                            <button class="btn btn-primary" onclick="openFeedbackModal('<?php echo htmlspecialchars($row['id']); ?>', '<?php echo htmlspecialchars($row['lab']); ?>', '<?php echo htmlspecialchars($row['date']); ?>')">
                                                Give Feedback
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="no-records">No sit-in records found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Feedback Modal -->
    <div id="feedbackModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); overflow: auto;">
        <div class="modal-content" style="background-color: #fefefe; margin: 15% auto; padding: 20px; border-radius: 8px; width: 80%; max-width: 500px;">
            <span class="close" onclick="closeFeedbackModal()" style="float: right; cursor: pointer; font-size: 28px; font-weight: bold;">&times;</span>
            <h2 style="margin-bottom: 20px; text-align: left;">Give Feedback</h2>
            <form id="feedbackForm" method="POST" action="history.php" style="text-align: left;">
                <input type="hidden" name="session_id" id="session_id">
                <div style="margin-bottom: 15px; text-align: left;">
                    <label for="subject" style="display: block; margin-bottom: 5px; text-align: left;">Subject:</label>
                    <input type="text" id="subject" name="subject" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; text-align: left;">
                </div>
                <div style="margin-bottom: 15px; text-align: left;">
                    <label for="message" style="display: block; margin-bottom: 5px; text-align: left;">Message:</label>
                    <textarea id="message" name="message" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; min-height: 100px; text-align: left;"></textarea>
                </div>
                <button type="submit" name="submit_feedback" class="btn btn-primary" style="width: 100%;">Submit Feedback</button>
            </form>
        </div>
    </div>

    <script>
        function openFeedbackModal(sessionId, lab, date) {
            document.getElementById('feedbackModal').style.display = 'block';
            document.getElementById('session_id').value = sessionId;
            document.getElementById('subject').value = `Feedback for ${lab} - ${date}`;
        }

        function closeFeedbackModal() {
            document.getElementById('feedbackModal').style.display = 'none';
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            var modal = document.getElementById('feedbackModal');
            if (event.target == modal) {
                closeFeedbackModal();
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