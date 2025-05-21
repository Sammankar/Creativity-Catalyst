<?php
include "connection.php";

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    $query = "SELECT ac.id, ac.semester, ac.academic_year, ac.start_date, ac.end_date, c.name AS course_name 
              FROM academic_calendar ac 
              LEFT JOIN courses c ON ac.course_id = c.course_id 
              WHERE ac.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode($row);
    } else {
        echo json_encode(['error' => 'Not found']);
    }
} else {
    echo json_encode(['error' => 'No ID provided']);
}
?>
