<?php
session_start(); // Start the session

// Ensure no output is sent before the response (no HTML or echo statements above this)
header('Content-Type: application/json'); // Set the content type to JSON

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ensure the user is logged in and the session contains the user ID
    if (isset($_SESSION['user_id'])) {
        $changed_by = $_SESSION['user_id'];
    } else {
        echo json_encode(['success' => false, 'error' => 'User not logged in']);
        exit;
    }

    // Get form data and sanitize
    $evaluator_id = $_POST['evaluator_id'];
    $status_action = $_POST['status_action'];
    $subject = htmlspecialchars($_POST['subject']);
    $body = htmlspecialchars($_POST['body']);
    $warning = htmlspecialchars($_POST['warning']);
    
    // Handle file attachments if present
    if (isset($_FILES['attachments'])) {
        // File upload handling code goes here (optional)
    }

    // Connect to the database
    include 'connection.php';
    require 'mail_status_evaluator.php';

    // Update evaluator status in the users table
    $stmt = $conn->prepare("UPDATE users SET users_status = ? WHERE user_id = ?");
    $stmt->bind_param("ii", $status_action, $evaluator_id);
    $stmt->execute();

    if ($stmt->error) {
        // If there's a database error, return JSON error message
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
        exit;
    }
    $stmt->close();

    // Send email (Assuming email sending logic exists)
    // If the email sending is successful, proceed
    $emailResult = sendevaluatorStatusEmail($evaluator_id, $changed_by, $status_action, $subject, $body, $warning, $_FILES['attachments'] ?? null);

    // If email sending is successful
    if ($emailResult === "Status updated and email sent.") {
        echo json_encode(['success' => true, 'message' => 'Status has been updated and an email has been sent.']);
    } else {
        // In case email failed
        echo json_encode(['success' => false, 'error' => $emailResult]);
    }

} else {
    // If it's not a POST request
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
}
?>
