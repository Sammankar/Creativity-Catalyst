<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(["error" => "Unauthorized"])); // Only Super-Admin can lock/unlock
}

$data = json_decode(file_get_contents("php://input"), true);
if (!isset($data['report_id'])) {
    die(json_encode(["error" => "Missing report_id"])); // Ensure report_id is set
}

$report_id = $data['report_id']; // Report ID from the POST data

// Fetch the current lock state
$query = "SELECT locked FROM guide_chat_reports WHERE report_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $report_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row === null) {
    die(json_encode(["error" => "Report not found"])); // If no report is found
}

$new_locked_state = !$row['locked']; // Toggle lock state

// Update the locked state in the database
$update_query = "UPDATE guide_chat_reports SET locked = ? WHERE report_id = ?";
$update_stmt = $conn->prepare($update_query);
$update_stmt->bind_param("ii", $new_locked_state, $report_id);
if (!$update_stmt->execute()) {
    die(json_encode(["error" => "Failed to update lock state"]));
}

// Return the new lock state (true or false)
echo json_encode(["locked" => $new_locked_state]);
?>
