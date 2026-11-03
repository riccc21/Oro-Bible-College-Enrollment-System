<?php
session_start();

// --- 1. INCLUDE PHPMAILER MANUALLY ---
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- 2. DATABASE CONNECTION ---
$conn = new mysqli("localhost", "root", "", "obc_enrollment");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

function generateOTP($length = 6) {
    return str_pad(rand(0, pow(10, $length)-1), $length, '0', STR_PAD_LEFT);
}

// --- 3. UPDATED PHPMAILER FUNCTION ---
function sendOTPEmail($to, $otp) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'ianjaycalingasanian@gmail.com'; // CHANGE THIS
        $mail->Password   = 'mioj lnqf tgjj twpd';   // YOUR 16-DIGIT APP PASSWORD
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('no-reply@obcportal.com', 'OBC Admin Portal');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = "Your Password Reset OTP Code";
        $mail->Body    = "
            <div style='font-family: Arial; border: 1px solid #ddd; padding: 20px; border-radius: 10px;'>
                <h2 style='color: #1e3a8a;'>Password Reset Request</h2>
                <p>Hello,</p>
                <p>An administrator has initiated a password reset for your account. Use the code below to log in:</p>
                <div style='font-size: 24px; font-weight: bold; color: #1e3a8a; background: #f0f2f5; padding: 10px; text-align: center; border-radius: 5px;'>$otp</div>
                <p>This code is valid for 5 minutes. Please change your password immediately after logging in.</p>
                <p>- OBC Admin Team</p>
            </div>";

        return $mail->send();
    } catch (Exception $e) {
        return false;
    }
}

$error = "";
$view_archived = isset($_GET['view']) && $_GET['view'] == 'archived';
$current_page = isset($_GET['page']) ? $_GET['page'] : 'users';
$role_filter = isset($_GET['role_filter']) ? $_GET['role_filter'] : 'all';

// --- 4. LOGOUT LOGIC ---
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin_accounts.php");
    exit();
}

// --- 5. ADMIN LOGIN LOGIC ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login_admin'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $stmt = $conn->prepare("SELECT password FROM accounts WHERE email = ? AND role = 'Admin' AND status = 'Active'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if ($password === $row['password']) { 
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_email'] = $email;
        } else { $error = "Invalid Password."; }
    } else { $error = "Admin account not found."; }
}

// --- 6. MANAGEMENT ACTIONS ---
if (isset($_SESSION['admin_logged_in'])) {
    if (isset($_POST['add'])) {
        $name = $conn->real_escape_string($_POST['name']); 
        $email = $conn->real_escape_string($_POST['email']);
        $role = $_POST['role']; 
        $password = $_POST['password']; 
        
        if ($role == 'Registrar') {
            $conn->query("INSERT INTO users (name, email, password, role, status) VALUES ('$name', '$email', '$password', 'Registrar', 'Active')");
        } else {
            $conn->query("INSERT INTO accounts (name, email, password, role, status) VALUES ('$name', '$email', '$password', '$role', 'Active')");
        }
        header("Location: admin_accounts.php?page=users&role_filter=$role_filter");
        exit();
    }
    
    if (isset($_GET['toggle'])) {
        $id = intval($_GET['toggle']);
        $source = $_GET['src'] == 'u' ? 'users' : 'accounts';
        $res = $conn->query("SELECT status FROM $source WHERE id=$id");
        $row = $res->fetch_assoc();
        $newStatus = ($row['status'] == 'Active') ? 'Inactive' : 'Active';
        $conn->query("UPDATE $source SET status='$newStatus' WHERE id=$id");
        header("Location: admin_accounts.php?page=users&role_filter=$role_filter"); exit();
    }

    // UPDATED RESET ACTION
    if (isset($_POST['submit_reset'])) {
        $id = intval($_POST['user_id']);
        $source = $_POST['src'] == 'u' ? 'users' : 'accounts';
        $res = $conn->query("SELECT email FROM $source WHERE id=$id");
        if($res && $row = $res->fetch_assoc()){
            $email = $row['email'];
            $otp = generateOTP();
            // We update the password to the OTP value
            $conn->query("UPDATE $source SET password='$otp' WHERE id=$id");
            
            if(sendOTPEmail($email, $otp)) {
                echo "<script>alert('OTP sent successfully to $email');</script>";
            } else {
                echo "<script>alert('Failed to send email. Check your SMTP settings.');</script>";
            }
        }
    }

    if (isset($_POST['confirm_archive'])) {
        $id = intval($_POST['archive_id']);
        $source = $_POST['src'] == 'u' ? 'users' : 'accounts';
        $conn->query("UPDATE $source SET status='Archived' WHERE id=$id");
        header("Location: admin_accounts.php?page=users&role_filter=$role_filter"); exit();
    }

    if (isset($_GET['restore'])) {
        $id = intval($_GET['restore']);
        $source = $_GET['src'] == 'u' ? 'users' : 'accounts';
        $conn->query("UPDATE $source SET status='Active' WHERE id=$id");
        header("Location: admin_accounts.php?view=archived"); exit();
    }
}

