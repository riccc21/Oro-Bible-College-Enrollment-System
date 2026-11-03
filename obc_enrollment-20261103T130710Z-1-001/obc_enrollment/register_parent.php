<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}
    
$conn = new mysqli("localhost", "root", "", "obc_enrollment");

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Connection failed"]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handling both regular POST and JSON body
    $email = isset($_POST['email']) ? trim($_POST['email']) : "";
    $password = isset($_POST['password']) ? trim($_POST['password']) : "";

    if (empty($email) || empty($password)) {
        echo json_encode(["status" => "error", "message" => "Fields cannot be empty"]);
        exit();
    }

    $role = "Parent"; 

    $check = $conn->prepare("SELECT id FROM accounts WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Email already registered"]);
    } else {
        $stmt = $conn->prepare("INSERT INTO accounts (email, password, role, status) VALUES (?, ?, ?, 'Active')");
        $stmt->bind_param("sss", $email, $password, $role);
        
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Registration successful!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Database error during save"]);
        }
    }
}
$conn->close();
?>