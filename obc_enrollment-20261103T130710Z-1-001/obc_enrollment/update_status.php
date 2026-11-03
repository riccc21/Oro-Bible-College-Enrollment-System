<?php
// update_status.php
header('Content-Type: application/json');

// Database Connection
$conn = new mysqli("localhost", "root", "", "obc_enrollment");

// Get the raw POST data from the JavaScript fetch()
$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['id']) && isset($data['status'])) {
    $id = intval($data['id']);
    $status = $conn->real_escape_string($data['status']);

    // Update the enrollments table
    // Make sure 'status' column exists in your 'enrollments' table
    $query = "UPDATE enrollments SET status = '$status' WHERE id = $id";
    
    if ($conn->query($query)) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => $conn->error]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid input"]);
}
?>