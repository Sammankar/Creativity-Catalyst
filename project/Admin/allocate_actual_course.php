<?php
// allocate_actual_course.php

include 'connection.php'; // Ensure your DB connection is included
include 'mail_sendcourseallocation.php'; // Include the mail sending script

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $college_course_id = $_POST['college_course_id'];
    $course_id = $_POST['course_id'];
    $sub_admin_id = $_POST['sub_admin_id'];
    $subject = $_POST['subject'];
    $body = $_POST['body'];
    $attachment = null;

    // Handle file upload (if any)
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
        $uploadDir = 'images/sub_admin_course_allocation_reports/';  // Ensure this folder is writable
        $fileTmpPath = $_FILES['attachment']['tmp_name'];
        $fileName = time() . '_' . $_FILES['attachment']['name'];
        $filePath = $uploadDir . $fileName;
        $fileSize = $_FILES['attachment']['size'];
        $fileType = $_FILES['attachment']['type'];

        // File validation: Max size 15MB, allow only PNG, JPEG, PDF
        $maxFileSize = 15 * 1024 * 1024; // 15MB in bytes
        $allowedTypes = ['image/png', 'image/jpeg', 'application/pdf'];

        if ($fileSize > $maxFileSize) {
            echo "Error: File size exceeds the 15MB limit.";
            exit;
        }

        if (!in_array($fileType, $allowedTypes)) {
            echo "Error: Invalid file type. Only PNG, JPEG, and PDF files are allowed.";
            exit;
        }

        // Move the uploaded file to the designated directory
        if (move_uploaded_file($fileTmpPath, $filePath)) {
            $attachment = $fileName;
        } else {
            echo "Error: There was an issue uploading the file.";
            exit;
        }
    }

    // Insert into course_allocations table with default allocation_status as 1 (Active)
    $stmt = $conn->prepare("INSERT INTO course_allocations (user_id, college_course_id, allocation_status, allocated_at, subject, body, attachment) 
                          VALUES (?, ?, 1, NOW(), ?, ?, ?)");
    $stmt->bind_param("iisss", $sub_admin_id, $college_course_id, $subject, $body, $attachment);
    $stmt->execute();

    if ($stmt->error) {
        echo "Error: " . $stmt->error;
        exit;
    }

    // Update the college_course table with the selected sub-admin
    $stmt = $conn->prepare("UPDATE college_courses SET sub_admin_id = ? WHERE college_course_id = ?");
    $stmt->bind_param("ii", $sub_admin_id, $college_course_id);
    $stmt->execute();

    if ($stmt->error) {
        echo "Error: " . $stmt->error;
        exit;
    }

    // **New query to update course_id in users table**
    $stmt = $conn->prepare("UPDATE users SET course_id = ? WHERE user_id = ?");
    $stmt->bind_param("ii", $course_id, $sub_admin_id);
    $stmt->execute();

    if ($stmt->error) {
        echo "Error: " . $stmt->error;
        exit;
    }

    // Send email notification after allocation
    $emailStatus = sendCourseAllocationEmail($sub_admin_id, $course_id, $subject, $body, $attachment);

    if ($emailStatus === "Course allocated and email sent.") {
        echo 'success';
    } else {
        echo "Course allocated, but email failed to send: " . $emailStatus;
    }
}
?>
