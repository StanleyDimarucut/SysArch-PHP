<?php
session_start();
if (!isset($_SESSION["admin_username"])) {
    header("Location: login.php");
    exit();
}

// Connect to the database
include("db.php");

// Fetch all students or filter by search
$search_query = "";
if (isset($_GET["search"]) && !empty(trim($_GET["search"]))) {
    $search_query = trim($_GET["search"]);
    $query = "SELECT * FROM register WHERE USERNAME != 'admin' AND IDNO LIKE ?";
    $stmt = mysqli_prepare($con, $query);
    $search_param = "%$search_query%";
    mysqli_stmt_bind_param($stmt, "s", $search_param);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $query = "SELECT * FROM register WHERE USERNAME != 'admin'";
    $result = mysqli_query($con, $query);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS | Students</title>
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
            width: 60%;
            margin: 30px auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
        }
        .search-bar {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        .search-bar input {
            width: 80%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }
        .btn-search {
            background-color: #144c94;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }
        .btn-search:hover {
            background-color: #0f3c7a;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        th {
            background-color: #144c94;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>

    <div class="navbar">
        <a href="admin_dashboard.php">Admin Dashboard</a>
        <div>
            <a href="announcement.php">Create Announcements</a>
            <a href="students.php">Students</a>
            <a href="../php/login.php" style="color: orange;">Log out</a>
        </div>
    </div>

    <div class="container">
        <h2>Student List</h2>

        <form method="GET" class="search-bar">
            <input type="text" name="search" placeholder="Search by ID Number..." value="<?php echo htmlspecialchars($search_query); ?>">
            <button type="submit" class="btn-search">Search</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>ID Number</th>
                    <th>Last Name</th>
                    <th>First Name</th>
                    <th>Middle Name</th>
                    <th>Course</th>
                    <th>Year Level</th>
                    <th>Username</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row["IDNO"]); ?></td>
                        <td><?php echo htmlspecialchars($row["LASTNAME"]); ?></td>
                        <td><?php echo htmlspecialchars($row["FIRSTNAME"]); ?></td>
                        <td><?php echo htmlspecialchars($row["MIDNAME"]); ?></td>
                        <td><?php echo htmlspecialchars($row["COURSE"]); ?></td>
                        <td><?php echo htmlspecialchars($row["YEARLEVEL"]); ?></td>
                        <td><?php echo htmlspecialchars($row["USERNAME"]); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

</body>
</html>
