<?php
include 'connection.php';
session_start();

if (!isset($_POST['selected_submission_id']) || !isset($_POST['message'])) {
    die("Invalid request.");
}
$receiver_id = $_SESSION['receiver_id'];
$submission_id = (int) $_POST['selected_submission_id'];
$message = trim($_POST['message']);
$sender_id = $_SESSION['user_id']; // Assuming student is logged in
$is_guide_message = 0;
$attachment_path = null;

if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == UPLOAD_ERR_OK) {
    $upload_dir = 'images/chat_attachments/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    $filename = basename($_FILES['attachment']['name']);
    $target_file = $upload_dir . time() . '_' . $filename;
    move_uploaded_file($_FILES['attachment']['tmp_name'], $target_file);
    $attachment_path = $target_file;
}

$sql = "INSERT INTO project_stage_chats 
        (submission_id, sender_id, receiver_id, message, file_path, sent_at, is_guide_message) 
        VALUES (?, ?, ?, ?, ?, NOW(), ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiissi", $submission_id, $sender_id, $receiver_id, $message, $attachment_path, $is_guide_message);
$stmt->execute();
$stmt->close();

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => htmlspecialchars($message),
    'attachment' => $attachment_path,
    'sent_at' => date('Y-m-d H:i:s')
]);
exit;
?>