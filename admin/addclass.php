<?php
session_start();
include('../connect/config.php');

$c_id = $_SESSION['college_id'];

// Ensure Only Admin Can Access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Handle adding a new class
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['Branch'])) {
    $branch = $conn->real_escape_string($_POST['Branch']);

    // Secure query using prepared statements
    $stmt = $conn->prepare("SELECT * FROM classes WHERE college_id = ? AND branch = ?");
    $stmt->bind_param("is", $c_id, $branch);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $error_message = "Class or department already exists!";
    } else {
        $stmt = $conn->prepare("INSERT INTO classes (college_id, branch) VALUES (?, ?)");
        $stmt->bind_param("is", $c_id, $branch);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            header("Location: addclass.php?success=1");
            exit();
        } else {
            $error_message = "Error adding class.";
        }
    }
}

// Fetch all classes from database
$classes = $conn->query("SELECT * FROM classes WHERE college_id='$c_id'");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Management</title>
    <link rel="stylesheet" href="css/addclass.css">
    <link rel="shortcut icon" href="../img/favicon.png" type="image/x-icon" />
    <link rel="stylesheet" href="admin.css">
    <style>
        .form-container {
            display: none;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 30px auto;
            font-family: 'Arial', sans-serif;
            animation: bounceIn 1s ease;
            text-align: center;
        }

        @keyframes bounceIn {
            from {
                transform: scale(0.5);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }
    </style>
</head>

<body>
    <!-- Popup Message -->
    <?php if (!empty($message) || !empty($error_message)): ?>
        <div id="popup-message" class="popup-message <?php echo !empty($message) ? 'success-message' : 'error-message'; ?>">
            <?php echo !empty($message) ? $message : $error_message; ?>
        </div>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                let popup = document.getElementById("popup-message");

                if (popup) {
                    popup.classList.add("show-popup");

                    setTimeout(function() {
                        popup.classList.remove("show-popup");
                        popup.classList.add("hide-popup");
                    }, 3000); // Hide after 3 seconds
                }
            });
        </script>
    <?php endif; ?>

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
            <h2>Class Management</h2>

            <form class="form-container" id="add-class-form" method="POST">
                <div class="form-group" style="display: flex; flex-direction: column;">
                    <label for="Branch">Branch:</label>
                    <input type="text" id="Branch" name="Branch" placeholder="Enter Branch/Name of Class" required>
                </div>
                <button type="submit">Add Class</button>
                <button type="button" id="hide">Cancel</button>
            </form>

            <button id="add-class-btn" class="btn-add-class">Add Class</button>

            <div class="class-cards">
                <?php while ($class = $classes->fetch_assoc()): ?>
                    <div class="class-card" data-branch="<?php echo $class['branch']; ?>" data-class-id="<?php echo $class['class_id']; ?>">
                        <h3><?php echo htmlspecialchars($class['branch']); ?></h3>
                        <p>Total Students:
                            <?php
                            $branch_name = $class['class_id'];
                            $student_count = $conn->query("SELECT COUNT(*) AS total FROM students WHERE class_id='$branch_name' AND college_id='$c_id'")->fetch_assoc();
                            echo $student_count['total'];
                            ?>
                        </p>
                    </div>

                <?php endwhile; ?>
            </div>

            <div class="student-table-container" id="student-table" style="display: none;">
                <h3 id="class-title">Students in Class</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Roll no</th>
                            <th>Student Name</th>
                            <th>Average</th>
                            <th>Contact</th>
                        </tr>
                    </thead>
                    <tbody id="student-list"></tbody>
                </table>
            </div>
        </main>
    </div>

    <footer class="footer">
        <p>&copy; 2024 Academic Hub. All rights reserved.</p>
    </footer>

    <script src="admin.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Reset all forms on page load
            document.querySelectorAll("form").forEach(form => form.reset());

            // Prevent form resubmission on refresh
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
        });



        document.addEventListener("DOMContentLoaded", function() {
            console.log("DOM is fully loaded and parsed!");

            //hide UNHIDE
            document.getElementById("add-class-btn").addEventListener("click", () => {
                document.getElementById("add-class-form").style.display = "block";
                document.getElementById("add-class-btn").style.display = "none";
            });

            document.getElementById("hide").addEventListener("click", () => {
                document.getElementById("add-class-form").style.display = "none";
                document.getElementById("add-class-btn").style.display = "block";
            });

            document.querySelectorAll(".class-card").forEach(card => {


                card.addEventListener("click", function() {
                    let branch = this.getAttribute("data-branch");
                    let class_id = this.getAttribute("data-class-id");

                    viewClassDetails(branch, class_id);
                });
            });

            async function viewClassDetails(branch, class_id) {


                document.getElementById("class-title").innerText = `Students in ${branch}`;
                document.getElementById("student-table").style.display = "block";

                try {
                    let response = await fetch(`fetch_students.php?class_id=${class_id}`);
                    let text = await response.text(); // Get raw response

                    let data;
                    try {
                        data = JSON.parse(text); // Attempt JSON parsing
                    } catch (jsonError) {
                        console.error("JSON Parse Error:", jsonError);
                        document.getElementById("student-list").innerHTML = `<tr><td colspan="4">Invalid JSON response</td></tr>`;
                        return;
                    }

                    let tableBody = document.getElementById("student-list");
                    tableBody.innerHTML = "";

                    if (data.error || data.length === 0) {
                        tableBody.innerHTML = `<tr><td colspan="4">${data.error || "No data found"}</td></tr>`;
                        return;
                    }

                    data.forEach(student => {
                        let row = `<tr>
                <td>${student.roll_number}</td>
                <td>${student.username}</td>
                <td>${student.avg}</td>
                <td>${student.phone}</td>
            </tr>`;
                        tableBody.innerHTML += row;
                    });

                } catch (error) {
                    console.error("Fetch Error:", error);
                    document.getElementById("student-list").innerHTML = `<tr><td colspan="4">Failed to load data</td></tr>`;
                }
            }

        });
    </script>

</body>

</html>