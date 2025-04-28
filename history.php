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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS | My Sit-in History</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
            margin: 0 15px;
            font-size: 0.95rem;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .navbar a:hover {
            color: #ffd700;
        }

        .logout-link {
            color: #ffd700 !important;
        }

        .container {
            margin-top: 80px;
            padding: 2rem;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }

        h2 {
            color: #1a1a1a;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        .filter-container {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }

        .filter-form {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .date-input, select {
            padding: 0.6rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .btn {
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 4px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-search {
            background-color: #144c94;
            color: white;
        }

        .btn-search:hover {
            background-color: #0d3a7d;
        }

        .btn-reset {
            background-color: #dc3545;
            color: white;
        }

        .btn-reset:hover {
            background-color: #c82333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        th {
            background-color: #144c94;
            color: white;
            padding: 1rem;
            text-align: left;
            font-weight: 500;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid #f0f2f5;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .no-records {
            text-align: center;
            padding: 2rem;
            color: #666;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }

            .date-input, select {
                width: 100%;
            }
        }

        .btn-feedback {
            background-color: #144c94;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background-color 0.3s ease;
        }

        .btn-feedback:hover {
            background-color: #0d3a7d;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="dashboard.php" style="font-size: 1.2rem; font-weight: 600;">Student Dashboard</a>
        <div>
            <a href="dashboard.php">Home</a>
            <a href="profile.php">Edit Profile</a>
            <a href="history.php">History</a>
            <a href="Reservation.php">Reservation</a>
            <a href="login.php" style="color: #ffd700;">Log out</a>
        </div>
    </div>

    <div class="container">
        <?php if (isset($_GET["success"])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_GET["success"]); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET["error"])): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($_GET["error"]); ?>
            </div>
        <?php endif; ?>

        <div class="history-grid">
            <div class="card">
                <h2>My Sit-in History</h2>
                
                <div class="filter-container">
                    <form method="GET" class="filter-form">
                        <input type="date" name="date" class="date-input" value="<?php echo $selected_date; ?>">
                        <select name="lab" class="date-input">
                            <option value="">All Labs</option>
                            <?php while ($lab = mysqli_fetch_assoc($labs_result)): ?>
                                <option value="<?php echo htmlspecialchars($lab['lab']); ?>" <?php echo $lab_filter == $lab['lab'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($lab['lab']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <select name="purpose" class="date-input">
                            <option value="">All Purposes</option>
                            <?php while ($purpose = mysqli_fetch_assoc($purposes_result)): ?>
                                <option value="<?php echo htmlspecialchars($purpose['purpose']); ?>" <?php echo $purpose_filter == $purpose['purpose'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($purpose['purpose']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <button type="submit" class="btn btn-search">Search</button>
                        <button type="button" class="btn btn-reset" onclick="location.href='history.php'">Reset</button>
                        <button type="button" class="btn btn-search" onclick="location.href='history.php?show_all=1'">Show All</button>
                    </form>
                </div>

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
                                        <button class="btn-feedback" onclick="openFeedbackModal('<?php echo htmlspecialchars($row['id']); ?>', '<?php echo htmlspecialchars($row['lab']); ?>', '<?php echo htmlspecialchars($row['date']); ?>')">
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

    <style>
        .history-grid {
            display: block;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get the modal and close button after DOM is loaded
            var modal = document.getElementById("feedbackModal");
            var span = document.getElementsByClassName("close")[0];

            // When the user clicks the close button, close the modal
            if (span) {
                span.onclick = function() {
                    modal.style.display = "none";
                }
            }

            // When the user clicks anywhere outside of the modal, close it
            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = "none";
                }
            }

            window.openFeedbackModal = function(sessionId, lab, date) {
                document.getElementById('session_id').value = sessionId;
                document.getElementById('subject').value = `Feedback for ${lab} Session on ${date}`;
                document.getElementById('message').value = '';
                document.getElementById('feedback-error').style.display = 'none';
                document.getElementById('feedback-success').style.display = 'none';
                modal.style.display = "block";
            }

            // AJAX feedback submission
            var feedbackForm = document.getElementById('feedbackForm');
            if (feedbackForm) {
                feedbackForm.onsubmit = function(e) {
                    e.preventDefault();
                    var formData = new FormData(feedbackForm);
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', 'submit_feedback.php', true);
                    xhr.onload = function() {
                        var res = {};
                        try { res = JSON.parse(xhr.responseText); } catch (e) {}
                        if (xhr.status === 200 && res.success) {
                            document.getElementById('feedback-success').innerText = res.success;
                            document.getElementById('feedback-success').style.display = 'block';
                            document.getElementById('feedback-error').style.display = 'none';
                            setTimeout(function() { modal.style.display = 'none'; location.reload(); }, 1200);
                        } else {
                            document.getElementById('feedback-error').innerText = res.error || 'Submission failed.';
                            document.getElementById('feedback-error').style.display = 'block';
                            document.getElementById('feedback-success').style.display = 'none';
                        }
                    };
                    xhr.send(formData);
                };
            }
        });
    </script>

    <!-- Feedback Modal -->
    <div id="feedbackModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Submit Feedback</h2>
            <div id="feedback-success" class="alert alert-success" style="display:none;"></div>
            <div id="feedback-error" class="alert alert-error" style="display:none;"></div>
            <form id="feedbackForm" action="submit_feedback.php" method="POST">
                <input type="hidden" id="session_id" name="session_id">
                <div class="form-group">
                    <label for="subject">Subject:</label>
                    <input type="text" id="subject" name="subject" required>
                </div>
                <div class="form-group">
                    <label for="message">Message:</label>
                    <textarea id="message" name="message" rows="4" required></textarea>
                </div>
                <button type="submit" class="btn-submit">Submit Feedback</button>
            </form>
        </div>
    </div>

    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 500px;
            position: relative;
        }

        .close {
            position: absolute;
            right: 20px;
            top: 10px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #666;
        }

        .close:hover {
            color: #000;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        .btn-submit {
            background-color: #144c94;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }

        .btn-submit:hover {
            background-color: #0d3a7d;
        }
    </style>
</body>
</html>