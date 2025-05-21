<?php
include('connection.php');

$id = $_GET['id'] ?? 0;

$stmt = $conn->prepare("SELECT u.full_name, u.email, c.name AS course_name, sa.current_semester AS semester, sa.current_academic_year AS academic_year, gu.full_name AS guide_name FROM users u LEFT JOIN student_academics sa ON u.user_id = sa.user_id LEFT JOIN courses c ON u.course_id = c.course_id LEFT JOIN guide_allocations g ON u.user_id = g.student_user_id AND g.is_current = 1 LEFT JOIN users gu ON g.guide_user_id = gu.user_id WHERE u.user_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(['success' => true, 'Student' => $row]);
} else {
    echo json_encode(['success' => false]);
}
?>
