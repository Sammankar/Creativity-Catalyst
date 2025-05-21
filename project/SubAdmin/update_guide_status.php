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
    $guide_id = $_POST['guide_id'];
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
    require 'mail_status.php';

    // Update admin status in the users table
    $stmt = $conn->prepare("UPDATE users SET users_status = ? WHERE user_id = ?");
    $stmt->bind_param("ii", $status_action, $guide_id);
    $stmt->execute();

    if ($stmt->error) {
        // If there's a database error, return JSON error message
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
        exit;
    }
    $stmt->close();

    // Update college status in the colleges table based on the admin's status
    $stmt2 = $conn->prepare("UPDATE colleges SET college_status = ? WHERE admin_id = ?");
    $stmt2->bind_param("ii", $status_action, $guide_id);
    $stmt2->execute();

    if ($stmt2->error) {
        // If there's a database error, return JSON error message
        echo json_encode(['success' => false, 'error' => 'Failed to update college status: ' . $stmt2->error]);
        exit;
    }
    $stmt2->close();

    // Send email (Assuming email sending logic exists)
    // If the email sending is successful, proceed
    $emailResult = sendGuideStatusEmail($guide_id, $changed_by, $status_action, $subject, $body, $warning, $_FILES['attachments'] ?? null);

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
