<?php
session_start();

// Redirect to dashboard if already logged in
if (isset($_SESSION['registrar'])) {
    header("Location: dashboard.php");
    exit();
}

// 1. Database Connection
$conn = new mysqli("localhost", "root", "", "obc_enrollment");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. Login Logic
$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        $stmt = $conn->prepare("SELECT name, password FROM users WHERE email = ? AND role = 'Registrar' AND status = 'Active'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            // Plain text comparison as per system ledger
            if ($password === $row['password']) {
                $_SESSION['registrar'] = $email;
                $_SESSION['registrar_name'] = $row['name'];
                
                $stmt->close();
                $conn->close();
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid Password.";
            }
        } else {
            $error = "User not found or account inactive.";
        }
        $stmt->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Portal Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* STATIC FULL BACKGROUND CONTAINER */
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: 'Segoe UI', sans-serif;
            overflow: hidden; 
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* Static Background Layer - Animation Removed */
        .bg-static {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('School_Bg.jpg') no-repeat center center;
            background-size: cover;
        }

        /* LOGIN CARD */
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 45px 40px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.4);
            width: 100%;
            max-width: 420px;
            text-align: center;
            border-top: 5px solid #1e3a8a;
            position: relative;
            z-index: 1;
        }

        /* Branding */
        .logo-container img { width: 100px; margin-bottom: 15px; }
        .school-title {
            font-family: 'Times New Roman', Times, serif; 
            font-size: 2.2rem;
            color: #1e3a8a;
            margin: 0;
            font-weight: bold;
        }
        .portal-subtitle {
            color: #64748b;
            margin-bottom: 30px;
            font-size: 1.1rem;
            font-weight: 600;
        }

        /* Form Styling */
        .input-group { margin-bottom: 20px; text-align: left; }
        .input-group label {
            display: block;
            margin-bottom: 8px;
            color: #1e3a8a;
            font-weight: 700;
            font-size: 0.9rem;
        }
        .input-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 1rem;
            transition: 0.3s;
        }
        .input-group input:focus {
            border-color: #1e3a8a;
            outline: none;
            box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
        }

        /* Button */
        .login-btn {
            width: 100%;
            padding: 14px;
            background-color: #1e3a8a;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }
        .login-btn:hover {
            background-color: #172554;
            transform: translateY(-2px);
        }

        .error-msg {
            background: #fee2e2;
            color: #dc2626;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            font-weight: bold;
        }

        /* Entrance Animation for Card Only */
        .animate-slow {
            animation: slideUp 0.8s ease-out;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <div class="bg-static"></div>

    <div class="login-card animate-slow">
        <div class="logo-container">
            <img src="Oro_Bible_Logo.png" alt="OBC Logo">
        </div>
        <h1 class="school-title">Oro Bible College</h1>
        <p class="portal-subtitle">Registrar's Portal</p>

        <?php if($error): ?>
            <div class="error-msg">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="index.php">
            <div class="input-group">
                <label><i class="fas fa-envelope"></i> Email Address</label>
                <input type="email" name="username" placeholder="Enter your email" required>
            </div>
            <div class="input-group">
                <label><i class="fas fa-lock"></i> Password</label>
                <input type="password" name="password" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="login-btn">
                <i class="fas fa-shield-halved"></i> Secure Login
            </button>
            <div style="margin-top: 15px; font-size: 0.9rem; color: #64748b;">
                <i class="fas fa-info-circle"></i> For Login Concerns, Please Contact Admin Services.
            </div>
        </form>
    </div>

</body>
</html>