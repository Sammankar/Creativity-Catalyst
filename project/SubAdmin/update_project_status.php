<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

require 'connection.php';
require 'sendProjectStatusEmail.php';

$data = json_decode(file_get_contents('php://input'), true);
$project_id = $data['id'] ?? null;
$status = $data['status'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

if (!$project_id || !isset($status) || !$user_id) {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
    exit;
}

// Update project_submission_schedule status
$stmt = $conn->prepare("UPDATE project_submission_schedule SET status = ?, updated_at = NOW() WHERE id = ?");
$stmt->bind_param("ii", $status, $project_id);
$stmt->execute();
$stmt->close();

// Fetch schedule info
$schedule = $conn->query("SELECT * FROM project_submission_schedule WHERE id = $project_id")->fetch_assoc();

if (!$schedule) {
    echo json_encode(['success' => false, 'error' => 'Schedule not found']);
    exit;
}

if ($status == 1) {
    // On activation
    $course_id = $schedule['course_id'];
    $semester = $schedule['semester'];
    $academic_year = $schedule['academic_year'];
    $start = $schedule['start_date'];
    $end = $schedule['end_date'];
    $college_id = $schedule['college_id'];

    // Fetch students with matching criteria
    $students = $conn->query("SELECT user_id FROM student_academics WHERE 
        course_id = $course_id AND 
        current_semester = $semester AND 
        current_academic_year = '$academic_year' AND 
        college_id = $college_id");

    while ($row = $students->fetch_assoc()) {
        $uid = $row['user_id'];

        // Update student_academics
        $conn->query("UPDATE student_academics SET 
            academic_project_schedule_id = $project_id,
            academic_project_schedule_start_date = '$start',
            academic_project_schedule_end_date = '$end',
            updated_at = NOW()
            WHERE user_id = $uid");

        // Update student_semester_result
        $conn->query("UPDATE student_semester_result SET 
            academic_project_schedule_id = $project_id,
            academic_project_schedule_start_date = '$start',
            academic_project_schedule_end_date = '$end',
            updated_at = NOW()
            WHERE user_id = $uid AND 
                course_id = $course_id AND 
                semester = $semester AND 
                academic_year = '$academic_year'");
    }

    // Send mail
    sendProjectStatusEmail($schedule);
}

echo json_encode(['success' => true, 'newStatus' => $status]);
?>
