<?php
session_start(); // ← YOU NEED THIS at the top to use $_SESSION
include "connection.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['competition_id'])) {
    $_SESSION['message'] = 'Invalid request';
    $_SESSION['message_type'] = 'error';
    header("Location: competition_list.php");
    exit;
}

$competition_id = (int) $_POST['competition_id'];

// Get current status
$stmt = $conn->prepare("SELECT competition_status FROM competitions WHERE competition_id = ?");
$stmt->bind_param("i", $competition_id);
$stmt->execute();
$result = $stmt->get_result();
$competition = $result->fetch_assoc();
$stmt->close();

if (!$competition) {
    $_SESSION['message'] = 'Competition not found';
    $_SESSION['message_type'] = 'error';
    header("Location: competition_list.php");
    exit;
}

$status = $competition['competition_status'];

// Always updatable fields
$name = trim($_POST['name']);
$description = trim($_POST['description']);
$rules = trim($_POST['rules']);
$recommended = trim($_POST['recommended_submissions'] ?? '');

if ($status == 0) {
    // Editable only if competition_status is 0
    $college_reg_start = $_POST['college_registration_start_date'];
    $college_reg_end = $_POST['college_registration_end_date'];
    $student_sub_start = $_POST['student_submission_start_date'];
    $student_sub_end = $_POST['student_submission_end_date'];
    $evaluation_start = $_POST['evaluation_start_date'];
    $evaluation_end = $_POST['evaluation_end_date'];
    $result_date = $_POST['result_declaration_date'];

    $stmt = $conn->prepare("UPDATE competitions SET 
        name = ?, 
        description = ?, 
        rules = ?, 
        recommended_submissions = ?, 
        college_registration_start_date = ?, 
        college_registration_end_date = ?, 
        student_submission_start_date = ?, 
        student_submission_end_date = ?, 
        evaluation_start_date = ?, 
        evaluation_end_date = ?, 
        result_declaration_date = ?, 
        updated_at = NOW()
        WHERE competition_id = ?");

    $stmt->bind_param(
        "sssssssssssi",
        $name,
        $description,
        $rules,
        $recommended,
        $college_reg_start,
        $college_reg_end,
        $student_sub_start,
        $student_sub_end,
        $evaluation_start,
        $evaluation_end,
        $result_date,
        $competition_id
    );

} else {
    // Only allow basic fields if status is NOT 0
    $stmt = $conn->prepare("UPDATE competitions SET 
        name = ?, 
        description = ?, 
        rules = ?, 
        recommended_submissions = ?, 
        updated_at = NOW()
        WHERE competition_id = ?");

    $stmt->bind_param(
        "ssssi",
        $name,
        $description,
        $rules,
        $recommended,
        $competition_id
    );
}

if ($stmt->execute()) {
    // ✅ SUCCESS: store session message and redirect
    $_SESSION['message'] = "Competition updated successfully.";
    $_SESSION['message_type'] = "success";
    header("Location: competition_list.php");
    exit;
} else {
    // ❌ ERROR: stay on same page with alert
    $errorMessage = "Update failed: " . htmlspecialchars($stmt->error);
    echo "<script>alert('$errorMessage'); window.location.href='edit_competition_details.php?id=$competition_id';</script>";
    exit;
}

$stmt->close();
$conn->close();
?>
