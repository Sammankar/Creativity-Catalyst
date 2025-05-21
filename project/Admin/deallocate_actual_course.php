<?php
// deallocate_actual_course.php

include 'connection.php';
session_start();

// Get admin details
$stmt = $conn->prepare("SELECT full_name FROM users WHERE user_id = ?");
$stmt->bind_param("i", $admin_id);  // Bind the admin's user_id
$stmt->execute();
$result = $stmt->get_result();

if ($admin = $result->fetch_assoc()) {
    $admin_name = $admin['full_name'];  // Admin's full name
} else {
    // Handle the case where the admin is not found
    $admin_name = "Admin Name Not Found";
}

$stmt->close();

// Get the deallocation details
$course_id = $_POST['course_id'];
$college_course_id = $_POST['college_course_id'];
$course_name = $_POST['course_name'];
$sub_admin_id = $_POST['user_id'];
$subject = $_POST['subject'];
$body = $_POST['body'];

// Fetch Sub-Admin details
$stmt = $conn->prepare("SELECT full_name, email FROM users WHERE user_id = ?");
$stmt->bind_param("i", $sub_admin_id);
$stmt->execute();
$result = $stmt->get_result();
$sub_admin = $result->fetch_assoc();

// Save the deallocation record in the course_allocations table
$stmt = $conn->prepare("INSERT INTO course_allocations (user_id, college_course_id, allocation_status, subject, body, deallocated_at)
                        VALUES (?, ?, 0, ?, ?, NOW())");
$stmt->bind_param("iiss", $sub_admin_id, $college_course_id, $subject, $body);
if (!$stmt->execute()) {
    die('Error: ' . $stmt->error);  // Output error if the execution fails
}

// Update the college_courses table to set sub_admin_id to NULL
$stmt = $conn->prepare("UPDATE college_courses SET sub_admin_id = NULL WHERE college_course_id = ?");
$stmt->bind_param("i", $college_course_id);
if (!$stmt->execute()) {
    die('Error: ' . $stmt->error);  // Output error if the execution fails
}

// Additionally, update the users table to remove the course_id associated with the sub-admin
$stmt = $conn->prepare("UPDATE users SET course_id = NULL WHERE user_id = ?");
$stmt->bind_param("i", $sub_admin_id);
if (!$stmt->execute()) {
    die('Error: ' . $stmt->error);  // Output error if the execution fails
}

// Send deallocation email with names
include 'mail_sendcoursedeallocation.php';
sendCourseDeallocationEmail(
    $course_name, 
    $sub_admin['full_name'],  // Sub-admin name
    $sub_admin['email'],      // Sub-admin email
    $subject, 
    $body
);

// Return success response
echo json_encode(['status' => 'success']);
?>
