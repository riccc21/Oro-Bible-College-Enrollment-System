<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "obc_enrollment"; // Use the exact name from your phpMyAdmin

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Connection failed"]));
}
?>