<?php
include('connection.php');

// Get the data sent via POST
$data = json_decode(file_get_contents('php://input'), true);
$user_id = $data['user_id'] ?? null;

if ($user_id) {
    // Update guide_permission to 1 (promote guide)
    $query = "UPDATE users SET guide_permission = 1 WHERE user_id = $user_id";
    if ($conn->query($query)) {
        // Return success response
        echo json_encode(['success' => true]);
    } else {
        // Return error response
        echo json_encode(['success' => false, 'message' => 'Failed to promote guide']);
    }
} else {
    // Invalid user_id
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
}
?>
