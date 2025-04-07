<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

// Connect to the database
include("db.php");
$username = $_SESSION["username"];

// Get student ID
$query = "SELECT IDNO FROM register WHERE USERNAME = ?";
$stmt = mysqli_prepare($con, $query);
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
$student_id = $row['IDNO'];

// Handle feedback submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $subject = trim($_POST["subject"]);
    $message = trim($_POST["message"]);

    if (!empty($subject) && !empty($message)) {
        $insert_query = "INSERT INTO feedback (student_id, subject, message) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($con, $insert_query);
        mysqli_stmt_bind_param($stmt, "iss", $student_id, $subject, $message);
        
        if (mysqli_stmt_execute($stmt)) {
            header("Location: feedback.php?success=Feedback submitted successfully!");
        } else {
            header("Location: feedback.php?error=Failed to submit feedback.");
        }
        exit();
    } else {
        header("Location: feedback.php?error=All fields are required.");
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS | Feedback</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: rgb(230, 233, 241);
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

        .main-container {
            margin-top: 80px;
            padding: 2rem;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }

        .feedback-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .card h2 {
            color: #144c94;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f0f2f5;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.95rem;
            transition: border-color 0.3s ease;
        }

        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #144c94;
        }

        .submit-button {
            background-color: #144c94;
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s ease;
            width: 100%;
        }

        .submit-button:hover {
            background-color: #0d3a7d;
        }

        .feedback-list {
            max-height: 600px;
            overflow-y: auto;
            padding-right: 0.5rem;
        }

        .feedback-item {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid #144c94;
        }

        .feedback-item h3 {
            color: #144c94;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .feedback-item .date {
            color: #666;
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }

        .feedback-item p {
            color: #444;
            line-height: 1.5;
        }

        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
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

        @media (max-width: 768px) {
            .feedback-grid {
                grid-template-columns: 1fr;
            }
            .main-container {
                padding: 1rem;
            }
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
            <a href="feedback.php">Feedback</a>
            <a href="login.php" style="color: #ffd700;">Log out</a>
        </div>
    </div>

    <div class="main-container">
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

        <div class="feedback-grid">
            <div class="card">
                <h2>Submit Feedback</h2>
                <form action="feedback.php" method="POST">
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" required placeholder="Enter feedback subject">
                    </div>
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" required placeholder="Enter your feedback message"></textarea>
                    </div>
                    <button type="submit" class="submit-button">Submit Feedback</button>
                </form>
            </div>

            <div class="card">
                <h2>Your Previous Feedback</h2>
                <div class="feedback-list">
                    <?php if (mysqli_num_rows($feedback_result) > 0): ?>
                        <?php while ($feedback = mysqli_fetch_assoc($feedback_result)): ?>
                            <div class="feedback-item">
                                <h3><?php echo htmlspecialchars($feedback["subject"]); ?></h3>
                                <div class="date">
                                    Submitted on <?php echo date("F j, Y, g:i a", strtotime($feedback["date_submitted"])); ?>
                                </div>
                                <p><?php echo nl2br(htmlspecialchars($feedback["message"])); ?></p>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="text-align: center; color: #666;">No feedback submitted yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 