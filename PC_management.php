<?php
session_start();
include 'db.php';

// Check if user is admin
if (!isset($_SESSION['admin_username'])) {
    header("Location: login.php");
    exit();
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
        $room = mysqli_real_escape_string($con, $_POST['room']);
        $pc_number = mysqli_real_escape_string($con, $_POST['pc_number']);
        $status = mysqli_real_escape_string($con, $_POST['status']);
        
        // Check if PC status record exists
        $check_query = "SELECT id FROM pc_status WHERE room = ? AND pc_number = ?";
        $check_stmt = mysqli_prepare($con, $check_query);
        mysqli_stmt_bind_param($check_stmt, "si", $room, $pc_number);
        mysqli_stmt_execute($check_stmt);
        $result = mysqli_stmt_get_result($check_stmt);
        
        if (mysqli_num_rows($result) > 0) {
            // Update existing record
            $update_query = "UPDATE pc_status SET status = ? WHERE room = ? AND pc_number = ?";
            $update_stmt = mysqli_prepare($con, $update_query);
            mysqli_stmt_bind_param($update_stmt, "ssi", $status, $room, $pc_number);
            mysqli_stmt_execute($update_stmt);
        } else {
            // Insert new record
            $insert_query = "INSERT INTO pc_status (room, pc_number, status) VALUES (?, ?, ?)";
            $insert_stmt = mysqli_prepare($con, $insert_query);
            mysqli_stmt_bind_param($insert_stmt, "sis", $room, $pc_number, $status);
            mysqli_stmt_execute($insert_stmt);
        }
        
        $message = "PC status updated successfully!";
    }
}

