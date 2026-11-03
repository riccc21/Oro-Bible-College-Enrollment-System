<?php
if (!isset($_GET['section_id'])) {
    echo "No section selected.";
    exit();
}

$section_id = intval($_GET['section_id']);

$conn = new mysqli("localhost","root","","obc_enrollment");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$sql = "SELECT first_name, last_name
        FROM enrollments
        WHERE section_id = $section_id
        ORDER BY last_name ASC";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<ul style='list-style:none; padding-left:0;'>";
    while($row = $result->fetch_assoc()){
        echo "<li style='padding:5px 0; border-bottom:1px solid #e2e8f0;'>";
        echo htmlspecialchars($row['last_name']) . ", " . htmlspecialchars($row['first_name']);
        echo "</li>";
    }
    echo "</ul>";
} else {
    echo "No students assigned to this section yet.";
}
?>