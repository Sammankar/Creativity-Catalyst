<?php
include('connection.php');

$user_id = $_GET['user_id'];

$scheduleQuery = "SELECT * FROM project_submission_schedule 
    WHERE is_editable = 1 AND status = 1 LIMIT 1";
$scheduleResult = $conn->query($scheduleQuery);
$schedule = $scheduleResult->fetch_assoc();

$response = ['has_assignments' => false];

if ($schedule) {
    $schedule_id = $schedule['id'];

    $allocationQuery = "SELECT COUNT(*) as student_count FROM guide_allocations 
        WHERE guide_user_id = $user_id AND is_current = 1 AND academic_calendar_id = {$schedule['academic_calendar_id']}";
    $allocationResult = $conn->query($allocationQuery);
    $allocation = $allocationResult->fetch_assoc();

    if ($allocation['student_count'] > 0) {
        $response = [
            'has_assignments' => true,
            'academic_year' => $schedule['academic_year'],
            'semester' => $schedule['semester'],
            'start_date' => $schedule['start_date'],
            'end_date' => $schedule['end_date'],
            'student_count' => $allocation['student_count']
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>