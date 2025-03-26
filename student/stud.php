<?php
session_start();
include '../connect/config.php';

// Ensure Only Students Can Access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$page_no = 1; // Assign a unique number to each page (p1 = 1, p2 = 2, etc.)
$stud_id = $_SESSION["user_id"];
$c_id = $_SESSION["college_id"];
$username = $_SESSION["username"];


// Fetch Attendance and Marks Data
$sql = "SELECT * FROM students WHERE user_id='$stud_id' AND college_id='$c_id'";
$result = mysqli_query($conn, $sql);
$data = mysqli_fetch_assoc($result);


$avg=$data['avg'];

$onboardingCompleted = 0; // Default: Not completed

$check_b = isset($data['check_b']) ? trim($data['check_b']) : '';
$completed_pages = array_filter(array_map('trim', explode(',', $check_b))); // Clean and split values

$page_no = strval($page_no); // Ensure it's a string

// Debugging logs
echo "<script>console.log('Fetched check_b value: " . addslashes(json_encode($check_b)) . "');</script>";
echo "<script>console.log('Completed pages array: " . addslashes(json_encode($completed_pages)) . "');</script>";
echo "<script>console.log('Checking page_no: " . addslashes(json_encode($page_no)) . " (Type: " . gettype($page_no) . ")');</script>";



// Check if page_no exists in completed pages
if (in_array($page_no, $completed_pages, true)) {
    $onboardingCompleted = 1;
}
echo "<script>console.log('Onboarding Completed: " . addslashes(json_encode($onboardingCompleted)) . "');</script>";
error_log("Onboarding Completed: " . var_export($onboardingCompleted, true));




// ✅ Fetch Overall Attendance Percentage
$sqlAttendanceOverall = "SELECT 
    COUNT(DISTINCT CONCAT(date, subject_id)) AS total_lectures, 
    SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) AS attended_lectures
    FROM attendance WHERE student_id = ?";

$stmt = mysqli_prepare($conn, $sqlAttendanceOverall);
mysqli_stmt_bind_param($stmt, "i", $stud_id);
mysqli_stmt_execute($stmt);
$resultOverall = mysqli_stmt_get_result($stmt);
$dataOverall = mysqli_fetch_assoc($resultOverall);

$total_lectures = $dataOverall['total_lectures'] ?? 0;  // Avoid undefined key error
$attended_lectures = $dataOverall['attended_lectures'] ?? 0;

$attendancePercentage = ($total_lectures > 0) ? round(($attended_lectures / $total_lectures) * 100) : 0;


