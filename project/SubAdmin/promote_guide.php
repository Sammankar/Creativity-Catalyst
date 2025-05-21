<?php
include('connection.php');

// Read JSON data from the request
$data = json_decode(file_get_contents('php://input'), true);
$guide_id = $data['guide_id'];
$course_name = $data['course_name'];
$college_id = $data['college_id'];

// Get course_id from course_name
$courseQuery = "SELECT course_id FROM courses WHERE name = '$course_name'";
$courseResult = $conn->query($courseQuery);
$courseRow = $courseResult->fetch_assoc();
$course_id = $courseRow['course_id'] ?? 0;

// Check current status
$query = "SELECT project_head FROM users WHERE user_id = $guide_id";
$result = $conn->query($query);
$row = $result->fetch_assoc();

if ($row['project_head'] == 1) {
    // Demote the current guide
    $updateQuery = "UPDATE users SET project_head = 0 WHERE user_id = $guide_id";
    if ($conn->query($updateQuery)) {
        echo json_encode(['success' => true, 'action' => 'demoted']);
    } else {
        echo json_encode(['success' => false]);
    }
} else {
    // Demote existing project head for same course and college
    $conn->query("UPDATE users SET project_head = 0 WHERE college_id = $college_id AND course_id = $course_id AND project_head = 1");

    // Promote the selected guide
    $updateQuery = "UPDATE users SET project_head = 1 WHERE user_id = $guide_id";
    if ($conn->query($updateQuery)) {
        echo json_encode(['success' => true, 'action' => 'promoted']);
    } else {
        echo json_encode(['success' => false]);
    }
}
?>
