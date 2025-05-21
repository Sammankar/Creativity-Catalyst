<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if user is logged in and session contains the user ID
    if (isset($_SESSION['user_id'])) {
        $changed_by = $_SESSION['user_id'];
    } else {
        echo json_encode(['success' => false, 'error' => 'User not logged in']);
        exit;
    }

    // Get form data and sanitize
    $student_id = $_POST['student_id']; // student being updated
    $status_action = $_POST['status_action']; // 1 or 0 (Activate/Deactivate)
    $subject = htmlspecialchars($_POST['subject']);
    $body = htmlspecialchars($_POST['body']);
    $warning = htmlspecialchars($_POST['warning']);

    // Handle file attachments if present
    $attachments = isset($_FILES['attachments']) ? $_FILES['attachments'] : null;

    // Connect to the database
    include 'connection.php';
    require 'mail_status_student.php';

    // Fetch current status for student
    $stmt = $conn->prepare("SELECT users_status FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();

    if (!$student) {
        echo json_encode(['success' => false, 'error' => 'student not found.']);
        exit;
    }

    $old_status = $student['users_status'];

    // Update student's status in the database
    $stmt = $conn->prepare("UPDATE users SET users_status = ? WHERE user_id = ?");
    $stmt->bind_param("ii", $status_action, $student_id);
    $stmt->execute();

    if ($stmt->error) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
        exit;
    }
    $stmt->close();

    // Log the status change in the student_status_reports table
    $stmt = $conn->prepare("INSERT INTO student_status_reports (student_id, changed_by, old_status, new_status, subject, body, warning) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiisss", $student_id, $changed_by, $old_status, $status_action, $subject, $body, $warning);
    $stmt->execute();
    $stmt->close();

    // ✅ Update status in student_academics table instead of inserting
    $stmt = $conn->prepare("UPDATE student_academics SET status = ?, changed_by = ?, updated_at = NOW() WHERE user_id = ?");
    $stmt->bind_param("iii", $status_action, $changed_by, $student_id);
    $stmt->execute();
    $stmt->close();

    // Send email to student (using the new email function for students)
    $emailResult = sendstudentStatusEmail($student_id, $changed_by, $status_action, $subject, $body, $warning, $attachments);

    if ($emailResult === "Status updated and email sent.") {
        echo json_encode(['success' => true, 'message' => 'Status has been updated and an email has been sent to the student.']);
    } else {
        echo json_encode(['success' => false, 'error' => $emailResult]);
    }

} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
}
?>
