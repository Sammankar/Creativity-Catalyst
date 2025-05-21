<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_SESSION['user_id'])) {
        $changed_by = $_SESSION['user_id'];
    } else {
        echo json_encode(['success' => false, 'error' => 'User not logged in']);
        exit;
    }

    $user_id = $_POST['user_id'];
    $access_action = $_POST['access_action'];
    $subject = htmlspecialchars($_POST['subject']);
    $body = htmlspecialchars($_POST['body']);
    $warning = htmlspecialchars($_POST['warning']);

    include 'connection.php';
    require 'mail_restriction_access.php';
    
    // Update access status
    $stmt = $conn->prepare("UPDATE users SET access_status = ? WHERE user_id = ?");
    $stmt->bind_param("ii", $access_action, $user_id);
    $stmt->execute();

    if ($stmt->error) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
        exit;
    }
    $stmt->close();

    // Send email
    $emailResult = sendAccessChangeEmail($user_id, $changed_by, $access_action, $subject, $body, $warning, $_FILES['attachments'] ?? null);

    if ($emailResult === "Access updated and email sent.") {
        echo json_encode(['success' => true, 'message' => 'Access status updated and email sent.']);
    } else {
        echo json_encode(['success' => false, 'error' => $emailResult]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
}
?>
