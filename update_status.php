<?php
header("Access-Control-Allow-Origin: *");
require_once('db_connect.php');

$id = $_POST['id'];
$status = $_POST['status'];
$remarks = $_POST['remarks'] ?? '';

$sql = "UPDATE enrollments SET status = ?, remarks = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $status, $remarks, $id);

if ($stmt->execute()) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error"]);
}
?>