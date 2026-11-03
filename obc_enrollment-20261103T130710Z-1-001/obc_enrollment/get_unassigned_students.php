<?php
header("Content-Type: application/json");
$conn = new mysqli("localhost", "root", "", "obc_enrollment");

if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed"]));
}

$grade = $_GET['grade'] ?? 0;
$grade = intval($grade);
$gradeStr = "Grade " . $grade;

// 1. We change 'id' to 'student_id' so the frontend passes the correct key
// 2. We check for both NULL and 0 to ensure students are truly unassigned
$stmt = $conn->prepare("
    SELECT student_id, first_name, last_name 
    FROM enrollments 
    WHERE (grade_level = ? OR grade_level = ?) 
    AND (section_id IS NULL OR section_id = 0)
    AND status = 'Active' 
    ORDER BY last_name ASC
");

$stmt->bind_param("is", $grade, $gradeStr);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
while($row = $result->fetch_assoc()){
    // We map 'student_id' to the key 'id' so your existing JS 'student.id' still works
    $students[] = [
        "id" => $row['student_id'], 
        "first_name" => $row['first_name'],
        "last_name" => $row['last_name']
    ];
}

echo json_encode($students);
$stmt->close();
$conn->close();
?>