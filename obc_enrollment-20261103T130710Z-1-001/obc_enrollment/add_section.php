<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['registrar'])) {
    header("Location: index.php");
    exit();
}

// Database Connection
$conn = new mysqli("localhost", "root", "", "obc_enrollment");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle section creation
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $section_name = trim($_POST['section_name']);
    $grade_level = trim($_POST['grade_level']);
    $adviser = trim($_POST['adviser']);

    if (empty($section_name) || empty($grade_level) || empty($adviser)) {
        $_SESSION['error'] = "All fields are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO sections (section_name, grade_level, adviser) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $section_name, $grade_level, $adviser);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Section created successfully!";
        } else {
            $_SESSION['error'] = "Error creating section: " . $stmt->error;
        }
        $stmt->close();
    }
}

$conn->close();
header("Location: sections.php");
exit();
?>
