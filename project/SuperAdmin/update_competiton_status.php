<?php
session_start();
header('Content-Type: application/json');
include 'connection.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id']) || !isset($data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$competitionId = intval($data['id']);
$newStatus = intval($data['status']);

// Prepare update statement
$stmt = $conn->prepare("UPDATE competitions SET competition_status = ? WHERE competition_id = ?");
$stmt->bind_param("ii", $newStatus, $competitionId);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'newStatus' => $newStatus
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Database update failed.'
    ]);
}
?>
