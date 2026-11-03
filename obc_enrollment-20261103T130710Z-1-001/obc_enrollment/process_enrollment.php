<?php
session_start();

if (!isset($_SESSION['registrar'])) {
    header("Location: index.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "obc_enrollment");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['id']) && isset($_GET['action'])) {
    $student_id = intval($_GET['id']);
    $action = trim($_GET['action']);
    $decline_reason = null;

    if ($action === 'accept') {
        $status = 'Active';

        $stmt = $conn->prepare("UPDATE enrollments SET status=?, decline_reason=NULL WHERE student_id=?");
        $stmt->bind_param("si", $status, $student_id);
        $stmt->execute();
        $stmt->close();

        $conn->close();
        header("Location: dashboard.php?status=active");
        exit();

    } elseif ($action === 'decline') {
        $status = 'Declined';

        // Get custom remarks from URL if provided
        if (isset($_GET['remarks']) && trim($_GET['remarks']) !== '') {
            $decline_reason = trim($_GET['remarks']);
        } else {
            $decline_reason = "Enrollment declined. Please resubmit your application with clear documents and correct information.";
        }

        $stmt = $conn->prepare("UPDATE enrollments SET status=?, decline_reason=? WHERE student_id=?");
        $stmt->bind_param("ssi", $status, $decline_reason, $student_id);
        $stmt->execute();
        $stmt->close();

        $conn->close();
        header("Location: dashboard.php?status=declined");
        exit();

    } else {
        $conn->close();
        header("Location: dashboard.php");
        exit();
    }
}

$conn->close();
header("Location: dashboard.php");
exit();
?>