<?php
include "connection.php";

if (isset($_GET['id'])) {
    $courseId = intval($_GET['id']);

    $query = "
        SELECT c.name, c.total_semesters, c.duration, c.created_at, u.full_name, u.email 
        FROM courses c
        JOIN users u ON c.created_by = u.user_id
        WHERE c.course_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $courseId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $course = $result->fetch_assoc();
        echo json_encode(["success" => true, "course" => $course]);
    } else {
        echo json_encode(["success" => false]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(["success" => false]);
}
?>
