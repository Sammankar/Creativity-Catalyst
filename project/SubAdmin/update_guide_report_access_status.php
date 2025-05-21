<?php
include 'connection.php'; // Include database connection
include 'guide_sendAccessStatusMail.php'; // Include the new mail function

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $report_id = $_POST['report_id'] ?? null;
    $user_id = $_POST['user_id'] ?? null;
    $action = $_POST['action'] ?? null;

    if (!$report_id || !$user_id || !$action) {
        echo json_encode(["status" => "error", "message" => "Invalid request"]);
        exit;
    }

    try {
        $conn->begin_transaction(); // Start transaction

        // Fetch user details and current access status
        $stmt = $conn->prepare("SELECT full_name, email, access_status FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if (!$user) {
            echo json_encode(["status" => "error", "message" => "User not found"]);
            exit;
        }

        $fullName = $user['full_name'];
        $email = $user['email'];
        $previousAccessStatus = $user['access_status']; // Store previous access status
        $currentAccessStatus = ($action === "give_access") ? 1 : 0; // Determine new access status

        if ($action === "give_access") {
            // Update user access
            $updateUser = $conn->prepare("UPDATE users SET access_status = 1 WHERE user_id = ?");
            $updateUser->bind_param("i", $user_id);
            $updateUser->execute();

            // Update report log status and store access statuses
            $updateReport = $conn->prepare("UPDATE guide_access_denial_reports SET access_denial_report_status = 1, previous_access_status = ?, current_access_status = ? WHERE report_id = ?");
            $updateReport->bind_param("iii", $previousAccessStatus, $currentAccessStatus, $report_id);
            $updateReport->execute();

        } elseif ($action === "restrict") {
            // Only update the report log status and store access statuses
            $updateReport = $conn->prepare("UPDATE guide_access_denial_reports SET access_denial_report_status = 1, previous_access_status = ?, current_access_status = ? WHERE report_id = ?");
            $updateReport->bind_param("iii", $previousAccessStatus, $currentAccessStatus, $report_id);
            $updateReport->execute();
        }

        $conn->commit(); // Commit changes

        // Send Email Notification
        sendAccessStatusMail($email, $fullName, $currentAccessStatus);

        echo json_encode(["status" => "success", "message" => "Access updated successfully"]);
    } catch (Exception $e) {
        $conn->rollback(); // Rollback if any error
        echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
}
?>
