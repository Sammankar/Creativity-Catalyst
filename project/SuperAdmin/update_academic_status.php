<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

$changed_by = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['id']) || !isset($data['status'])) {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
    exit;
}

$calendar_id = $data['id'];
$status_action = $data['status'];

include 'connection.php';
require 'sendCalendarStatusEmail.php';

// Update the academic calendar status
$stmt = $conn->prepare("UPDATE academic_calendar SET status = ? WHERE id = ?");
$stmt->bind_param("ii", $status_action, $calendar_id);
$stmt->execute();

if ($stmt->error) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
    exit;
}
$stmt->close();

// Fetch academic calendar details
$stmt2 = $conn->prepare("SELECT * FROM academic_calendar WHERE id = ?");
$stmt2->bind_param("i", $calendar_id);
$stmt2->execute();
$result = $stmt2->get_result();
$calendar = $result->fetch_assoc();
$stmt2->close();

if (!$calendar) {
    echo json_encode(['success' => false, 'error' => 'Calendar not found']);
    exit;
}

// Send email
$emailResult = sendCalendarStatusEmail($calendar['created_by'], $calendar['semester'], $status_action, $calendar['course_id']);

if (strpos($emailResult, 'email sent') !== false) {
    echo json_encode(['success' => true, 'message' => 'Academic calendar status updated and email sent.']);
} else {
    echo json_encode(['success' => false, 'error' => 'Status updated but email failed: ' . $emailResult]);
}
?>
