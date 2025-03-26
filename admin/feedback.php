<?php
session_start();
include("../connect/config.php");

// Ensure Only Admin Can Access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback-Review</title>
    <link rel="stylesheet" href="css/report.css">
    <link rel="stylesheet" href="admin.css">
    <link rel="shortcut icon" href="../img/favicon.png" type="image/x-icon" />
</head>

<body>
    <!-- Header -->
    <header class="header">
        <div class="logo">Academic Hub</div>
        <nav class="navbar">
            <a href="admin.php">Dashboard</a>
            <a href="addstaff.php">Staff-Manage</a>
            <a href="addstud.php">Student-Manage</a>
            <a href="addclass.php">Class-Manage</a>
            <a href="addsub.php">Subjects</a>
            <a href="reports.php">Report</a>
            <a href="profile.php"><img src="../img/avt/<?php echo $_SESSION['avt']; ?>.png" alt="Profile" style="vertical-align: middle;  height: 30px;  width: 30px;  object-fit: cover;  border-radius: 50%;"></a>
        </nav>
        <div class="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container">

        <!-- Sidebar -->
        <aside class="sidebar">
            <ul>
                <li><a href="admin.php">Dashboard</a></li>
                <li><a href="addstaff.php">Staff-Manage</a></li>
                <li><a href="addstud.php">Student-Manage</a></li>
                <li><a href="addclass.php">Class-Organization</a></li>
                <li><a href="addsub.php">Subjects ADD</a></li>
                <li><a href="reports.php">Reports</a></li>
                <li><a href="feedback.php">Feedback-Review</a></li>
            </ul>
        </aside>


        <!-- Marks Section -->
        <section id="reports">
            <h2>Student Marks</h2>
            <div id="reports-container" class="reports-container">

                <?php

                // Fetch user data along with student class information and branch from the classes table
                $query = "SELECT users.role, f.message,f.type
                                    FROM users
                                    INNER JOIN feedback f ON users.user_id = f.user_id
                                    WHERE users.college_id = ?";

                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $_SESSION["college_id"]);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($class = $result->fetch_assoc()) {
                    $t=$class['type'];
                    echo " 
                            <div class='reports-card' >
                <h3 style='text-align:center;'>{$class['role']}</h3>
                <div class='type'>
                <p class='it'><strong>Type: </strong>{$class['type']} </p>
                <p><strong>Message:  </strong>{$class['message']}</p>
                </div>
                              </div> ";
                }

                $stmt->close();

                ?>


            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <p>&copy; 2025 Academic Hub. All rights reserved.</p>
    </footer>

    <script src="admin.js"></script>

</body>

</html>