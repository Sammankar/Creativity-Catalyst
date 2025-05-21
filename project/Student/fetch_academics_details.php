<?php
require_once 'connection.php'; // your DB connection

header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    $query = $conn->prepare("
        SELECT ssr.*, 
               ac.semester, 
               ac.academic_year, 
               ac.start_date, 
               ac.end_date, 
               ac.is_editable,
               c.name AS course_name,
               ua.full_name AS created_by_name
        FROM student_semester_result ssr
        LEFT JOIN academic_calendar ac ON ssr.academic_calendar_id = ac.id
        LEFT JOIN courses c ON ssr.course_id = c.course_id
        LEFT JOIN users ua ON ac.created_by = ua.user_id
        WHERE ssr.id = ?
    ");
    $query->bind_param("i", $id);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        $academic = $result->fetch_assoc();
        echo json_encode(['success' => true, 'academic' => $academic]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
}
?>