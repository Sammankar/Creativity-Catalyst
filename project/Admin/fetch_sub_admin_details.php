<?php
include "connection.php";

if (isset($_GET['id'])) {
    $subAdminId = intval($_GET['id']);

    $query = "
        SELECT 
            u.full_name AS sub_admin_name,
            u.email AS sub_admin_email,
            u.phone_number AS sub_admin_phone,
            u.role AS sub_admin_role,
            u.users_status AS sub_admin_status,
            c.name AS course_name
        FROM users u
        LEFT JOIN courses c ON u.course_id = c.course_id
        WHERE u.user_id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $subAdminId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $subAdmin = $result->fetch_assoc();
        echo json_encode(["success" => true, "sub_admin" => $subAdmin]);
    } else {
        echo json_encode(["success" => false]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(["success" => false]);
}
?>
