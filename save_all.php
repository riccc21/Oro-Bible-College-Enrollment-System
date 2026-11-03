<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

$conn = new mysqli("localhost", "root", "", "obc_enrollment");

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Database connection failed"]));
}

function uploadFile($fileKey, $studentId) {
    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] != 0) {
        return ""; 
    }
    $targetDir = "uploads/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    $fileName = basename($_FILES[$fileKey]["name"]);
    $newFileName = $studentId . "_" . $fileKey . "_" . $fileName;
    $targetFilePath = $targetDir . $newFileName;
    if (move_uploaded_file($_FILES[$fileKey]["tmp_name"], $targetFilePath)) {
        return $newFileName; 
    }
    return "";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $last_name = $_POST['last_name'] ?? "";
    $first_name = $_POST['first_name'] ?? "";
    $input_id = $_POST['student_id'] ?? "";

    // 1. DUPLICATION CHECK (Only for NEW students)
    if (empty($input_id)) {
        $checkQuery = "SELECT student_id FROM enrollments 
                       WHERE TRIM(first_name) = TRIM(?) 
                       AND TRIM(last_name) = TRIM(?) 
                       AND status != 'Rejected' LIMIT 1";
        
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("ss", $first_name, $last_name);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            echo json_encode([
                "status" => "error", 
                "message" => "Student is Already Enrolled."
            ]);
            $checkStmt->close();
            $conn->close();
            exit; 
        }
        $checkStmt->close();
        $student_id = rand(1000000000, 9999999999);
        $student_type = "NEW";
    } else {
        $student_id = $input_id;
        $student_type = "RETURNING";
    }

    // Process files
    $path_birth = uploadFile('path_birth_cert', $student_id);
    $path_137 = uploadFile('path_form_137', $student_id);
    $path_moral = uploadFile('path_good_moral', $student_id);
    $path_photo = uploadFile('path_2x2_photo', $student_id);

    // Collect all data
    $email = $_POST['email'] ?? "";
    $mi = $_POST['middle_initial'] ?? "";
    $suffix = $_POST['suffix'] ?? "";
    $age = $_POST['age'] ?? 0;
    $gender = $_POST['gender'] ?? "";
    $dob = $_POST['dob'] ?? "";
    $address = $_POST['address'] ?? "";
    $contact = $_POST['contact'] ?? "";
    $city = $_POST['city'] ?? "";
    $grade_level = $_POST['grade_level'] ?? "";
    $school_year = $_POST['school_year'] ?? "";
    $semester = $_POST['semester'] ?? "";
    $g_name = $_POST['guardian_name'] ?? "";
    $g_dob = $_POST['guardian_dob'] ?? "";
    $g_contact = $_POST['guardian_contact'] ?? "";
    $g_address = $_POST['guardian_address'] ?? "";
    $status = "Pending"; // Always reset to pending for review
    $docs_submitted = (!empty($path_birth) || !empty($path_137) || !empty($path_moral) || !empty($path_photo)) ? "Yes" : "No";

    // 2. THE UPSERT SQL
    // We try to INSERT. If student_id exists, it UPDATES only specific fields.
    // Notice first_name and last_name are NOT in the UPDATE part.
    $sql = "INSERT INTO enrollments (
        student_id, email, last_name, first_name, middle_initial, suffix, age, gender, dob, 
        address, contact, city, grade_level, school_year, semester, path_birth_cert, 
        path_form_137, path_good_moral, path_2x2_photo, guardian_name, guardian_dob, 
        guardian_contact, guardian_address, status, student_type, docs_submitted
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ON DUPLICATE KEY UPDATE 
        age = VALUES(age),
        address = VALUES(address),
        contact = VALUES(contact),
        city = VALUES(city),
        grade_level = VALUES(grade_level),
        school_year = VALUES(school_year),
        semester = VALUES(semester),
        path_birth_cert = VALUES(path_birth_cert),
        path_form_137 = VALUES(path_form_137),
        path_good_moral = VALUES(path_good_moral),
        path_2x2_photo = VALUES(path_2x2_photo),
        guardian_name = VALUES(guardian_name),
        guardian_contact = VALUES(guardian_contact),
        guardian_address = VALUES(guardian_address),
        status = 'Pending',
        student_type = 'RETURNING'";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssssssssssssssssssssssss", 
        $student_id, $email, $last_name, $first_name, $mi, $suffix, $age, $gender, $dob, 
        $address, $contact, $city, $grade_level, $school_year, $semester, $path_birth, 
        $path_137, $path_moral, $path_photo, $g_name, $g_dob, $g_contact, $g_address, 
        $status, $student_type, $docs_submitted
    );

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "student_id" => $student_id, "type" => $student_type]);
    } else {
        echo json_encode(["status" => "error", "message" => $stmt->error]);
    }
    $stmt->close();
}
$conn->close();
?>