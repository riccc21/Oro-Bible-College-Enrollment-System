<?php
// Save this as assign_student.php in your obc_enrollment folder
$conn = new mysqli("localhost", "root", "", "obc_enrollment");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = intval($_POST['student_id']);
    $sectionId = intval($_POST['section_id']);

    $stmt = $conn->prepare("UPDATE enrollments SET section_id = ? WHERE student_id = ?");
    $stmt->bind_param("ii", $sectionId, $studentId);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}
$conn->close();
?>