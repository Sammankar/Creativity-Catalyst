<?php
include "connection.php";

if (isset($_GET['id'])) {
    $evaluatorId = intval($_GET['id']);

    $query = "
        SELECT u.full_name, u.email, u.phone_number
        FROM users u
        WHERE u.user_id = ? AND u.role = 6"; // Ensure it's an evaluator
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $evaluatorId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $evaluator = $result->fetch_assoc();
        echo json_encode(["success" => true, "evaluator" => $evaluator]);
    } else {
        echo json_encode(["success" => false]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(["success" => false]);
}
?>
