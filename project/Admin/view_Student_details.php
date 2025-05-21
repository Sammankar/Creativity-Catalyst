<?php
include "connection.php";

if (isset($_GET['id'])) {
    $user_id = intval($_GET['id']);

    $query = "
        SELECT u.full_name, u.email, u.phone_number, c.name as college_name
        FROM users u
        LEFT JOIN colleges c ON u.college_id = c.college_id
        WHERE u.user_id = ? AND u.role = 5"; // Ensure it's an Student
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $Student = $result->fetch_assoc();
        echo json_encode(["success" => true, "Student" => $Student]);
    } else {
        echo json_encode(["success" => false]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(["success" => false]);
}
?>
