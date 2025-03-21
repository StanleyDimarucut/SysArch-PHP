<?php
session_start();
if (!isset($_SESSION["admin_username"])) {
    header("Location: login.php");
    exit();
}

// Connect to the database
include("db.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS | Student List</title>
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
        .search-box {
            width: 300px;
            margin-bottom: 20px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        #studentsTable {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        #studentsTable th {
            background-color: #144c94;
            color: white;
            padding: 12px;
            text-align: left;
        }
        #studentsTable td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
        #studentsTable tr:hover {
            background-color: #f5f5f5;
        }
        #studentsTable tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="admin_dashboard.php">Admin Dashboard</a>
        <div>
            <a href="announcement.php">Announcements</a>
            <a href="student_list.php">View Student List</a>
            <a href="students.php">Sit-in</a>
            <a href="sitin_view.php">Current Sit-in</a>
            <a href="session_history.php">Sit-in Reports</a>
            <a href="../php/login.php" style="color: orange;">Log out</a>
        </div>
    </div>

    <div class="container">
        <div class="table-container">
            <h3>Registered Students</h3>
            <input type="text" id="studentFilter" class="search-box" onkeyup="filterStudents()" placeholder="Search students...">
            <table id="studentsTable">
                <thead>
                    <tr>
                        <th>ID Number</th>
                        <th>Name</th>
                        <th>Course</th>
                        <th>Year Level</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $students_query = "SELECT IDNO, FIRSTNAME, LASTNAME, COURSE, YEARLEVEL FROM register WHERE USERNAME != 'admin' ORDER BY LASTNAME";
                    $students_result = mysqli_query($con, $students_query);
                    while ($student = mysqli_fetch_assoc($students_result)) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($student['IDNO']) . "</td>";
                        echo "<td>" . htmlspecialchars($student['FIRSTNAME'] . ' ' . $student['LASTNAME']) . "</td>";
                        echo "<td>" . htmlspecialchars($student['COURSE']) . "</td>";
                        echo "<td>" . htmlspecialchars($student['YEARLEVEL']) . "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function filterStudents() {
            var input = document.getElementById("studentFilter");
            var filter = input.value.toLowerCase();
            var table = document.getElementById("studentsTable");
            var tr = table.getElementsByTagName("tr");

            for (var i = 1; i < tr.length; i++) {
                var td = tr[i].getElementsByTagName("td");
                var found = false;
                for (var j = 0; j < td.length; j++) {
                    var cell = td[j];
                    if (cell) {
                        var text = cell.textContent || cell.innerText;
                        if (text.toLowerCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                }
                tr[i].style.display = found ? "" : "none";
            }
        }
    </script>
</body>
</html> 