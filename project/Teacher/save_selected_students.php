<?php
include "connection.php";
session_start();

$project_head_id = $_SESSION['user_id'] ?? null;
$competition_id = $_POST['competition_id'] ?? null;
$selected_students = $_POST['selected_students'] ?? [];

if (!$project_head_id || !$competition_id) {
    die("Invalid access.");
}

// Get college_id
$head_query = "SELECT college_id FROM users WHERE user_id = $project_head_id";
$head_result = $conn->query($head_query);
$college_id = $head_result->fetch_assoc()['college_id'] ?? null;

if (!$college_id) {
    die("College info missing.");
}

// Prepare insert statement for competition participants
$stmt = $conn->prepare("INSERT INTO competition_participants 
                        (competition_id, college_id, student_user_id, course_id, current_semester, is_verified_by_project_head, verified_at, submission_status, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");

foreach ($selected_students as $student_id) {
    // Get student data (course_id, current_semester, etc.)
    $student_query = "SELECT course_id, current_semester FROM student_academics WHERE user_id = $student_id";
    $student_result = $conn->query($student_query);
    
    if ($student_result->num_rows > 0) {
        $student_data = $student_result->fetch_assoc();
        $course_id = $student_data['course_id'];
        $current_semester = $student_data['current_semester'];
    } else {
        continue; // Skip this student if no data found (shouldn't happen, but safety measure)
    }

    // Set other data (for simplicity, we're assuming the status and verification fields are fixed for now)
    $is_verified_by_project_head = 0; // Set to 0 by default (you can update this as needed)
    $verified_at = null;  // Leave null for now, assuming this field gets updated later
    $submission_status = 'Pending';  // Default status, you can adjust as needed
    
    // Bind parameters and execute insertion
    $stmt->bind_param("iiiiiiss", $competition_id, $college_id, $student_id, $course_id, $current_semester, $is_verified_by_project_head, $verified_at, $submission_status);
    $stmt->execute();
}

// Redirect to the 'view_selected_students.php' with success message
$_SESSION['message'] = "Selected students successfully added.";
$_SESSION['message_type'] = "success";
header("Location: view_selected_students.php?competition_id=$competition_id");
exit;
?>
