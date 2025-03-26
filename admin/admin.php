<?php
session_start();
include '../connect/config.php';
include '../connect/functions.php';

// Ensure Only Admin Can Access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
$username = $_SESSION["username"];
$c_id = $_SESSION["college_id"];
// Fetch Counts
$staff_count = getTotalCount("staff", $c_id);
$student_count = getTotalCount("students", $c_id);
$class_count = getTotalCount("classes", $c_id);

// Fetch Data
$staff_data = getStaffList($c_id);
$student_data = getStudentList($c_id);
$class_data = getClassList($c_id);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Academic Hub</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="shortcut icon" href="../img/favicon.png" type="image/x-icon" />

    <!-- Chart js-->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        section {
            margin-top: 40px;
            overflow-x: auto;
        }
    </style>
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



        <main class="main-content">
            <h6>Welcome, <span><?php echo $username; ?></span>!</h6>
            <h2>Dashboard</h2>
            <button id="logoutbtn"><a href="../connect/logout.php">Logout</a></button>

            <div class="metrics">
                <div class="card">
                    <h3>Total Staff</h3>
                    <p><?php echo $staff_count; ?></p>
                </div>
                <div class="card">
                    <h3>Total Students</h3>
                    <p><?php echo $student_count; ?></p>
                </div>
                <div class="card">
                    <h3>Classes</h3>
                    <p><?php echo $class_count; ?></p>
                </div>
            </div>

            <section>
                <h2>Staff Management</h2>
                <button onclick="window.location.href='addstaff.php';">Add New Staff</button>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Phone</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($staff = $staff_data->fetch_assoc()): ?>
                            <tr>
                                <td><?= $staff['username'] ?></td>
                                <td><?= $staff['department'] ?></td>
                                <td><?= $staff['phone'] ?></td>
                                <td>
                                    <a href='addstaff.php'><button>Changes</button></a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </section>

            <section>
                <h2>Student Management</h2>
                <button onclick="window.location.href='addstud.php';">Add New Student</button>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Roll No</th>
                            <th>Phone no</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($student = $student_data->fetch_assoc()): ?>
                            <tr>
                                <td><?= $student['username'] ?></td>
                                <td><?= $student['roll_number'] ?></td>
                                <td><?= $student['phone'] ?></td>
                                <td>
                                    <a href='addstud.php'><button>Changes</button></a>
                                </td>
                            </tr>
                        <?php endwhile; ?>

                    </tbody>
                </table>
            </section>

            <section>
                <h2>Class Organization</h2>
                <button onclick="window.location.href='addclass.php';">Add New Class</button>
                <table>
                    <thead>
                        <tr>
                            <th>Class</th>
                            <th>Students</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($class = $class_data->fetch_assoc()): ?>
                            <tr>
                                <td><?= $class['branch'] ?></td>
                                <td><?= $class['total'] ?></td>
                                <td>
                                    <a href='addclass.php'><button>Changes</button></a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </section>

            <!-- Reports Section -->
            <section id="reports">
                <h2>Reports</h2>
                <button onclick="window.location.href='reports.php';">Generate Report</button>
                <p>Create easy-to-read reports for classes and categories. You can save them as PDFs to print, or as Excel files to use offline.</p>
            </section>

            <!-- Class Organization Section -->
            <section id="chart-section">
                <h2>Class & Student Distribution</h2>
                <div style="width: 300px; height: 300px; margin: auto;">
                    <canvas id="doughnutChart"></canvas>
                </div>
            </section>

        </main>
    </div>

    <footer class="footer">
        <p>&copy; 2025 Academic Hub. All rights reserved.</p>
    </footer>

    <script src="admin.js"></script>

    <script>
        // PHP Variables for Chart Data
        var classCount = <?php echo $class_count; ?>;
        var studentCount = <?php echo $student_count; ?>;

        document.addEventListener("DOMContentLoaded", function() {
            var ctx = document.getElementById("doughnutChart").getContext("2d");

            var myChart = new Chart(ctx, {
                type: "doughnut",
                data: {
                    labels: ["Total Classes", "Total Students"],
                    datasets: [{
                        data: [classCount, studentCount],
                        backgroundColor: ["#4CAF50", "#FF9800"],
                        hoverBackgroundColor: ["#388E3C", "#F57C00"]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: "60%", // Makes the chart more compact
                    plugins: {
                        legend: {
                            position: "bottom",
                            labels: {
                                font: {
                                    size: 12
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>

</body>

</html>