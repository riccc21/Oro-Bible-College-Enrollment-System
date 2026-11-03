<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
require_once(__DIR__ . '/db_connect.php');

$data = json_decode(file_get_contents("php://input"), true);

// Extract data from Flutter (Ensure these keys match exactly)
$full_name = ($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '');
$grade_level = $data['grade_level'] ?? 'N/A';
$status = 'pending';

// Save to the table your dashboard uses
$sql = "INSERT INTO students (full_name, grade_level, status) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $full_name, $grade_level, $status);

if ($stmt->execute()) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => $stmt->error]);
}
?>