<?php
session_start();
include '../connect/config.php'; // Include your database connection file

// Ensure Only Admin Can Access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
if ($role == 'staff' || $role == 'student') {
    if ($role == 'student') {
        $test = 'students';
    } else if ($role == 'staff') {
        $test = 'staff';
    }
    // Fetch user data from database
    $query = "SELECT * FROM users INNER JOIN $test ON users.user_id = $test.user_id WHERE users.user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
} else if ($role == 'admin') {
    // Fetch user data from database
    $query = "SELECT * FROM users  WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
}

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    echo "User not found!";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $avt = $_POST['avatar'];

    $query = "UPDATE users SET avt = ? WHERE user_id = ? AND role=?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "sis", $avt, $user_id, $role);

    if (mysqli_stmt_execute($stmt)) {
        $message = "Success";
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    } else {
        $error_message = "Failed!";
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Academic Hub</title>
    <link rel="stylesheet" href="css/profile.css">
    <link rel="stylesheet" href="admin.css">
    <link rel="shortcut icon" href="../img/favicon.png" type="image/x-icon" />
    
</head>
<style>
    @media (max-width:600px) {
        .main-content{
            display: contents;
        }
    }
</style>

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


        <!-- Main Content -->
        <main class="main-content">

            <section id="student-profile">
                <div class="profile-container">
                    <!-- Profile Image -->
                    <div class="profile-image">
                        <img src="../img/avt/<?php echo $user['avt']; ?>.png" alt="Profile Image" id="profile-img">
                    </div>

                    <!-- Personal Information -->
                    <div class="profile-info">
                        <h3>Personal Information</h3>
                        <p><strong>Name:</strong> <?php echo $user['username']; ?></p>
                        <p><strong>Email:</strong> <?php echo $user['email']; ?></p>
                        <p><strong>Phone:</strong> <?php if ($role != 'admin') {
                                                        echo $user['phone'];
                                                    } ?></p>
                    </div>

                    <!-- Academic Information -->
                    <div class="academic-info">
                        <h3>Academic Information</h3>
                        <p><strong>Instit:</strong> <?php //echo $user['course']; ?></p>
                        <p><strong>Year:</strong> <?php //echo $user['year']; ?></p>
                        <p><strong>Section:</strong> <?php //echo $user['section']; ?></p>
                    </div>


                    <div id="message-box"></div> <!-- Message Box -->


                    <!-- Settings Section -->
                    <div class="settings">
                        <h3>Settings</h3>
                        <button id="edit-profile">Edit Profile</button>
                        <button id="change-password">Change Password</button>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <form method="POST" action="profile.php" class="avatar-form" id="avt">
        <label>Select Your Avatar:</label>
        <div class="avatar-container">
            <input type="radio" name="avatar" value="avatar1" id="avatar1" required>
            <label for="avatar1"><img src="../img/avt/avatar1.png" class="avatar-img"></label>

            <input type="radio" name="avatar" value="avatar2" id="avatar2">
            <label for="avatar2"><img src="../img/avt/avatar2.png" class="avatar-img"></label>

            <input type="radio" name="avatar" value="avatar3" id="avatar3">
            <label for="avatar3"><img src="../img/avt/avatar3.png" class="avatar-img"></label>

            <input type="radio" name="avatar" value="avatar4" id="avatar4">
            <label for="avatar4"><img src="../img/avt/avatar4.png" class="avatar-img"></label>

            <input type="radio" name="avatar" value="avatar5" id="avatar5">
            <label for="avatar5"><img src="../img/avt/avatar5.png" class="avatar-img"></label>

            <input type="radio" name="avatar" value="avatar6" id="avatar6">
            <label for="avatar6"><img src="../img/avt/avatar6.png" class="avatar-img"></label>

            <input type="radio" name="avatar" value="avatar7" id="avatar7">
            <label for="avatar7"><img src="../img/avt/avatar7.png" class="avatar-img"></label>

            <input type="radio" name="avatar" value="avatar8" id="avatar8">
            <label for="avatar8"><img src="../img/avt/avatar8.png" class="avatar-img"></label>
        </div>

        <button type="submit" id="toggleButton">Save Avatar</button>
    </form>


    <footer class="footer">
        <p>&copy; 2025 Academic Hub. All rights reserved.</p>
    </footer>

    <script src="admin.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const hamburger = document.querySelector(".hamburger");
            const navbar = document.querySelector(".navbar");
            const toggleButton = document.getElementById("toggleButton");
            const formContainer = document.getElementById("avt");
            const profileImg = document.getElementById("profile-img");

            // Toggle navbar menu
            hamburger.addEventListener("click", () => {
                navbar.classList.toggle("active");
                hamburger.classList.toggle("active");
            });

            // Show avatar selection form when clicking the profile image
            profileImg.addEventListener("click", () => {
                if (formContainer.style.display === "none" || formContainer.style.display === "") {
                    formContainer.style.display = "block";
                    toggleButton.style.display = "block"; // Ensure the save button appears
                } else {
                    formContainer.style.display = "none";
                }
            });

            // Hide form when clicking the save button
            toggleButton.addEventListener("click", (event) => {
                // Prevent form submission
                formContainer.style.display = "none";
            });

            function showMessage(text, type) {
                let messageBox = document.getElementById("message-box");
                messageBox.innerHTML = text;
                messageBox.className = type === "success" ? "success" : "error"; // Apply class
                messageBox.style.display = "block";

                // Hide after 2 seconds
                setTimeout(() => {
                    messageBox.style.display = "none";
                }, 1500);
            }

        });
    </script>
</body>

</html>