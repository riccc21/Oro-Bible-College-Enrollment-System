<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}

$conn = new mysqli("localhost", "root", "", "obc_enrollment");

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Connection failed"]));
}

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (!empty($email) && !empty($password)) {
    // 1. Check if email already exists
    $checkSql = "SELECT email FROM accounts WHERE email = '$email'";
    $checkResult = $conn->query($checkSql);

    if ($checkResult->num_rows > 0) {
        // Email exists, return specific message
        echo json_encode(["status" => "error", "message" => "Email Address is Already Existed"]);
    } else {
        // 2. Email is unique, proceed with registration
        $sql = "INSERT INTO accounts (email, password) VALUES ('$email', '$password')";
        
        if ($conn->query($sql) === TRUE) {
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Registration failed"]);
        }
    }
} else {
    echo json_encode(["status" => "error", "message" => "Empty fields"]);
}

$conn->close();
?>