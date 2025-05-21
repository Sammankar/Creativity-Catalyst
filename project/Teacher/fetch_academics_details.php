<?php
include('connection.php'); // Include your database connection

if (isset($_GET['id'])) {
    $academicId = $_GET['id'];
    $query = "SELECT 
                ac.id, 
                ac.course_id, 
                c.name AS course_name, 
                ac.semester, 
                ac.academic_year, 
                ac.start_date, 
                ac.end_date, 
                ac.is_editable, 
                u.full_name AS created_by_name
              FROM academic_calendar ac
              LEFT JOIN courses c ON ac.course_id = c.course_id
              LEFT JOIN users u ON ac.created_by = u.user_id
              WHERE ac.id = ?";
    
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("i", $academicId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            echo json_encode(['success' => true, 'academic' => $row]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No record found']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Query preparation failed']);
    }
}
?>
