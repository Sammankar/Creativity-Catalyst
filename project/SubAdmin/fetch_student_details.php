<?php
include "connection.php";

if (isset($_GET['id'])) {
    $student_id = intval($_GET['id']);

    // Fetch student details along with their course information
    $query = "
        SELECT u.full_name, u.email, u.current_semester, c.name as course_name, c.course_id
        FROM users u
        LEFT JOIN courses c ON u.course_id = c.course_id
        WHERE u.user_id = ? AND u.role = 5";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
        $course_id = $student['course_id'];

        // Fetch total semesters for this course
        $semesterQuery = "SELECT total_semesters FROM courses WHERE course_id = ?";
        $stmt2 = $conn->prepare($semesterQuery);
        $stmt2->bind_param("i", $course_id);
        $stmt2->execute();
        $semesterResult = $stmt2->get_result();
        $semesters = $semesterResult->fetch_assoc();

        // Return student data along with total semesters
        echo json_encode([
            "success" => true, 
            "student" => $student, 
            "total_semesters" => [$semesters]
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
