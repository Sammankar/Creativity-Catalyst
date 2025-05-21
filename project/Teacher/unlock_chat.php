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

// Use prepared statement to delete locks for the selected submission_ids
$placeholders = implode(',', array_fill(0, count($submission_ids), '?'));
$types = str_repeat('i', count($submission_ids));

$sql = "DELETE FROM stage_chat_locks WHERE submission_id IN ($placeholders)";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$submission_ids);
$stmt->execute();

if ($stmt->affected_rows >= 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'No rows deleted']);
}

$stmt->close();
?>
