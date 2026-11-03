<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "obc_enrollment");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ensure we are getting the student_id
    $student_id = isset($_POST['student_id']) ? $_POST['student_id'] : "";

    if (!empty($student_id)) {
        // We use a prepared statement to set section_id to NULL
        // Note: Make sure your DB column 'section_id' allows NULL values
        $sql = "UPDATE enrollments SET section_id = NULL WHERE student_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $student_id);

        if ($stmt->execute()) {
            // Success! Even if 0 rows were changed (already NULL), it's a success
            echo "success";
        } else {
            echo "SQL Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Error: No Student ID received by PHP.";
    }
}
$conn->close();
?>