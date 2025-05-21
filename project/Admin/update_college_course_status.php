<?php
require 'connection.php'; // Include your database connection

// Get the data from the request
$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['id']) && isset($data['status'])) {
    $id = intval($data['id']);
    $newStatus = intval($data['status']);

    // Check if the course is frozen
    $checkStmt = $conn->prepare("SELECT college_course_status FROM college_courses WHERE college_course_id = ?");
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $checkStmt->bind_result($currentStatus);
    $checkStmt->fetch();
    $checkStmt->close();

    if ($currentStatus == 2) {
        echo json_encode(["success" => false, "message" => "This course is temporarily frozen by Super-Admin."]);
        exit;
    }

    // Update the college course status
    $stmt = $conn->prepare("UPDATE college_courses SET college_course_status = ? WHERE college_course_id = ?");
    $stmt->bind_param("ii", $newStatus, $id);

    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "newStatus" => $newStatus,
        ]);
    } else {
        echo json_encode([
            "success" => false,
        ]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode([
        "success" => false,
    ]);
}

?>
