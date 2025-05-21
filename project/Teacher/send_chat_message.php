<?php
include 'connection.php';
session_start();

if (!isset($_POST['selected_submission_id']) || !isset($_POST['message']) || !isset($_POST['receiver_id'])) {
    die("Invalid request.");
}

$submission_id = (int) $_POST['selected_submission_id'];
$message = trim($_POST['message']);
$sender_id = $_SESSION['user_id']; // Guide is logged in
$receiver_id = (int) $_POST['receiver_id']; // This is the student
$is_guide_message = isset($_POST['is_guide']) && $_POST['is_guide'] == '1' ? 1 : 0;
$attachment_path = null;

// Handle file upload
if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == UPLOAD_ERR_OK) {
    $upload_dir = '../Student/images/chat_attachments/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    
    $filename = basename($_FILES['attachment']['name']);
    $unique_name = time() . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', $filename);
    $target_file = $upload_dir . $unique_name;

    if (move_uploaded_file($_FILES['attachment']['tmp_name'], $target_file)) {
        $attachment_path = $target_file;
    }
}

// Insert into chat table
$sql = "INSERT INTO project_stage_chats 
        (submission_id, sender_id, receiver_id, message, file_path, sent_at, is_guide_message) 
        VALUES (?, ?, ?, ?, ?, NOW(), ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiissi", $submission_id, $sender_id, $receiver_id, $message, $attachment_path, $is_guide_message);
$stmt->execute();
$stmt->close();

// Get stage number for return
$stage_sql = "SELECT ps.stage_number 
              FROM project_stage_submissions s
              INNER JOIN project_submission_stages ps ON s.stage_id = ps.id
              WHERE s.id = ?";
$stage_stmt = $conn->prepare($stage_sql);
$stage_stmt->bind_param("i", $submission_id);
$stage_stmt->execute();
$stage_result = $stage_stmt->get_result();
$stage_number = $stage_result->fetch_assoc()['stage_number'] ?? 'Unknown';
$stage_stmt->close();

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => htmlspecialchars($message),
    'attachment' => $attachment_path,
    'sent_at' => date('Y-m-d H:i:s'),
    'stage_number' => $stage_number
]);
exit;
?>
