<?php

session_start();
include '../connect/config.php'; // Ensure you include database connection

// Ensure Only Students Can Access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

// Get logged-in student ID
$student_id = $_SESSION['user_id'];

// Query to fetch marks with subject names
$query = "SELECT 
            s.subject_id, s.subject_name, 
            MAX(CASE WHEN m.exam_id = 1 THEN m.marks_obtained END) AS midterm,
            MAX(CASE WHEN m.exam_id = 2 THEN m.marks_obtained END) AS endterm,
            MAX(CASE WHEN m.exam_id = 1 THEN m.total_marks END) AS mid_total,
            MAX(CASE WHEN m.exam_id = 2 THEN m.total_marks END) AS end_total
          FROM marks m
          JOIN subjects s ON m.subject_id = s.subject_id
          WHERE m.student_id = ?
          GROUP BY s.subject_name";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marks</title>
    <link rel="stylesheet" href="css/marks.css">
    <link rel="stylesheet" href="style.css">

    <link rel="shortcut icon" href="../img/favicon.png" type="image/x-icon" />
    
</head>

<body>
     <!-- Header -->
     <header class="header">
        <div class="logo">Academic Hub</div>
        <nav class="navbar">
            <a href="stud.php">Dashboard</a>
            <a href="attendance.php">Attendance</a>
            <a href="marks.php">Marks</a>
            <a href="feedback.php">Feedback</a>
            <a href="Leaderboard.php">Leaderboard</a>
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
                <li><a href="stud.php">Dashboard</a></li>
                <li><a href="attendance.php">Attendance</a></li>
                <li><a href="marks.php">Marks</a></li>
                <li><a href="feedback.php">Feedback</a></li>
                <li><a href="Leaderboard.php">Leaderboard</a></li>
            </ul>
        </aside>

        <!-- Marks Section -->
        <section id="marks">
            <h2>Student Marks</h2>
            <div class="marks-container" style="cursor: pointer;">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="subject-card">
                        <h3><?php echo htmlspecialchars($row['subject_name']); ?></h3>
                        <p><strong>Midterm:</strong>
                            <?php echo round(($row['midterm'] ?? 0), 2); ?>/<?php echo round(($row['mid_total'] ?? 20), 2); ?>
                        </p>
                        <p><strong>Endterm:</strong>
                            <?php echo round(($row['endterm'] ?? 0), 2); ?>/<?php echo round(($row['end_total'] ?? 20), 2); ?>
                        </p>
                        <p><strong>Total:</strong>
                            <?php echo round((($row['midterm'] ?? 0) + ($row['endterm'] ?? 0)) / 2, 2); ?>/20
                        </p>
                        <input type="hidden" value="<?php echo htmlspecialchars($row['subject_id']); ?>" name="sub_id"/>
                    </div>
                <?php endwhile; ?>

            </div>
            <div style="display: none;">
                <h3>Subject All test:</h3>
                <div>
                    <table>
                        <thead>
                            <tr>
                                <th>Test-Name</th>
                                <th>Obtain</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody id="student-list">
                            <tr>
                                <?php
                                $query="SELECT "
                                ?>
                            </tr>
                        </tbody>
                    </table>

                </div>
            </div>
    </div>
    </section>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <p>&copy; 2025 Academic Hub. All rights reserved.</p>
    </footer>

    <script src="script.js"></script>
</body>

</html>