// AJAX bulk update handler
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) && $_POST['action'] === 'bulk_update' &&
    isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) {
    if (!isset($_SESSION['admin_username'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    $room = isset($_POST['room']) ? mysqli_real_escape_string($con, $_POST['room']) : '';
    $status = isset($_POST['status']) ? mysqli_real_escape_string($con, $_POST['status']) : '';
    $selected_pcs = isset($_POST['selected_pcs']) ? explode(',', $_POST['selected_pcs']) : [];
    if (!$room || !$status || empty($selected_pcs)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing parameters']);
        exit();
    }
    foreach ($selected_pcs as $pc_number) {
        $pc_number = intval($pc_number);
        if ($pc_number > 0) {
            $check = mysqli_query($con, "SELECT id FROM pc_status WHERE room='$room' AND pc_number=$pc_number");
            if (mysqli_num_rows($check) > 0) {
                mysqli_query($con, "UPDATE pc_status SET status='$status' WHERE room='$room' AND pc_number=$pc_number");
            } else {
                mysqli_query($con, "INSERT INTO pc_status (room, pc_number, status) VALUES ('$room', $pc_number, '$status')");
            }
        }
    }
    // Return updated statuses for all 50 PCs in this room
    $statuses = [];
    $res = mysqli_query($con, "SELECT pc_number, status FROM pc_status WHERE room='$room'");
    while ($row = mysqli_fetch_assoc($res)) {
        $statuses[$row['pc_number']] = $row['status'];
    }
    echo json_encode(['success' => true, 'statuses' => $statuses]);
    exit();
}

// Always use hardcoded rooms
$rooms = ['524', '526', '528', '530', '542', '544', '517'];
$pcs_per_room = 50;

$selected_room = isset($_GET['room']) ? $_GET['room'] : '';
$pc_status = array();

if ($selected_room) {
    $status_query = "SELECT pc_number, status FROM pc_status WHERE room = ?";
    $status_stmt = mysqli_prepare($con, $status_query);
    mysqli_stmt_bind_param($status_stmt, "s", $selected_room);
    mysqli_stmt_execute($status_stmt);
    $status_result = mysqli_stmt_get_result($status_stmt);
    while ($row = mysqli_fetch_assoc($status_result)) {
        $pc_status[$row['pc_number']] = $row['status'];
    }
}

// Fetch admin notifications for bell and dropdown
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PC Management - Admin Panel</title>
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
            background-color: #f8fafc;
            color: #1e293b;
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
        .nav-link i {
            font-size: 14px;
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
        }
        .main-container {
            margin-top: 100px;
            padding: 2rem;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
            width: 95%;
        }
        .card {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            border: 1px solid #e2e8f0;
        }
        .card h2 {
            color: #1a5dba;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .room-selector {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        .room-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            background: #f1f5f9;
            color: #475569;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .room-btn:hover {
            background: #e2e8f0;
            transform: translateY(-1px);
        }
        .room-btn.active {
            background: #1a5dba;
            color: white;
        }
        .pc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 1rem;
        }
        .pc-item {
            padding: 1rem;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            background: white;
            transition: box-shadow 0.2s, border-color 0.2s, background 0.2s, transform 0.15s;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 110px;
            position: relative;
        }
        .pc-item:hover, .pc-item.active {
            transform: translateY(-2px) scale(1.03);
            box-shadow: 0 8px 24px rgba(26,93,186,0.13);
            border-color: #1a5dba;
            z-index: 2;
        }
        .pc-item.available {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            border-color: #22c55e;
            color: #166534;
        }
        .pc-item.in_use {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-color: #f59e0b;
            color: #92400e;
        }
        .pc-item.maintenance {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border-color: #ef4444;
            color: #991b1b;
        }
        .pc-number {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            letter-spacing: 1px;
        }
        .status-badge {
            display: inline-block;
            font-size: 0.95rem;
            font-weight: 600;
            border-radius: 12px;
            padding: 0.25em 0.9em;
            margin-bottom: 0.2rem;
            margin-top: 0.1rem;
            background: rgba(255,255,255,0.5);
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
            text-transform: capitalize;
        }
        .pc-item.available .status-badge {
            color: #22c55e;
            background: #e7f9ef;
        }
        .pc-item.in_use .status-badge {
            color: #f59e0b;
            background: #fff7e6;
        }
        .pc-item.maintenance .status-badge {
            color: #ef4444;
            background: #fbeaea;
        }
        .pc-controls {
            display: none;
            flex-direction: column;
            align-items: center;
            width: 100%;
            margin-top: 0.5rem;
        }
        .pc-item:hover .pc-controls, .pc-item.active .pc-controls {
            display: flex;
        }
        .status-select {
            width: 100%;
            padding: 0.6rem 0.8rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            color: #475569;
            background: #f8fafc;
            margin-bottom: 0.5rem;
            font-weight: 500;
            transition: border-color 0.2s, box-shadow 0.2s;
            box-shadow: 0 1px 4px rgba(26,93,186,0.03);
        }
        .status-select:focus {
            outline: none;
            border-color: #1a5dba;
            box-shadow: 0 0 0 3px rgba(26,93,186,0.09);
        }
        .update-btn {
            width: 100%;
            padding: 0.7rem 0;
            border: none;
            border-radius: 8px;
            background: linear-gradient(135deg, #144c94 0%, #1a5dba 100%);
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 1rem;
            box-shadow: 0 2px 8px rgba(26,93,186,0.07);
            margin-top: 0.2rem;
        }
        .update-btn:hover {
            background: #144c94;
            transform: translateY(-2px) scale(1.04);
            box-shadow: 0 4px 16px rgba(26,93,186,0.13);
        }
        .message {
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 8px;
            font-weight: 500;
        }
        .success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }
        .error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        @media (max-width: 768px) {
            .navbar {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
            }
            .main-container {
                margin-top: 140px;
                padding: 1rem;
            }
            .pc-grid {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            }
        }
        .status-toolbar {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            justify-content: center;
        }
        .status-btn {
            padding: 0.7rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            background: #f1f5f9;
            color: #475569;
            transition: all 0.2s;
            border: 2px solid transparent;
        }
        .status-btn.selected, .status-btn:focus {
            background: linear-gradient(135deg, #144c94 0%, #1a5dba 100%);
            color: #fff;
            border-color: #1a5dba;
        }
        .status-btn.available { color: #22c55e; }
        .status-btn.in_use { color: #f59e0b; }
        .status-btn.maintenance { color: #ef4444; }
        .pc-item.selected {
            box-shadow: 0 0 0 4px #1a5dba44;
            border-color: #1a5dba;
            position: relative;
        }
        .update-selected-btn {
            display: block;
            margin: 2rem auto 1rem auto;
            padding: 0.9rem 2.5rem;
            border: none;
            border-radius: 8px;
            background: linear-gradient(135deg, #144c94 0%, #1a5dba 100%);
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(26,93,186,0.07);
            transition: all 0.2s;
        }
        .update-selected-btn:hover {
            background: #144c94;
            transform: translateY(-2px) scale(1.04);
            box-shadow: 0 4px 16px rgba(26,93,186,0.13);
        }
        .pc-controls { display: none; }
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
            right: 0;
            left: auto;
            top: 40px;
            background: #fffbe6;
            border: 1px solid #ffe58f;
            border-radius: 8px;
            min-width: 300px;
            z-index: 3000;
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
        <div id="adminNotifDropdown" class="notif-dropdown" style="display:none;">
            <h4>Admin Notifications</h4>
            <ul>
                <?php
                if ($admin_notif_result && mysqli_num_rows($admin_notif_result) > 0) {
                    mysqli_data_seek($admin_notif_result, 0);
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
    </div>

    <div class="main-container">
        <div class="card">
            <h2><i class="fas fa-desktop"></i> PC Status Management</h2>
            
            <?php if (isset($message)): ?>
                <div class="message success">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="room-selector">
                <?php foreach ($rooms as $room): ?>
                    <a href="?room=<?php echo $room; ?>" 
                       class="room-btn <?php echo $selected_room === $room ? 'active' : ''; ?>">
                        Room <?php echo $room; ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php if ($selected_room): ?>
                <form id="updateSelectedForm" method="POST" action="update_pc_status.php">
                    <input type="hidden" name="action" value="bulk_update">
                    <input type="hidden" name="status" id="bulkStatusInput" value="available">
                    <input type="hidden" name="selected_pcs" id="selectedPCsInput" value="">
                    <input type="hidden" name="room" value="<?php echo htmlspecialchars($selected_room); ?>">
                    <div class="status-toolbar">
                        <button type="button" class="status-btn available selected" data-status="available">Available</button>
                        <button type="button" class="status-btn in_use" data-status="in_use">In Use</button>
                        <button type="button" class="status-btn maintenance" data-status="maintenance">Maintenance</button>
                    </div>
                    <button type="submit" class="update-selected-btn">Update Selected</button>
                    <div class="pc-grid">
                        <?php for ($i = 1; $i <= $pcs_per_room; $i++): 
                            $status = isset($pc_status[$i]) ? $pc_status[$i] : 'available'; ?>
                            <div class="pc-item <?php echo $status; ?>" data-pc="<?php echo $i; ?>">
                                <div class="pc-number">PC <?php echo $i; ?></div>
                                <span class="status-badge"><?php echo ucfirst(str_replace('_', ' ', $status)); ?></span>
                            </div>
                        <?php endfor; ?>
                    </div>
                </form>
                <script>
                function toggleMenu() {
                    const menu = document.querySelector('.nav-menu');
                    menu.classList.toggle('active');
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
                // Status button selection
                const statusBtns = document.querySelectorAll('.status-btn');
                let selectedStatus = 'available';
                statusBtns.forEach(btn => {
                    btn.addEventListener('click', function() {
                        statusBtns.forEach(b => b.classList.remove('selected'));
                        this.classList.add('selected');
                        selectedStatus = this.getAttribute('data-status');
                        document.getElementById('bulkStatusInput').value = selectedStatus;
                    });
                });
                // PC selection
                const pcItems = document.querySelectorAll('.pc-item');
                let selectedPCs = [];
                pcItems.forEach(item => {
                    item.addEventListener('click', function() {
                        const pcNum = this.getAttribute('data-pc');
                        if (this.classList.contains('selected')) {
                            this.classList.remove('selected');
                            selectedPCs = selectedPCs.filter(n => n !== pcNum);
                        } else {
                            this.classList.add('selected');
                            selectedPCs.push(pcNum);
                        }
                        document.getElementById('selectedPCsInput').value = selectedPCs.join(',');
                    });
                });
                // AJAX form submit
                document.getElementById('updateSelectedForm').addEventListener('submit', function(e) {
                    e.preventDefault();
                    if (selectedPCs.length === 0) {
                        alert('Please select at least one PC to update.');
                        return;
                    }
                    const form = this;
                    const formData = new FormData(form);
                    fetch('PC_management.php', {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            // Update UI
                            const statuses = data.statuses;
                            document.querySelectorAll('.pc-item').forEach(item => {
                                const pcNum = item.getAttribute('data-pc');
                                const newStatus = statuses[pcNum] || 'available';
                                item.classList.remove('available', 'in_use', 'maintenance', 'selected');
                                item.classList.add(newStatus);
                                item.querySelector('.status-badge').textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1).replace('_',' ');
                            });
                            selectedPCs = [];
                            document.getElementById('selectedPCsInput').value = '';
                            document.getElementById('updateMessage').textContent = 'Selected PCs updated!';
                            setTimeout(()=>{document.getElementById('updateMessage').textContent='';}, 2000);
                        } else {
                            document.getElementById('updateMessage').textContent = 'Update failed: ' + (data.message || 'Unknown error');
                        }
                    })
                    .catch(() => {
                        document.getElementById('updateMessage').textContent = 'Update failed: Network error';
                    });
                });
                </script>
            <?php else: ?>
                <p style="text-align: center; color: #64748b; margin-top: 2rem;">
                    Select a room to manage PC statuses
                </p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

<?php
// PHP bulk update handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bulk_update') {
    $room = $selected_room;
    $status = mysqli_real_escape_string($con, $_POST['status']);
    $selected_pcs = explode(',', $_POST['selected_pcs']);
    foreach ($selected_pcs as $pc_number) {
        $pc_number = intval($pc_number);
        if ($pc_number > 0) {
            $check = mysqli_query($con, "SELECT id FROM pc_status WHERE room='$room' AND pc_number=$pc_number");
            if (mysqli_num_rows($check) > 0) {
                mysqli_query($con, "UPDATE pc_status SET status='$status' WHERE room='$room' AND pc_number=$pc_number");
            } else {
                mysqli_query($con, "INSERT INTO pc_status (room, pc_number, status) VALUES ('$room', $pc_number, '$status')");
            }
        }
    }
    $message = "Selected PCs updated to '" . ucfirst(str_replace('_', ' ', $status)) . "'!";
}
?> 