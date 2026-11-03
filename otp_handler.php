<?php
session_start();

// --- 1. INCLUDE PHPMailer MANUALLY ---
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Connection to your database
$conn = new mysqli("localhost", "root", "", "obc_enrollment");

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Connection failed"]);
    exit;
}

$action = $_POST['action'] ?? '';

// --- ACTION 1: SEND OTP ---
if ($action == 'send_otp') {
    $email = $_POST['email'] ?? '';
    $otp = rand(100000, 999999); 

    // Update the database first - targets 'accounts' table and 'reset_code' column
    $stmt = $conn->prepare("UPDATE accounts SET reset_code = ? WHERE email = ?");
    $stmt->bind_param("ss", $otp, $email);
    
    if ($stmt->execute()) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'ianjaycalingasanian@gmail.com';
            $mail->Password   = 'twag jryv dium naxw'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('no-reply@obcportal.com', 'OBC System');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Your Verification Code';
            $mail->Body    = "Your OTP code is: <b>$otp</b>";

            $mail->send();
            echo json_encode(["status" => "success"]);
        } catch (Exception $e) {
            echo json_encode(["status" => "error", "message" => $mail->ErrorInfo]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Database update failed"]);
    }
    exit; // Stop execution after sending
}

// --- ACTION 2: VERIFY OTP (Moved outside of send_otp block) ---
if ($action == 'verify_otp') {
    $email = trim($_POST['email'] ?? '');
    $user_otp = trim($_POST['otp'] ?? '');

    // Fetch from the 'accounts' table
    $stmt = $conn->prepare("SELECT reset_code FROM accounts WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result && (string)$result['reset_code'] === (string)$user_otp && !empty($user_otp)) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid or expired code"]);
    }
    exit;
}

// --- ACTION 3: UPDATE PASSWORD ---
if ($action == 'update_password') {
    $email = $_POST['email'] ?? '';
    $newPass = $_POST['new_password'] ?? ''; // Hash this if your system requires it

    $stmt = $conn->prepare("UPDATE accounts SET password = ?, reset_code = NULL WHERE email = ?");
    $stmt->bind_param("ss", $newPass, $email);
    
    if ($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error"]);
    }
    exit;
}
if ($action == 'update_password') {
    $email = $_POST['email'] ?? '';
    $newPass = $_POST['new_password'] ?? '';

    // We update the password AND set reset_code to NULL so the old code can't be used again
    $stmt = $conn->prepare("UPDATE accounts SET password = ?, reset_code = NULL WHERE email = ?");
    $stmt->bind_param("ss", $newPass, $email);
    
    ob_clean();
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Password updated successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update password"]);
    }
    exit;
}
?>