<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(["error" => "Unauthorized"]));
}

$report_id = $_GET['report_id'];

// Query to fetch messages for the given report_id
$query = "SELECT cm.*, u.full_name AS sender_name 
          FROM sub_admin_chat_messages cm
          JOIN users u ON cm.sender_id = u.user_id
          WHERE cm.report_id = ?
          ORDER BY cm.timestamp ASC";
$stmt = $conn->prepare($query);

if ($stmt === false) {
    die(json_encode(["error" => "Failed to prepare the query"]));
}

$stmt->bind_param("i", $report_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result === false) {
    die(json_encode(["error" => "Failed to fetch messages"]));
}

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

echo json_encode($messages);
?>
