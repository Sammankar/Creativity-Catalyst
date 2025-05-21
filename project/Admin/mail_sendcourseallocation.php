<?php
// mail_sendcourseallocation.php
// mail_sendcourseallocation.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function sendCourseAllocationEmail($sub_admin_id, $course_id, $subject, $body, $attachment) {
    
    // Get the email of the sub-admin from the database
    session_start();

// Check if the session variable 'user_id' is set
if (isset($_SESSION['user_id'])) {
    $admin_id = $_SESSION['user_id'];  // Get the admin's user_id from session
    
    // Get the admin's full name from the 'users' table
    include 'connection.php';  // Include your DB connection
    
    // Prepare the query to fetch the admin's full name based on user_id
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
} else {
    // Handle the case where the session is not set or the user is not logged in
    echo "Session not set. User not logged in.";
    exit();
}

    // Get sub-admin details
    $stmt = $conn->prepare("SELECT email, full_name FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $sub_admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if (!$user) {
        return "Sub-admin not found.";
    }
    
    $email = $user['email'];
    $full_name = $user['full_name'];
    
    // Step 2: Fetch the course_id for the given sub_admin_id from the college_courses table
    $stmt = $conn->prepare("SELECT cc.course_id FROM college_courses cc 
                            WHERE cc.sub_admin_id = ?");
    $stmt->bind_param("i", $sub_admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $course_id = $row['course_id'];  // The course_id linked to the sub-admin
    } else {
        return "No course found for this sub-admin.";
    }
    
    // Step 3: Fetch the course_name from the courses table using the course_id
    $stmt = $conn->prepare("SELECT name FROM courses WHERE course_id = ?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $course_name = $row['name'];  // The course_name for the given course_id
    } else {
        return "Course not found for the given course_id.";
    }
    
    $mail = new PHPMailer(true);
    $uploadDir = __DIR__ . '/images/sub_admin_course_allocation_reports/';  // Ensure your uploads folder exists and is writable

    try {
        // Set up SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.sendgrid.net'; // Set your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'apikey';  // Replace with your SendGrid API key
        $mail->Password = ''; // Replace with your SendGrid API key
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('enter-email-id', 'Course Allocation System');
        $mail->addAddress($email);

        // Attach file if present
        if ($attachment) {
            $filePath = $uploadDir . $attachment;
            if (file_exists($filePath)) {
                $mail->addAttachment($filePath);
            }
        }

        // Compose the email body
        $emailSubject = "Course Allocation Notification - $course_name";  // Updated subject with course name
        $emailBody = "<html><body>"
            . "<h3>Course Allocation Notification</h3>"
            . "<p>Hello $full_name,</p>"
            . "<p>You have been allocated a new course: <strong>$course_name</strong> with the following details:</p>"
            . "<p><strong>Subject:</strong> $subject</p>"
            . "<p><strong>Body:</strong> $body</p>"
            . "<p><strong>Allocated By Your College Admin:</strong> $admin_name</p>"
            . "<p><strong>Allocated At:</strong> " . date('Y-m-d H:i:s') . "</p>"
            . "<p>Regards,<br>Course Allocation Team</p>"
            . "</body></html>";

        // Send the email
        $mail->isHTML(true);
        $mail->Subject = $emailSubject;
        $mail->Body    = $emailBody;

        $mail->send();

        return "Course allocated and email sent.";

    } catch (Exception $e) {
        return "Error: " . $mail->ErrorInfo;
    }
}

?>
