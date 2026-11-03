<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

$conn = new mysqli("localhost", "root", "", "obc_enrollment");

if ($conn->connect_error) {
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed"
    ]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Generate random student ID
    $student_id = rand(100000000, 999999999);

    // Student Information
    $last_name = $_POST['last_name'] ?? "";
    $first_name = $_POST['first_name'] ?? "";
    $middle_initial = $_POST['middle_initial'] ?? "";
    $suffix = $_POST['suffix'] ?? "";
    $age = intval($_POST['age'] ?? 0);
    $gender = $_POST['gender'] ?? "";
    $dob = $_POST['dob'] ?? "";
    $address = $_POST['address'] ?? "";
    $contact = $_POST['contact'] ?? "";
    $city = $_POST['city'] ?? "";

    // Enrollment Info
    $grade_level = $_POST['grade_level'] ?? "";
    $school_year = $_POST['school_year'] ?? "";
    $semester = $_POST['semester'] ?? "";
    $status = "Pending";

    // Documents
    $p_birth = $_POST['path_birth_cert'] ?? "";
    $p_137 = $_POST['path_form_137'] ?? "";
    $p_moral = $_POST['path_good_moral'] ?? "";
    $p_photo = $_POST['path_2x2_photo'] ?? "";

    // Guardian Info
    $guardian_name = $_POST['guardian_name'] ?? "";
    $guardian_dob = $_POST['guardian_dob'] ?? "";
    $guardian_contact = $_POST['guardian_contact'] ?? "";
    $guardian_address = $_POST['guardian_address'] ?? "";

    // Insert SQL
    $sql = "INSERT INTO enrollments (
        student_id, last_name, first_name, middle_initial, suffix, age, gender, dob,
        address, contact, city, grade_level, school_year, semester, status,
        path_birth_cert, path_form_137, path_good_moral, path_2x2_photo,
        guardian_name, guardian_dob, guardian_contact, guardian_address
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "issssisssssssssssssssss",
        $student_id,
        $last_name,
        $first_name,
        $middle_initial,
        $suffix,
        $age,
        $gender,
        $dob,
        $address,
        $contact,
        $city,
        $grade_level,
        $school_year,
        $semester,
        $status,
        $p_birth,
        $p_137,
        $p_moral,
        $p_photo,
        $guardian_name,
        $guardian_dob,
        $guardian_contact,
        $guardian_address
    );

    if ($stmt->execute()) {

        echo json_encode([
            "status" => "success",
            "student_id" => $student_id,
            "message" => "Enrollment submitted successfully"
        ]);

    } else {

        echo json_encode([
            "status" => "error",
            "message" => $stmt->error
        ]);

    }

    $stmt->close();
}

$conn->close();
?>