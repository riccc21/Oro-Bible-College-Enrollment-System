<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { exit; }

$conn = new mysqli("localhost", "root", "", "obc_enrollment");

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Connection failed"]));
}

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (!empty($email) && !empty($password)) {
    // We check the 'accounts' table specifically
    $sql = "SELECT * FROM accounts WHERE email = '$email' AND password = '$password'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo json_encode(["status" => "success", "message" => "Login successful"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid email or password"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Please fill all fields"]);
}

$conn->close();
?>