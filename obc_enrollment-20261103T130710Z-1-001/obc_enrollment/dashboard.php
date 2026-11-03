<?php
    session_start();

    // Check if user is logged in
    if (!isset($_SESSION['registrar'])) {
        header("Location: index.php");
        exit();
    }

    // Database Connection
    $conn = new mysqli("localhost", "root", "", "obc_enrollment");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Fetch dashboard data
    $totalStudents = 0;
    $pendingEnrollments = 0;
    $totalSections = 0;

    // Get total students
    $result = $conn->query("SELECT COUNT(*) as count FROM enrollments WHERE status='Pending'");
    if ($result) {
        $row = $result->fetch_assoc();
        $totalStudents = $row['count'];
    }

    // Get pending enrollments
    $result = $conn->query("SELECT COUNT(*) as count FROM enrollments");
    if ($result) {
        $row = $result->fetch_assoc();
        $pendingEnrollments = $row['count'];
    }

    // Get total sections
    $result = $conn->query("SELECT COUNT(*) as count FROM sections");
    if ($result) {
        $row = $result->fetch_assoc();
        $totalSections = $row['count'];
    }
    // Total students = all students (Active + Pending)
    $result = $conn->query("SELECT COUNT(*) as count FROM enrollments");
    $totalStudents = $result ? $result->fetch_assoc()['count'] : 0;

    // Pending enrollments = students still Pending
    $result = $conn->query("SELECT COUNT(*) as count FROM enrollments WHERE status='Pending'");
    $pendingEnrollments = $result ? $result->fetch_assoc()['count'] : 0;

    // Active students = students with status Active
    $result = $conn->query("SELECT COUNT(*) as count FROM enrollments WHERE status='Active'");
    $activeStudents = $result ? $result->fetch_assoc()['count'] : 0;

    // NEW: Get count of New Students specifically
    $resNew = $conn->query("SELECT COUNT(*) as count FROM enrollments WHERE student_type='New' AND status='Pending'");
    $totalNewStudents = $resNew ? $resNew->fetch_assoc()['count'] : 0;
    ?>


    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Registrar Dashboard</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
        <link rel="stylesheet" href="dashboard.css">
        <style> 
            .main-content {
                font-weight: bold;
                font-family: 'Segoe UI', sans-serif;
                transition: opacity 0.3s ease; 
            }
            
            .user-name {
                font-family: sans-serif;
                font-weight: bold;
                color: #1e3a8a;
                text-transform: capitalize;
                border-bottom: 2px solid #1e3a8a;
                padding-bottom: 2px;
            }

            .sidebar {
                font-family: 'Times New Roman', Times, serif;
                font-size: 1.3rem;
            }

            /* RED LOGOUT BUTTON STYLING */
            .sidebar ul li a.logout-btn {
                color: #dc2626; /* Red text */
                font-weight: bold;
                transition: 0.3s;
            }

            .sidebar ul li a.logout-btn:hover {
                background-color: #fee2e2; /* Light red background on hover */
                color: #991b1b; /* Darker red text on hover */
            }

            .sidebar ul li a.logout-btn i {
                color: #dc2626; /* Red icon */
            }

            .welcome-text {
                font-family: 'Segoe UI', Tahoma, Verdana, sans-serif;
                font-size: 1.2rem;
                color: #333;
            }

            .cards {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 15px;
                margin-top: 20px;
            }

            .card {
                background: white;
                padding: 15px;
                border-radius: 10px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.05);
                border-left: 5px solid #1e3a8a;
            }

            .card h3 i {
                margin-right: 10px;
                color: #1e3a8a; 
                width: 25px;    
                text-align: center;
            }

            .data-space {
                font-size: 1.5rem;
                color: #1e3a8a;
                margin-top: 10px;
            }

            /* REGISTRAR ACTION NOTIFICATION STYLING */
            .status-alert {
                padding: 15px;
                margin-bottom: 20px;
                border-radius: 8px;
                font-family: 'Segoe UI', sans-serif;
                display: flex;
                align-items: center;
                animation: fadeIn 0.5s ease-in;
            }
            .alert-success { background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
            .alert-declined { background-color: #e0f2fe; color: #075985; border: 1px solid #bae6fd; }
            
            @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

            .alert-box {
                background: white;
                padding: 20px;
                border-radius: 10px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.05);
                margin-top: 30px;
                border: 1px solid #fee2e2; 
            }

            .alert-box h3 { 
                color: #dc2626; 
                margin-top: 0; 
                display: flex;
                align-items: center;
            }

            .alert-box h3 i {
                margin-right: 10px;
                animation: pulse-red 2s infinite;
            }

            @keyframes pulse-red {
                0% { transform: scale(1); opacity: 1; }
                50% { transform: scale(1.1); opacity: 0.8; }
                100% { transform: scale(1); opacity: 1; }
            }

            .notification-space p {
                padding: 10px;
                border-bottom: 1px solid #f1f5f9;
                font-weight: normal;
            }

            .fade-out { opacity: 0; }
            
            /* NEW DESIGN ADDITIONS */
            table { border-collapse: collapse; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
            th { background: #f8fafc; padding: 15px; border-bottom: 2px solid #e2e8f0; }
            td { padding: 12px; border-bottom: 1px solid #f1f5f9; }
            .btn-style { padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer; font-weight: bold; transition: 0.2s; }
            .btn-style:hover { opacity: 0.8; }

            /* Added Styling for Status Badges */
            .type-badge {
                padding: 4px 8px;
                border-radius: 4px;
                font-size: 0.85rem;
                color: white;
            }
            .type-new { background-color: #3b82f6; } /* Blue for New */
            .type-returning { background-color: #8b5cf6; } /* Purple for Returning */
        </style>
    </head>
    <body>

    <div class="dashboard-container">

        <div class="sidebar">
            <div class="logo-container">
                <img src="Oro_Bible_Logo.png" alt="School Logo">
            </div>

            <h2>Registrar</h2>
            <ul>
                <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="students.php"><i class="fas fa-user-graduate"></i> Students</a></li>
                <li><a href="sections.php"><i class="fas fa-layer-group"></i> Sections</a></li>
                <li><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
            <h1>DASHBOARD</h1>
            <p class="welcome-text">
                Welcome, <span class="user-name"><?php echo htmlspecialchars($_SESSION['registrar_name']); ?></span>
            </p>
            <br>

            <?php if(isset($_GET['status'])): ?>
                <?php if($_GET['status'] == 'declined'): ?>
                    <div class="status-alert alert-declined">
                        <i class="fas fa-info-circle" style="margin-right:10px;"></i> 
                        Enrollment Declined. The student has been notified to resubmit clear documents.
                    </div>
                <?php elseif($_GET['status'] == 'active'): ?>
                    <div class="status-alert alert-success">
                        <i class="fas fa-check-circle" style="margin-right:10px;"></i> 
                        Enrollment Approved successfully!
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            

            <div class="cards">
                <div class="card">
                    <h3><i class="fas fa-users"></i> Total Students</h3>
                    <div class="data-space"><?php echo $totalStudents; ?></div>
                </div>

                <div class="card">
                    <h3><i class="fas fa-clock"></i> Pending Enrollments</h3>
                    <div class="data-space"><?php echo $pendingEnrollments; ?></div>
                </div>

                <div class="card">
                    <h3><i class="fas fa-user-plus"></i> New Student Applicants</h3>
                    <div class="data-space"><?php echo $totalNewStudents; ?></div>
                </div>

                <div class="card">
                    <h3><i class="fas fa-chalkboard-user"></i> Total Sections</h3>
                    <div class="data-space"><?php echo $totalSections; ?></div>
                </div>
            </div>
            
            <br>

            <div class="alert-box">
                <h3><i class="fas fa-bell"></i> Pending Enrollment Notifications</h3>

    <table border="1" width="100%" cellpadding="10">
    <tr>
        
        <th><center>Student Name</center></th>
        <th><center>Student Type</center></th> <th><center>Attachment</center></th>
        <th><center>Action</center></th>
        
    </tr>

    <?php
    $result = $conn->query("SELECT * FROM enrollments WHERE status='Pending'");

    while($row = $result->fetch_assoc()){
        // Logic to determine CSS class based on type
        $typeClass = ($row['student_type'] == 'New') ? 'type-new' : 'type-returning';
    ?>

    <tr>
    <td><?php echo $row['last_name'] . ", " . $row['first_name'] . " " . $row['middle_initial'] . " " . $row['suffix']; ?></td>

    <td>
        <center>
            <span class="type-badge <?php echo $typeClass; ?>">
                <?php echo htmlspecialchars($row['student_type']); ?>
            </span>
        </center>
    </td>

    <td>
    <center>
    <a href="<?php echo $row['path_birth_cert']; ?>" target="_blank">View Birth Cert</a><br>
    <a href="<?php echo $row['path_form_137']; ?>" target="_blank">View Form 137</a><br>
    <a href="<?php echo $row['path_good_moral']; ?>" target="_blank">View Good Moral</a><br>
    <a href="<?php echo $row['path_2x2_photo']; ?>" target="_blank">View 2x2</a>
    </center>
    </td>

    <td>
    <center>
    <a href="process_enrollment.php?id=<?php echo $row['student_id']; ?>&action=accept">
    <button class="btn-style" style="background:green;color:white;">Accept</button>
    </a>

    <button class="btn-style" style="background:red;color:white;" onclick="declineWithReason(<?php echo $row['student_id']; ?>)">
        Decline
    </button>
    </center>
    </td>

    </tr>

    <?php } ?>
    </table>
            </div>
        </div>
    </div>

    <script>
    // Logic for the Decline Confirmation
    function declineWithReason(studentId) {
        // Simple confirmation dialog
        if (confirm("Are you sure you want to decline this enrollment?")) {
            // Redirect to process_enrollment.php with decline action
            window.location.href = "process_enrollment.php?id=" + studentId + "&action=decline";
        }
    }

    document.querySelectorAll('.sidebar ul li a').forEach(link => {
    link.addEventListener('click', function(event) {
        const href = this.getAttribute('href');
        if(href === "logout.php" || href === "#") return; 

        event.preventDefault(); 
        const mainContent = document.querySelector('.main-content');
        mainContent.classList.add('fade-out');
        setTimeout(() => {
        window.location.href = href;
        }, 300);
    });
    });
    </script>
    </body>
    </html>