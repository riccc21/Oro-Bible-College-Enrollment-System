<?php
header('Content-Type: application/json');

$email = $_POST['email'] ?? '';

if (empty($email)) {
    echo json_encode(['status' => 'error', 'message' => 'Email required']);
    exit();
}

$conn = new mysqli("localhost", "root", "", "obc_enrollment");

if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit();
}

// UPDATED: Selecting 'decline_reason' as well as 'status'
$stmt = $conn->prepare("SELECT status, decline_reason FROM enrollments WHERE email=? ORDER BY id DESC LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $row = $result->fetch_assoc()) {
    $status = $row['status'];
    
    if ($status == 'Declined') {
        echo json_encode([
            'status' => 'Declined',
            // Fetches the dynamic reason from your database column
            'reason' => $row['decline_reason'] ?? 'Your enrollment request has been declined due to unclear/wrong information.'
        ]);
    } else {
        echo json_encode(['status' => $status]);
    }
} else {
    echo json_encode(['status' => 'No enrollment found']);
}

$stmt->close();
$conn->close();
?>