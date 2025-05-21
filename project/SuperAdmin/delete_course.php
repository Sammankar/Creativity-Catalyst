<?php
include 'connection.php'; // Ensure database connection is included

header("Content-Type: application/json");

// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['id'])) {
    $course_id = intval($data['id']);

    // Delete query
    $stmt = $conn->prepare("DELETE FROM courses WHERE course_id = ?");
    $stmt->bind_param("i", $course_id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Course deleted successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
    }

    $stmt->close();
} else {
    echo json_encode(["success" => false, "message" => "Invalid request."]);
}

$conn->close();
?>
