<?php
session_start();
include 'connection.php'; // DB connection

// Include the mail function file
include 'sendCourseRequestStatusEmail.php';  // Adjust the path where your sendCourseRequestStatusEmail function is located

if (!isset($_SESSION['user_id'])) {
    echo "unauthorized";
    exit();
}

if (isset($_POST['request_id']) && isset($_POST['status'])) {
    $request_id = $_POST['request_id'];
    $status = $_POST['status'];

    // Fetch necessary data for insertion
    $query = "SELECT college_id, requested_course_id FROM course_requests WHERE request_id = '$request_id'";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);

    if ($row) {
        $college_id = $row['college_id'];
        $course_ids = explode(", ", $row['requested_course_id']); // Handle multiple courses

        // Update status in course_requests
        $updateQuery = "UPDATE course_requests SET status = '$status', status_updated_at = NOW() WHERE request_id = '$request_id'";
        if (mysqli_query($conn, $updateQuery)) {
            if ($status == 1) { // Only insert if Approved
                foreach ($course_ids as $course_id) {
                    // Check if course already exists for college (Avoid duplicate entries)
                    $checkQuery = "SELECT * FROM college_courses WHERE college_id = '$college_id' AND course_id = '$course_id'";
                    $checkResult = mysqli_query($conn, $checkQuery);
                    if (mysqli_num_rows($checkResult) == 0) {
                        // Insert new course for the college
                        $insertQuery = "INSERT INTO college_courses (college_id, course_id, college_course_status) 
                                        VALUES ('$college_id', '$course_id', '1')";
                        mysqli_query($conn, $insertQuery);
                    }
                }
            }

            // Fetch the college admin's email for the notification
            $queryAdminEmail = "SELECT u.email, c.name AS college_name, GROUP_CONCAT(co.name SEPARATOR ', ') AS requested_courses 
                                FROM course_requests cr
                                LEFT JOIN users u ON cr.admin_id = u.user_id
                                LEFT JOIN colleges c ON cr.college_id = c.college_id
                                LEFT JOIN courses co ON cr.requested_course_id = co.course_id
                                WHERE cr.request_id = '$request_id'
                                GROUP BY cr.request_id";
            $resultAdminEmail = mysqli_query($conn, $queryAdminEmail);
            $adminData = mysqli_fetch_assoc($resultAdminEmail);
            
            $college_admin_email = $adminData['email'];
            $requested_courses = $adminData['requested_courses'];

            // Send email to the college admin with the status
            if (sendCourseRequestStatusEmail($college_admin_email, $requested_courses, $status == 1 ? 'approved' : 'rejected')) {
                echo "success";
            } else {
                echo "status_updated_but_email_failed";
            }
        } else {
            echo "error";
        }
    } else {
        echo "not_found";
    }
} else {
    echo "invalid";
}
?>
