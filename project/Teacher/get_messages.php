<?php
// get_messages.php

// get_messages.php

// get_messages.php

include 'connection.php'; // Include the database connection

// Check if the report_id is passed in the GET request
if (isset($_GET['report_id'])) {
    $report_id = $_GET['report_id']; // The report ID passed from the client
    
    // Fetch all messages related to the given report_id
    $stmt = $conn->prepare("SELECT cm.*, u.full_name AS sender_name 
                            FROM guide_chat_messages cm
                            JOIN users u ON cm.sender_id = u.user_id
                            WHERE cm.report_id = ? 
                            ORDER BY cm.timestamp ASC"); // Order by creation date
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $result = $stmt->get_result(); // Execute the query and get the result

    // Prepare an array to store the messages
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row; // Add each message to the array
    }

    // Return the messages as a JSON response
    echo json_encode($messages);
} else {
    echo json_encode(["success" => false, "message" => "Report ID not provided."]);
}

?>
