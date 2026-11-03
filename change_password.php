<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { exit; }

$conn = new mysqli("localhost", "root", "", "obc_enrollment");

$email = $_POST['email'] ?? '';
$newPassword = $_POST['new_password'] ?? '';

if (!empty($email) && !empty($newPassword)) {
    // Update password where email matches
    $sql = "UPDATE accounts SET password = '$newPassword' WHERE email = '$email'";
    
    if ($conn->query($sql) === TRUE) {
        echo json_encode(["status" => "success", "message" => "Password updated!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database error"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Missing data"]);
}
$conn->close();
?>