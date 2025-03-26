<?php
session_start();
// Include the database configuration file for PHP
include('../connect/config.php');

// Ensure Only Admin Can Access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$c_id = $_SESSION["college_id"];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $subject_id = $_POST['subject']; // ✅ Correctly retrieve subject_id
    $role = "staff";
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Secure password

    // Retrieve the selected value
    $selected_value = $_POST['department']; // Example: "3,Computer Science"
    list($class_id, $branch) = explode(",", $selected_value);
    $class_id = (int) $class_id; // Convert subject_id to an integer (if needed)


    // Check if staff already exists
    $check_staff = "SELECT user_id FROM users WHERE (username = ? OR email = ?) AND college_id=?";
    $stmt_check1 = $conn->prepare($check_staff);
    $stmt_check1->bind_param("ssi", $name, $email, $c_id);
    $stmt_check1->execute();
    $stmt_check1->store_result();

    if ($stmt_check1->num_rows > 0) {
        echo "Error: Staff with this name or email already exists!";
        $stmt_check1->close();
        exit;
    }
    $stmt_check1->close();

    // Begin transaction
    mysqli_begin_transaction($conn);
    try {
        // Insert into Users Table
        $sql_user = "INSERT INTO users (college_id, username, email, password, role) VALUES (?, ?, ?, ?, ?)";
        $stmt2 = $conn->prepare($sql_user);
        $stmt2->bind_param("issss", $c_id, $name, $email, $password, $role);

        if (mysqli_stmt_execute($stmt2)) {
            $user_id = mysqli_insert_id($conn);
            $stmt2->close();

            // Insert into `staff` table
            $query = "INSERT INTO staff (user_id, college_id,department, phone) VALUES (?,?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "iiss", $user_id, $c_id, $branch, $phone);

            if (mysqli_stmt_execute($stmt)) {
                $staff_id = mysqli_insert_id($conn); // ✅ Get the last inserted staff_id


                // Insert into `staff_subjects_classes` table 
                $query2 = "INSERT INTO staff_subjects_classes (staff_id, subject_id, class_id) VALUES (?, ?, ?)";
                $stmt2 = mysqli_prepare($conn, $query2);
                mysqli_stmt_bind_param($stmt2, "iii", $staff_id, $subject_id, $class_id);

                if (mysqli_stmt_execute($stmt2)) {
                    $message = "Staff added successfully and assigned to the subject & class!";
                    mysqli_commit($conn); // ✅ Commit transaction if everything is fine
                } else {
                    throw new Exception('Error assigning staff to subject & class: ' . mysqli_error($conn));
                }
                mysqli_stmt_close($stmt2);
            } else {
                throw new Exception('Error adding staff to staff table: ' . mysqli_error($conn));
            }
        }
    } catch (Exception $e) {
        mysqli_rollback($conn); // ✅ Rollback transaction if any error occurs
        $message = "Error: " . $e->getMessage();
    }
}


// Fetch all staff for display
$sql = "SELECT * FROM users INNER JOIN staff ON users.user_id=staff.user_id WHERE users.college_id='$c_id'";
$result = $conn->query($sql);
?>






<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Staff Dashboard</title>
    <link rel="stylesheet" href="css/addstaff.css">
    <link rel="stylesheet" href="admin.css">
    <link rel="shortcut icon" href="../img/favicon.png" type="image/x-icon" />
    
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
            <h6>Add Staff And Manage Staff:</h6>


            <button id="toggleButton">Add New Staff</button>

            <form class="form-container" id="add-staff-form" method="POST" style="display: none;">
                <div class="form-group">
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" placeholder="Enter staff name" required />
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" placeholder="Enter email address" required />
                </div>
                <div class="form-group">
                    <label for="subject">Subject:</label>
                    <select id="class_id" name="subject" required>
                        <option value="">Select Subject</option>
                        <?php
                        $classResult = $conn->query("SELECT * FROM subjects where college_id='$c_id'");
                        while ($row = mysqli_fetch_assoc($classResult)) { ?>
                            <option value="<?php echo $row['subject_id']; ?>"><?php echo $row['subject_name']; ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="department">Department:</label>
                    <select id="class_id" name="department" required>
                        <option value="">Select Department</option>
                        <?php
                        $classResult = $conn->query("SELECT * FROM classes where college_id='$c_id'");
                        while ($row = mysqli_fetch_assoc($classResult)) { ?>
                            <option value="<?php echo $row['class_id']; ?>,<?php echo $row['branch']; ?>"><?php echo $row['branch']; ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="phone">Phone:</label>
                    <input type="tel" id="phone" name="phone" placeholder="Enter phone number" required />
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" placeholder="Create password" required />
                </div>
                <button type="submit">Add Staff</button>
            </form>

            <!-- Staff Management Table -->
            <section id="staff-management">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Phone</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="staff-table-body">
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['username']) ?></td>
                                <td><?= htmlspecialchars($row['department']) ?></td>
                                <td><?= htmlspecialchars($row['phone']) ?></td>
                                <td>
                                    <button>Edit</button>
                                    <button>Delete</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </section>
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


            // Hide and unhide form script
            const toggleButton = document.getElementById('toggleButton');
            const formContainer = document.getElementById('add-staff-form');
            const hide = document.getElementById('hide');

            toggleButton.addEventListener('click', () => {
                if (formContainer.style.display === 'none' || formContainer.style.display === '') {
                    formContainer.style.display = 'block';
                    toggleButton.style.display = 'none';
                }
            });
            hide.addEventListener('click', () => {
                if (formContainer.style.display === 'block') {
                    formContainer.style.display = 'none';
                    toggleButton.style.display = 'block';
                }
            });


            // Toggle form visibility
            document.getElementById("toggleButton").addEventListener("click", function() {
                document.getElementById("add-staff-form").style.display = "block";
                this.style.display = "none";
            });
        });
    </script>

</body>

</html>