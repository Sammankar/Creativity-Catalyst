<?php
session_start();
include 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get data from the POST request
    $report_id = $_POST['report_id'];
    $super_admin_id = $_POST['super_admin_id']; // Super-Admin's user ID (sent from JavaScript)
    $admin_user_id = $_POST['admin_user_id']; // Admin's user ID (sent from JavaScript)
    $message = $_POST['message']; // The message content
    
    // Check if the message is not empty
    if (empty($message)) {
        echo json_encode(["success" => false, "message" => "Message cannot be empty."]);
        exit;
    }

    // Check if the chat is locked
    $lock_query = "SELECT locked FROM chat_reports WHERE report_id = ?";
    $stmt = $conn->prepare($lock_query);
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row['locked']) {
        echo json_encode(["success" => false, "message" => "Chat is locked. You cannot send messages."]);
        exit; // If locked, don't allow message sending
    }

    // Insert the message into the database (into the `chat_messages` table)
    $stmt = $conn->prepare("INSERT INTO chat_messages (report_id, sender_id, receiver_id, message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $report_id, $super_admin_id, $admin_user_id, $message);
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true]); // Return success if the message is inserted
    } else {
        echo json_encode(["success" => false, "message" => "Error sending message."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
}
?>
