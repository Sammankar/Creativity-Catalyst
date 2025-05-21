<?php
require_once 'connection.php';
session_start();

$guide_id = $_GET['guide_id'];
$user_id = $_SESSION['user_id'];

$uQuery = "SELECT course_id FROM users WHERE user_id = ?";
$stmt = $conn->prepare($uQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$course_id = $stmt->get_result()->fetch_assoc()['course_id'];

$today = date('Y-m-d');
$query = "
    SELECT id, semester, academic_year 
    FROM academic_calendar 
    WHERE course_id = ? 
      AND status = 1 
      AND is_editable = 1 
      AND start_date <= ? 
      AND end_date >= ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("iss", $course_id, $today, $today);
$stmt->execute();
$result = $stmt->get_result();

$html = "<div class='mb-4 p-3 border rounded-md bg-white dark:bg-gray-800 shadow-sm'>
    <label class='block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2'>Already Assigned Students to this Guide:</label>
    <ul class='list-disc pl-5 text-sm text-gray-700 dark:text-gray-200 space-y-1'>";

while ($cal = $result->fetch_assoc()) {
    $calendar_id = $cal['id'];
    $semester = $cal['semester'];
    $academic_year = $cal['academic_year'];

    $countQuery = "
        SELECT COUNT(*) AS student_count 
        FROM guide_allocations 
        WHERE guide_user_id = ? 
          AND academic_calendar_id = ? 
          AND is_current = 1
    ";
    $stmt2 = $conn->prepare($countQuery);
    $stmt2->bind_param("ii", $guide_id, $calendar_id);
    $stmt2->execute();
    $count = $stmt2->get_result()->fetch_assoc()['student_count'];

    $html .= "<li>Semester $semester ($academic_year): $count students</li>";
}
$html .= "</ul></div>";
echo $html;
?>
