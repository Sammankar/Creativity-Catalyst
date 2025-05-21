<?php
include 'connection.php';

$student_id = $_GET['student_id'] ?? 0;
$academic_year = $_GET['academic_year'] ?? '';
$semester = $_GET['semester'] ?? '';
$course = $_GET['course'] ?? 0;

// Fetch schedule
$schedule_stmt = $conn->prepare("SELECT * FROM project_submission_schedule WHERE academic_year = ? AND semester = ? AND course_id = ?");
$schedule_stmt->bind_param("ssi", $academic_year, $semester, $course);
$schedule_stmt->execute();
$schedule = $schedule_stmt->get_result()->fetch_assoc();
$schedule_stmt->close();

if (!$schedule) {
    die("Schedule not found.");
}

$schedule_id = $schedule['id'];

// Check if current date is after end_date + 1 day (00:00)
$end_date = new DateTime($schedule['end_date']);
$visible_date = $end_date->modify('+1 day')->setTime(0, 0);
$now = new DateTime();

if ($now < $visible_date) {
    die("Upload not allowed yet. You can upload after: " . $visible_date->format('Y-m-d H:i:s'));
}

// Fetch stage data
$stage_stmt = $conn->prepare("SELECT id, marks FROM project_submission_stages WHERE schedule_id = ?");
$stage_stmt->bind_param("i", $schedule_id);
$stage_stmt->execute();
$stages = $stage_stmt->get_result();
$stage_stmt->close();

$total_marks = 0;
$obtained_marks = 0;

while ($stage = $stages->fetch_assoc()) {
    $stage_id = $stage['id'];
    $stage_marks = $stage['marks'];

    $submission_stmt = $conn->prepare("SELECT guide_marks FROM project_stage_submissions WHERE student_user_id = ? AND stage_id = ?");
    $submission_stmt->bind_param("ii", $student_id, $stage_id);
    $submission_stmt->execute();
    $submission = $submission_stmt->get_result()->fetch_assoc();
    $submission_stmt->close();

    if ($submission && is_numeric($submission['guide_marks'])) {
        $total_marks += $stage_marks;
        $obtained_marks += (float)$submission['guide_marks'];
    }
}

if ($total_marks === 0) {
    die("No valid submissions to calculate marks.");
}

// Calculate 35% of total marks
$pass_threshold = $total_marks * 0.35;
$status = ($obtained_marks >= $pass_threshold) ? 1 : 2;

// Check if record already exists
$result_stmt = $conn->prepare("SELECT id FROM student_semester_result WHERE user_id = ? AND course_id = ? AND semester = ? AND academic_year = ?");
$result_stmt->bind_param("iiss", $student_id, $course, $semester, $academic_year);
$result_stmt->execute();
$result = $result_stmt->get_result()->fetch_assoc();
$result_stmt->close();

if ($result) {
    // Update if not already stored
    $update_stmt = $conn->prepare("UPDATE student_semester_result SET internal_total_marks = ?, internal_obtained_marks = ?, status = ?, updated_at = NOW() WHERE id = ?");
    $update_stmt->bind_param("ddii", $total_marks, $obtained_marks, $status, $result['id']);
    $update_stmt->execute();
    $update_stmt->close();
} else {
    // Insert new record
    $insert_stmt = $conn->prepare("INSERT INTO student_semester_result (user_id, course_id, college_id, semester, academic_calendar_id, academic_year, internal_total_marks, internal_obtained_marks, status, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $insert_stmt->bind_param("iiissdddi", 
        $student_id, 
        $course, 
        $schedule['college_id'], 
        $semester, 
        $schedule['academic_calendar_id'], 
        $academic_year, 
        $total_marks, 
        $obtained_marks, 
        $status
    );
    $insert_stmt->execute();
    $insert_stmt->close();
}

echo "Marks uploaded successfully.";
?>
