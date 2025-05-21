<?php
include "connection.php";

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$response = ['success' => false];

if ($id > 0) {
    $stmt = $conn->prepare("
        SELECT pss.*, c.name AS course_name, u.full_name AS created_by_name
        FROM project_submission_schedule pss
        LEFT JOIN courses c ON pss.course_id = c.course_id
        LEFT JOIN users u ON pss.created_by = u.user_id
        WHERE pss.id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $response['success'] = true;
        $response['schedule'] = $row;
    }
    $stmt->close();
}

echo json_encode($response);
