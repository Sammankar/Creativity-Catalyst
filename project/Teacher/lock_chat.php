<?php
include 'connection.php';
session_start();

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['submission_ids']) || !is_array($data['submission_ids'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$submission_ids = $data['submission_ids'];
$locked_by = $_SESSION['user_id']; // Guide who locked

$insert_stmt = $conn->prepare("INSERT INTO stage_chat_locks (submission_id, locked_by, locked_at) VALUES (?, ?, NOW())");

foreach ($submission_ids as $submission_id) {
    $submission_id = (int)$submission_id;

    // Check if already locked
    $check = $conn->prepare("SELECT id FROM stage_chat_locks WHERE submission_id = ?");
    $check->bind_param("i", $submission_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows === 0) {
        $insert_stmt->bind_param("ii", $submission_id, $locked_by);
        $insert_stmt->execute();
    }

    $check->close();
}

$insert_stmt->close();

echo json_encode(['success' => true]);
exit;
?>
