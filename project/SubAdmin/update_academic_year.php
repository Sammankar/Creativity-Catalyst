<?php
include 'connection.php';

$user_id = $_POST['user_id'];
$calendar_id = $_POST['calendar_id']; // this is academic_calendar.id

// Step 1: Get academic year from academic_calendar
$calendarQuery = "SELECT academic_year FROM academic_calendar WHERE id = ?";
$stmt = $conn->prepare($calendarQuery);
$stmt->bind_param("i", $calendar_id);
$stmt->execute();
$calendarResult = $stmt->get_result()->fetch_assoc();

if (!$calendarResult) {
    echo json_encode(["success" => false, "message" => "Invalid academic calendar selected."]);
    exit;
}

$academic_year = $calendarResult['academic_year'];

// Step 2: Get course_id and current_semester of student
$studentQuery = "SELECT course_id, current_semester FROM users WHERE user_id = ?";
$stmt = $conn->prepare($studentQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

$course_id = $student['course_id'];
$semester = $student['current_semester'];

// Step 3: Update student_academics with academic year + calendar ID
$updateAcademic = "UPDATE student_academics 
                   SET current_academic_year = ?, academic_calendar_id = ?, needs_manual_assignment = 0 
                   WHERE user_id = ?";
$stmt1 = $conn->prepare($updateAcademic);
$stmt1->bind_param("sii", $academic_year, $calendar_id, $user_id);
$stmt1->execute();

// Step 4: Update student_semester_result with academic year + calendar ID
$updateResult = "UPDATE student_semester_result 
                 SET academic_year = ?, academic_calendar_id = ?, needs_manual_assignment = 0 
                 WHERE user_id = ? AND course_id = ? AND semester = ?";
$stmt2 = $conn->prepare($updateResult);
$stmt2->bind_param("siiii", $academic_year, $calendar_id, $user_id, $course_id, $semester);
$stmt2->execute();

echo json_encode(["success" => true, "message" => "Academic year assigned successfully."]);
?>
