<?php
$conn = new mysqli("localhost", "root", "", "obc_enrollment");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$section_id = intval($_GET['section_id'] ?? 0);
$grade = intval($_GET['grade'] ?? 0);

if($section_id && $grade){
    $stmt = $conn->prepare("SELECT first_name,last_name FROM students WHERE section_id=? AND grade_level=? ORDER BY last_name ASC");
    $stmt->bind_param("ii",$section_id,$grade);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res->num_rows > 0){
        echo "<ul style='margin:0;padding-left:20px;'>";
        while($row = $res->fetch_assoc()){
            echo "<li>" . htmlspecialchars($row['last_name']) . ", " . htmlspecialchars($row['first_name']) . "</li>";
        }
        echo "</ul>";
    }
    $stmt->close();
}
$conn->close();