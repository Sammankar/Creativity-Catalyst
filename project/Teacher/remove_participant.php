<?php
include 'connection.php';
session_start();

// Set content type to JSON
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Unknown error'];

if (isset($_POST['participant_id'])) {
    $participant_id = $_POST['participant_id'];

    $deleteQuery = "DELETE FROM competition_participants WHERE participant_id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $participant_id);

    if ($stmt->execute()) {
        $_SESSION['message'] = 'Student removed successfully';
        $_SESSION['message_type'] = 'success';
        $response['success'] = true;
        $response['message'] = 'Student removed successfully';
    } else {
        $_SESSION['message'] = 'Error removing student';
        $_SESSION['message_type'] = 'error';
        $response['message'] = 'Error executing delete query';
    }

    $stmt->close();
} else {
    $response['message'] = 'Participant ID not received';
}

// Return JSON response
echo json_encode($response);
exit;
?>
