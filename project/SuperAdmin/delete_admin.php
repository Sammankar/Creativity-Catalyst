<?php
include 'connection.php'; // Ensure database connection is included
require_once 'mail_deletion.php'; // Include the new mail_deletion.php file

header("Content-Type: application/json");

// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['id'])) {
    $admin_id = intval($data['id']);

    // Get the admin's email before deletion
    $stmt = $conn->prepare("SELECT email FROM users WHERE user_id = ? AND role = 2"); // Ensure it's an admin
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        $admin_email = $admin['email'];
    } else {
        echo json_encode(["success" => false, "message" => "Admin not found."]);
        exit;
    }

    // Step 1: Delete the associated college record
    $deleteCollegeStmt = $conn->prepare("DELETE FROM colleges WHERE admin_id = ?");
    $deleteCollegeStmt->bind_param("i", $admin_id);
    
    if (!$deleteCollegeStmt->execute()) {
        echo json_encode(["success" => false, "message" => "Failed to delete college records."]);
        exit;
    }

    // Step 2: Delete the admin from users table
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ? AND role = 2"); // Ensure it's an admin
    $stmt->bind_param("i", $admin_id);

    if ($stmt->execute()) {
        // Send email after successful deletion
        $email_sent = sendAccountDeletionEmail($admin_email);  // Send the email to the deleted admin

        // Respond based on email status
        if ($email_sent) {
            echo json_encode(["success" => true, "message" => "Admin and associated college deleted, and notification email sent."]);
        } else {
            echo json_encode(["success" => true, "message" => "Admin and associated college deleted, but email could not be sent."]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
    }

    $stmt->close();
    $deleteCollegeStmt->close();
} else {
    echo json_encode(["success" => false, "message" => "Invalid request."]);
}

$conn->close();
?>
