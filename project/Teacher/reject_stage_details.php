<?php
include 'connection.php';

$submission_id = $_GET['submission_id'] ?? 0;
$stage_id = $_GET['stage_id'] ?? 0;
$student_id = $_GET['student_id'];
$academic_year = $_GET['academic_year'];
$semester = $_GET['semester'];
$course = $_GET['course'];

if ($submission_id && $stage_id) {
    // Fetch submitted_files
    $stmt = $conn->prepare("SELECT submitted_files FROM project_stage_submissions WHERE id = ? AND stage_id = ?");
    $stmt->bind_param("ii", $submission_id, $stage_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();

    if ($data && $data['submitted_files']) {
        $files = explode(",", $data['submitted_files']);
        foreach ($files as $file) {
            $file_path = '../Student/images/stages/' . $file;
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }

        // Nullify submitted_files and set status = 2
        $update = $conn->prepare("UPDATE project_stage_submissions SET submitted_files = NULL, status = 2 WHERE id = ? AND stage_id = ?");
        $update->bind_param("ii", $submission_id, $stage_id);
        $update->execute();
        $update->close();
    }
}

header("Location: view_submissions.php?student_id=$student_id&academic_year=$academic_year&semester=$semester&course=$course");
exit;
?>
