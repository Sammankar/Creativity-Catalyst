<?php
include 'connection.php';

$submission_id = $_GET['submission_id'] ?? 0;
$stage_id = $_GET['stage_id'] ?? 0;
$student_id = $_GET['student_id'];
$academic_year = $_GET['academic_year'];
$semester = $_GET['semester'];
$course = $_GET['course'];

if ($submission_id && $stage_id) {
    $sql = "UPDATE project_stage_submissions SET status = 1 WHERE id = ? AND stage_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $submission_id, $stage_id);
    $stmt->execute();
    $stmt->close();
}

header("Location: view_submissions.php?student_id=$student_id&academic_year=$academic_year&semester=$semester&course=$course");
exit;
?>
