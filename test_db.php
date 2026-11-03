<?php
// Headers to allow Flutter to talk to PHP
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$conn = new mysqli("localhost", "root", "", "obc_enrollment");

if ($conn->connect_error) {
    // If this shows, your MySQL is either OFF or the DB name is wrong
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]);
} else {
    echo json_encode(["status" => "success", "message" => "Database connected perfectly!"]);
}
?>