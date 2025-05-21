<?php
require 'connection.php'; // Include your database connection

// Get the data from the request
$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['id']) && isset($data['status'])) {
    $id = intval($data['id']);
    $newStatus = intval($data['status']);

    // Update the course status in courses table
    $stmt = $conn->prepare("UPDATE courses SET course_status = ? WHERE course_id = ?");
    $stmt->bind_param("ii", $newStatus, $id);

    if ($stmt->execute()) {
        // If course is deactivated (0), freeze it for all colleges (set college_course_status = 2)
        // If course is activated (1), unfreeze it (set college_course_status = 1)
        $collegeStatus = ($newStatus === 0) ? 2 : 1;
        $updateCollegeCourse = $conn->prepare("UPDATE college_courses SET college_course_status = ? WHERE course_id = ?");
        $updateCollegeCourse->bind_param("ii", $collegeStatus, $id);
        $updateCollegeCourse->execute();
        $updateCollegeCourse->close();

        echo json_encode([
            "success" => true,
            "newStatus" => $newStatus,
            "collegeCourseStatus" => $collegeStatus
        ]);
    } else {
        echo json_encode(["success" => false]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(["success" => false]);
}
?>
