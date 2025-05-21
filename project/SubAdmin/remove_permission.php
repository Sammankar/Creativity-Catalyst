<?php
include('connection.php');

// Check if 'user_id' is set in POST
if (!isset($_POST['user_id'])) {
    http_response_code(400);
    echo 'Invalid Request';
    exit;
}

$user_id = intval($_POST['user_id']);  // Use POST to get user_id

// Update the guide permission to 0
$update = "UPDATE users SET guide_permission = 0 WHERE user_id = $user_id";

if ($conn->query($update)) {
    echo 'success';  // Return success message
} else {
    http_response_code(500);
    echo 'Error updating guide permission.';  // Error if something goes wrong
}
?>
