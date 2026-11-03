<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

$conn = new mysqli("localhost", "root", "", "obc_enrollment");

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Generate a 9-digit Student ID (within the 6-12 digit requirement)
    $student_id = rand(100000000, 999999999);

    // Collect all data from Flutter POST request
    $data = $_POST;
    
    // Prepare the unified SQL statement
    $sql = "INSERT INTO enrollments (
                student_id, last_name, first_name, middle_initial, suffix, age, gender, dob, address, contact, city,
                grade_level, school_year, semester, 
                guardian_name, guardian_dob, guardian_contact, guardian_address
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    
    // Bind all 18 parameters
    $stmt->bind_param("issssissssssssssss", 
        $student_id, $data['last_name'], $data['first_name'], $data['middle_initial'], 
        $data['suffix'], $data['age'], $data['gender'], $data['dob'], 
        $data['address'], $data['contact'], $data['city'], 
        $data['grade_level'], $data['school_year'], $data['semester'],
        $data['guardian_name'], $data['guardian_dob'], $data['guardian_contact'], $data['guardian_address']
    );

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Enrollment complete", "student_id" => $student_id]);
    } else {
        echo json_encode(["status" => "error", "message" => $stmt->error]);
    }
    $stmt->close();
}
$conn->close();
?>