// Prevent division by zero
$attendancePercentage = ($dataOverall['attended_lectures'] > 0) ?
    round(($dataOverall['attended_lectures'] / $dataOverall['total_lectures']) * 100) : 0;

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Academic Hub</title>
    <link rel="stylesheet" href="style.css">

    <link rel="shortcut icon" href="../img/favicon.png" type="image/x-icon" />

    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.9.1/gsap.min.js"></script>


    <style>
        /* Modal Styling */
        .modal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            text-align: center;
            z-index: 1001;
        }

        .modal-content button {
            margin: 10px;
            padding: 10px;
            cursor: pointer;
        }

        /* Overlay for Background Blur */
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            z-index: 1000;
        }

        /* Tooltip Styling */
        .tooltip {
            display: none;
            position: absolute;
            background: black;
            color: white;
            padding: 8px;
            border-radius: 5px;
            font-size: 14px;
            z-index: 1002;
        }

        /* Blurred Background Effect */
        .blur {
            filter: blur(5px);
        }

        /* ✅ Added Blur Effect */
        .blur-effect {
            filter: blur(5px);
            pointer-events: none;
            /* Prevent clicking blurred elements */
        }

        #subject_car p {
            /* font-size: 20px; */
            font-weight: bold;
            color: #1f2937;
        }

        .progress-container {
            width: 150px;
            height: 150px;
            position: relative;
        }

        .progress-circle {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            background: conic-gradient(#ddd 0deg,
                    #ddd 360deg);
            /* Default empty circle */
            transition: background 1s ease-out;
            /* Smooth animation */
        }

        .progress-circle span {
            font-size: 1.5em;
            font-weight: bold;
        }




        
    </style>

</head>

<body>
    <!-- Onboarding Modal -->
    <div id="onboarding-modal" class="modal">
        <div class="modal-content">
            <h2>Welcome to Academic Hub!</h2>
            <p>Let's take a guided tour of your Home-dashboard.</p>
            <button id="start-tour-btn">Start Tour</button>
            <button id="skip-btn" onclick="completeOnboarding(1)">Skip</button>
        </div>
    </div>

    <!-- Overlay for Blurring Background -->
    <div id="overlay" class="overlay"></div>

    <!-- Tooltips -->
    <div id="tooltip" class="tooltip"></div>


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

        <!-- Dashboard -->
        <main class="main-content">
            <section id="dashboard">
                <h1>Welcome, <span><?php echo $username; ?></span>!</h1>
                <button id="logoutbtn"><a href="../connect/logout.php">Logout</a></button>

                <div class="metrics">
                    <div class="card">
                        <h3>Attendance</h3>
                        <div class="progress-container">
                            <div class="progress-circle" id="attendanceCircle">
                                <span id="attendanceCirclePercentage">0%</span>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <h3>Grades</h3>
                        <p id="motivationText">Loading...</p>
                    </div>

                    <div class="card">
                        <h3>Progress</h3>
                        <div class="progress-container">
                            <div class="progress-circle" id="progressCircle">
                                <span id="progressCirclePercentage">0%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <section id="subject_car">
                <h4 style="    margin: 23px 0;
    font-size: medium;">Total Subject To Complete in this Sem:</h4>

                <div class="metrics">
                    <?php
                    // Fetch subjects for the logged-in student
                    $query = "SELECT * FROM student_subjects ss 
                              INNER JOIN subjects s ON ss.subject_id = s.subject_id 
                              WHERE ss.student_id = {$_SESSION['student_id']}";

                    $result = mysqli_query($conn, $query); // Execute the query

                    // Check if the query executed successfully
                    if ($result && mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) { ?>
                            <div class="card">
                                <h3><?php echo htmlspecialchars($row['subject_name']); ?></h3>
                                <p>Total Lectures:</p>
                                <p>Faculty: </p>
                            </div>
                    <?php }
                    } else {
                        echo "<p>No subjects found.</p>";
                    }
                    ?>

                </div>
            </section>
        </main>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <p>&copy; 2025 Academic Hub. All rights reserved.</p>
    </footer>

    <script src="script.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            function animateProgress(elementId, targetPercentage) {
                let currentPercentage = 0;
                const percentageElementId = elementId + 'Percentage';
                const percentageElement = document.getElementById(percentageElementId);

                console.log("Looking for element with ID:", percentageElementId);
                console.log("Found element:", percentageElement);

                if (percentageElement) {
                    const interval = setInterval(function() {
                        currentPercentage++;
                        percentageElement.textContent = currentPercentage + '%';
                        document.getElementById(elementId).style.background = `conic-gradient(
                    #007bff ${currentPercentage * 3.6}deg, 
                    #ddd ${currentPercentage * 3.6}deg
                )`;
                        if (currentPercentage >= targetPercentage) {
                            clearInterval(interval);
                        }
                    }, 10);
                } else {
                    console.error("Element not found:", percentageElementId);
                }
            }



            console.log(<?php echo $attendancePercentage; ?>);

            const attendancePercentage = <?php echo $attendancePercentage; ?>;
            const progressPercentage = <?php echo $avg; ?>; // Assuming progress is the same as attendance for now

            animateProgress('attendanceCircle', attendancePercentage);
            animateProgress('progressCircle', progressPercentage);

            // Motivation Logic
            const motivationTextElement = document.getElementById('motivationText');

            if (progressPercentage < 50) {
                motivationTextElement.textContent = "Keep pushing! Every step counts.";
            } else if (progressPercentage < 75) {
                motivationTextElement.textContent = "Great progress! You're on the right track.";
            } else if (progressPercentage < 90) {
                motivationTextElement.textContent = "Excellent work! You're almost there.";
            } else {
                motivationTextElement.textContent = "You're a superstar! Keep shining!";
            }
        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var onboardingCompleted = <?php echo $onboardingCompleted; ?>;
            console.log("Onboarding script loaded");

            const overlay = document.getElementById("overlay");
            const onboardingModal = document.getElementById("onboarding-modal");
            const metricsSection = document.querySelector(".metrics");
            const popup = document.createElement("div");
            popup.classList.add("onboarding-popup");

            if (onboardingCompleted == 0) {

                popup.style.position = "absolute";
                popup.style.background = "white";
                popup.style.padding = "15px";
                popup.style.borderRadius = "8px";
                popup.style.boxShadow = "0 0 10px rgba(0, 0, 0, 0.3)";
                popup.style.zIndex = "1002";
                popup.style.display = "none";
                document.body.appendChild(popup);

                if (onboardingCompleted == 0) {
                    onboardingModal.style.display = "block";
                    overlay.style.display = "block";
                }

                let steps = [];

                if (window.innerWidth > 989) {
                    // Large screens (PC)
                    steps = [{
                            selector: ".navbar",
                            message: "This is the navigation bar where you can access different sections."
                        },
                        {
                            selector: ".sidebar",
                            message: "This is the sidebar for quick access to important links."
                        },
                        {
                            selector: ".metrics",
                            message: "Here you can see your attendance, grades, and progress."
                        },
                        {
                            selector: "#logoutbtn",
                            message: "Click here to log out securely."
                        }
                    ];
                } else {
                    // Small screens (Mobile/Tablets) - Exclude sidebar and navbar
                    steps = [{
                            selector: ".hamburger",
                            message: "This is the navigation Menu where you can access different sections."
                        },
                        {
                            selector: ".metrics",
                            message: "Here you can see your attendance, grades, and progress."
                        },
                        {
                            selector: "#logoutbtn",
                            message: "Click here to log out securely."
                        }
                    ];
                }

                metricsSection.style.display = "none"; // Hide metrics initially

                function showStep() {
                    if (currentStep >= steps.length) {
                        endTour();
                        return;
                    }

                    const {
                        selector,
                        message
                    } = steps[currentStep];
                    const element = document.querySelector(selector);

                    if (element) {
                        const rect = element.getBoundingClientRect();
                        let top = rect.top + window.scrollY + rect.height + 10;
                        let left = rect.left;

                        popup.innerHTML = `<p>${message}</p><button id='nextStep'>Next</button>`;
                        popup.style.display = "block";

                        // Adjust position if popup goes outside the screen
                        const popupRect = popup.getBoundingClientRect();
                        const screenWidth = window.innerWidth;
                        const screenHeight = window.innerHeight;

                        // Adjust Left Position if Overflowing Right
                        if (left + popupRect.width > screenWidth) {
                            left = screenWidth - popupRect.width - 20; // 20px padding from the right
                        }
                        if (left < 10) left = 10; // Prevents going off the left side

                        // Adjust Top Position if Overflowing Bottom
                        if (top + popupRect.height > screenHeight) {
                            top = rect.top + window.scrollY - popupRect.height - 10; // Move above the element
                        }
                        if (top < 10) top = 10; // Prevents going off the top side

                        popup.style.top = `${top}px`;
                        popup.style.left = `${left}px`;

                        document.getElementById("nextStep").addEventListener("click", nextStep);
                    }
                }


                function nextStep() {
                    currentStep++;
                    showStep();
                }

                function startTour() {
                    currentStep = 0;
                    onboardingModal.style.display = "none";
                    overlay.style.display = "none"; // Ensure overlay remains hidden
                    metricsSection.style.display = "flex"; // Show metrics section
                    showStep();
                }

                function endTour() {
                    popup.style.display = "none";
                    overlay.style.display = "none"; // Ensure overlay remains hidden
                    metricsSection.style.display = "flex"; // Ensure metrics are visible after tour
                    completeOnboarding(1);
                }

                document.getElementById("start-tour-btn").addEventListener("click", startTour);
                document.getElementById("skip-btn").addEventListener("click", endTour);

                function completeOnboarding(pageNo) {

                    fetch("api/update_on.php", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/x-www-form-urlencoded"
                            },
                            body: `page_no=${pageNo}`
                        })
                        .then(response => response.text())
                        .then(data => {
                            console.log("Response:", data);
                            window.location.reload();
                        })
                        .catch(error => console.error("Error:", error));
                }

            }


        });
    </script>



</body>

</html>