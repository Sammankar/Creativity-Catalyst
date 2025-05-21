<?php
include 'connection.php';

$course_id = $_GET['course_id'];
$semester = $_GET['semester'];

$sql = "SELECT id, academic_year, start_date, end_date 
        FROM academic_calendar 
        WHERE course_id = ? AND semester = ? AND is_editable = 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $course_id, $semester);
$stmt->execute();
$result = $stmt->get_result();

$years = [];
while ($row = $result->fetch_assoc()) {
    $years[] = $row;
}
echo json_encode($years);
?>
