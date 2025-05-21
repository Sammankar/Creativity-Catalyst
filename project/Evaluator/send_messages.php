<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(["error" => "Unauthorized"]));
}

$sender_id = $_SESSION['user_id'];
$receiver_id = $_POST['receiver_id'];  // Super-Admin ID
$report_id = $_POST['report_id'];
$message = $_POST['message'];

// Validate message
$message = trim($message);
if (empty($message)) {
    die(json_encode(["error" => "Message cannot be empty"]));
}

// Check if the chat is locked in the reports table
$query = "SELECT locked FROM evaluator_chat_reports WHERE report_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $report_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if ($row['chat_locked'] == 1) {
        die(json_encode(["error" => "Chat is locked. You cannot send messages."]));
    }
} else {
    die(json_encode(["error" => "Invalid report ID."]));
}

// Insert the message if chat is not locked
$query = "INSERT INTO evaluator_chat_messages (report_id, sender_id, receiver_id, message) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("iiis", $report_id, $sender_id, $receiver_id, $message);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    error_log("Error sending message: " . $stmt->error);
    echo json_encode(["error" => "Failed to send message"]);
}
?>
