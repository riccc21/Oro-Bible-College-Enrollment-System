<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { exit; }

$conn = new mysqli("localhost", "root", "", "obc_enrollment");

$email = $_POST['email'] ?? '';

if (!empty($email)) {
    $sql = "DELETE FROM accounts WHERE email = '$email'";
    
    if ($conn->query($sql) === TRUE) {
        echo json_encode(["status" => "success", "message" => "Account deleted"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error deleting record"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Email required"]);
}

$conn->close();
?>