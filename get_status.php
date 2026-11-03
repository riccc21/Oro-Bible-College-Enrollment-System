<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$conn = new mysqli("localhost", "root", "", "obc_enrollment");

// Use $_REQUEST to catch both GET and POST, and trim to remove hidden spaces
$email = isset($_REQUEST['email']) ? trim($_REQUEST['email']) : "";

if (empty($email)) {
    echo json_encode(["success" => false, "message" => "No email provided"]);
    exit;
}

// Debugging: This helps you see what the PHP is actually searching for
// You can remove this line once it works
// die(json_encode(["debug_received_email" => $email])); 

$sql = "SELECT status, registrar_remarks FROM enrollments WHERE email = ? ORDER BY id DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode([
        "success" => true,
        "status" => $row['status'],
        "remarks" => $row['registrar_remarks'] ?? "Review in progress."
    ]);
} else {
    echo json_encode([
        "success" => false, 
        "message" => "No record found for " . $email
    ]);
}
?>