$student_data = null;
if (isset($_GET['view_student'])) {
    $sid = $conn->real_escape_string($_GET['view_student']);
    $student_data = $conn->query("SELECT * FROM enrollments WHERE student_id = '$sid'")->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head> 
    <meta charset="UTF-8">
    <title>Admin Portal - OBC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        @keyframes slowReveal { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .animate-slow { animation: slowReveal 0.8s ease-out forwards; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f0f2f5; margin: 0; min-height: 100vh; display: flex; justify-content: center; align-items: center; }
        .login-bg { background: #1e3a8a; width: 100%; height: 100vh; display: flex; justify-content: center; align-items: center; }
        .login-box { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 15px 35px rgba(0,0,0,0.3); width: 100%; max-width: 400px; text-align: center; }
        .login-logo { width: 120px; height: auto; margin-bottom: 20px; }
        .login-box h2 { color: #1e3a8a; margin-bottom: 30px; font-weight: 700; font-size: 26px; }
        .input-group { position: relative; margin-bottom: 15px; }
        .input-group i { position: absolute; left: 15px; top: 15px; color: #94a3b8; }
        .input-group input { width: 100%; padding: 14px 14px 14px 45px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; font-size: 15px; outline: none; transition: 0.3s; }
        .login-btn { background: #1e3a8a; color: white; border: none; padding: 15px; width: 100%; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: 600; margin-top: 15px; transition: 0.3s; }
        .admin-wrapper { display: flex; width: 100%; min-height: 100vh; align-items: stretch; background: #f8fafc;}
        .sidebar { width: 260px; background: white; border-right: 1px solid #e2e8f0; padding: 30px 20px; display: flex; flex-direction: column; }
        .sidebar h2 { font-family: 'Times New Roman', serif; color: #1e3a8a; font-size: 1.5rem; margin-bottom: 30px; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; }
        .sidebar ul { list-style: none; padding: 0; }
        .sidebar ul li a { text-decoration: none; color: #64748b; font-weight: 600; display: block; padding: 12px; border-radius: 8px; transition: 0.3s; margin-bottom: 5px; }
        .sidebar ul li a:hover, .sidebar ul li a.active { background: #1e3a8a; color: white; }
        .container { flex: 1; padding: 40px; max-width: 1200px; }
        .add-form { background: white; padding: 25px; border-radius: 10px; margin-bottom: 25px; display: flex; gap: 12px; flex-wrap: wrap; border: 1px solid #e2e8f0; }
        .add-form input, .add-form select, .filter-select { padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; }
        .table-card { background: white; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #e2e8f0; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8fafc; color: #1e3a8a; padding: 18px 15px; text-align: left; font-size: 12px; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
        td { padding: 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .btn { padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 12px; cursor: pointer; border: 1px solid; background: white; display: inline-flex; align-items: center; gap: 5px; }
        .btn-status-active { color: #ef4444; border-color: #fca5a5; } 
        .btn-status-inactive { color: #10b981; border-color: #6ee7b7; }
        .btn-main { background: #1e3a8a; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; }
        .modal-overlay { display: block; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; backdrop-filter: blur(2px); }
        .modal-box { background: white; width: 400px; margin: 15vh auto; padding: 30px; border-radius: 15px; text-align: center; }
    </style>
</head>
<body>

<?php if (!isset($_SESSION['admin_logged_in'])): ?>
    <div class="login-bg">
        <div class="login-box animate-slow">
            <img src="Oro_Bible_Logo.png" alt="OBC Logo" class="login-logo">
            <h2>Admin Login</h2>
            <form method="POST">
                <div class="input-group"><i class="fas fa-envelope"></i><input type="email" name="email" placeholder="Admin Email" required></div>
                <div class="input-group"><i class="fas fa-lock"></i><input type="password" name="password" placeholder="Password" required></div>
                <button type="submit" name="login_admin" class="login-btn">Login to Portal</button>
            </form>
        </div>
    </div>
<?php else: ?>
    <div class="admin-wrapper">
        <div class="sidebar">
            <div style="text-align:center; margin-bottom:20px;"><img src="Oro_Bible_Logo.png" alt="Logo" style="width:70px;"></div>
            <center><h2>Admin Panel</h2></center>
            <ul>
                <li><a href="?page=users" class="<?= $current_page=='users' && !$view_archived ? 'active' : '' ?>"><i class="fas fa-users-cog"></i> User Management</a></li>
                <li><a href="?page=sections" class="<?= $current_page=='sections' ? 'active' : '' ?>"><i class="fas fa-layer-group"></i> Sections</a></li>
                <li><a href="?page=students" class="<?= $current_page=='students' ? 'active' : '' ?>"><i class="fas fa-user-graduate"></i> All Students</a></li>
                <li><a href="?view=archived" class="<?= $view_archived ? 'active' : '' ?>"><i class="fas fa-archive"></i> Archive Bin</a></li>
                <li style="margin-top:auto;"><a href="?logout=1" style="color:#ef4444;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <div class="container animate-slow">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h2 style="color:#1e3a8a; margin:0;"><?= $view_archived ? "Archive Bin" : "User Management" ?></h2>
                
                <?php if ($current_page == 'users' && !$view_archived): ?>
                <form method="GET" id="filterForm">
                    <input type="hidden" name="page" value="users">
                    <select name="role_filter" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                        <option value="all" <?= $role_filter == 'all' ? 'selected' : '' ?>>Show All Roles</option>
                        <option value="Registrar" <?= $role_filter == 'Registrar' ? 'selected' : '' ?>>Only Registrars</option>
                        <option value="student" <?= $role_filter == 'student' ? 'selected' : '' ?>>Only Students</option>
                    </select>
                </form>
                <?php endif; ?>
            </div>

            <?php if ($current_page == 'users' && !$view_archived): ?>
                <form method="POST" class="add-form">
                    <input type="text" name="name" placeholder="Full Name" required style="flex:2;">
                    <input type="email" name="email" placeholder="Email Address" required style="flex:2;">
                    <input type="password" name="password" placeholder="Set Password" required style="flex:1;">
                    <select name="role" style="flex:1;">
                        <option value="Registrar">Registrar</option>
                        <option value="student">Student Account</option>
                    </select>
                    <button type="submit" name="add" class="btn-main"><i class="fas fa-plus-circle"></i> Add</button>
                </form>
            <?php endif; ?>

            <div class="table-card">
                <table>
                    <thead>
                        <?php if($current_page == 'sections'): ?>
                            <tr><th>Section Name</th><th>Grade</th><th>Total Students</th><th>Action</th></tr>
                        <?php elseif($current_page == 'students'): ?>
                            <tr><th>ID</th><th>Full Name</th><th>Status</th><th>Grade & Section</th><th>Action</th></tr>
                        <?php else: ?>
                            <tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th>
                            <?php if ($role_filter !== 'student'): ?><th>Actions</th><?php endif; ?>
                            </tr>
                        <?php endif; ?>
                    </thead>
                    <tbody>
                        <?php
                        if ($current_page == 'sections') {
                            $res = $conn->query("SELECT *, (SELECT COUNT(*) FROM enrollments WHERE section_id = sections.section_id) as total FROM sections");
                            while($row = $res->fetch_assoc()) {
                                echo "<tr><td><b>{$row['section_name']}</b></td><td>Grade {$row['grade_level']}</td><td>{$row['total']} Students</td>
                                      <td><a href='?page=students&sec_id={$row['section_id']}' class='btn' style='background:#1e3a8a; color:white; border:none;'>View Students</a></td></tr>";
                            }
                        } elseif ($current_page == 'students') {
                            $sql = "SELECT e.*, s.section_name FROM enrollments e LEFT JOIN sections s ON e.section_id = s.section_id";
                            if(isset($_GET['sec_id'])) $sql .= " WHERE e.section_id = ".intval($_GET['sec_id']);
                            $res = $conn->query($sql);
                            while($row = $res->fetch_assoc()) {
                                echo "<tr><td>{$row['student_id']}</td><td><b>{$row['last_name']}, {$row['first_name']}</b></td><td><span style='color:#10b981; background:#dcfce7; padding:4px 10px; border-radius:10px;'>Active</span></td><td> {$row['grade_level']} - ".($row['section_name']??'N/A')."</td>
                                      <td>
                                        <a href='?page=students&view_student={$row['student_id']}&type=guard' class='btn' style='border-color:#10b981; color:#10b981;'>Guardian Info</a>
                                        <a href='?page=students&view_student={$row['student_id']}&type=more' class='btn' style='border-color:#3b82f6; color:#3b82f6;'>View More</a>
                                      </td></tr>";
                            }
                        } else {
                            $status_filter = $view_archived ? "status = 'Archived'" : "status != 'Archived'";
                            $reg_clause = ($role_filter == 'all' || $role_filter == 'Registrar') ? "AND role = 'Registrar'" : "AND 1=0";
                            $stu_clause = ($role_filter == 'all' || $role_filter == 'student') ? "AND role != 'Admin'" : "AND 1=0";

                            $union_sql = "
                                (SELECT id, name, email, role, status, 'u' as src FROM users WHERE $status_filter $reg_clause)
                                UNION
                                (SELECT id, name, email, role, status, 'a' as src FROM accounts WHERE $status_filter $stu_clause)
                                ORDER BY name ASC";
                            
                            $result = $conn->query($union_sql);
                            if ($result && $result->num_rows > 0):
                            while($row = $result->fetch_assoc()) {
                                $isActive = ($row['status'] == 'Active');
                                $displayName = !empty($row['name']) ? $row['name'] : "N/A";
                                $src = $row['src'];

                                echo "<tr><td><strong>" . $displayName . "</strong></td><td>{$row['email']}</td><td>" . ucfirst($row['role']) . "</td><td>{$row['status']}</td>";
                                
                                if ($role_filter !== 'student'):
                                    echo "<td>";
                                    if ($view_archived) {
                                        echo "<a href='?restore={$row['id']}&src=$src' class='btn' style='background:#10b981; color:white; border:none;'>Restore</a>";
                                    } else {
                                        echo "<a href='?toggle={$row['id']}&src=$src' class='btn ".($isActive?'btn-status-active':'btn-status-inactive')."'>".($isActive?'Deactivate':'Activate')."</a>";
                                        echo "<span class='btn' style='color:#f59e0b; border-color:#fcd34d; margin-left:5px;' onclick='openReset({$row['id']}, \"{$row['email']}\", \"$src\")'>Reset</span>";
                                        echo "<span class='btn' style='color:#64748b; border-color:#cbd5e1; margin-left:5px;' onclick='openArchive({$row['id']}, \"{$row['email']}\", \"$src\")'>Archive</span>";
                                    }
                                    echo "</td>";
                                endif;
                                echo "</tr>";
                            }
                            else:
                                echo "<tr><td colspan='5' style='text-align:center; padding:20px;'>No records found.</td></tr>";
                            endif;
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="archiveModal" class="modal-overlay" style="display:none;">
        <div class="modal-box"><h3>Archive User?</h3><p id="arcName"></p><form method="POST"><input type="hidden" name="archive_id" id="arcId"><input type="hidden" name="src" id="arcSrc"><button type="submit" name="confirm_archive" class="btn-main" style="background:#ef4444; width:100%;">Archive Account</button><button type="button" onclick="closeModals()" class="btn" style="width:100%; margin-top:10px; justify-content:center;">Cancel</button></form></div>
    </div>
    
    <div id="resetModal" class="modal-overlay" style="display:none;">
        <div class="modal-box"><h3>Send OTP Reset</h3><p id="resName"></p><form method="POST"><input type="hidden" name="user_id" id="resId"><input type="hidden" name="src" id="resSrc"><button type="submit" name="submit_reset" class="btn-main" style="background:#f59e0b; width:100%;">Confirm & Send</button><button type="button" onclick="closeModals()" class="btn" style="width:100%; margin-top:10px; justify-content:center;">Cancel</button></form></div>
    </div>

    <script>
        function openArchive(id, name, src) { document.getElementById('arcId').value = id; document.getElementById('arcName').innerText = name; document.getElementById('arcSrc').value = src; document.getElementById('archiveModal').style.display = 'block'; }
        function openReset(id, name, src) { document.getElementById('resId').value = id; document.getElementById('resName').innerText = name; document.getElementById('resSrc').value = src; document.getElementById('resetModal').style.display = 'block'; }
        function closeModals() { document.querySelectorAll('.modal-overlay').forEach(m => m.style.display = 'none'); }
    </script>
<?php endif; ?>
</body>
</html>