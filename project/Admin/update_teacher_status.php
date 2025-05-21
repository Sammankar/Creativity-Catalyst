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
    $teacher_id = $_POST['teacher_id']; // Teacher being updated
    $status_action = $_POST['status_action']; // 1 or 0 (Activate/Deactivate)
    $subject = htmlspecialchars($_POST['subject']);
    $body = htmlspecialchars($_POST['body']);
    $warning = htmlspecialchars($_POST['warning']);

    // Handle file attachments if present
    $attachments = isset($_FILES['attachments']) ? $_FILES['attachments'] : null;

    // Connect to the database
    include 'connection.php';
    require 'mail_status_teacher.php';

    // Fetch current status for teacher
    $stmt = $conn->prepare("SELECT users_status FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $teacher = $result->fetch_assoc();
    $stmt->close();

    if (!$teacher) {
        echo json_encode(['success' => false, 'error' => 'Teacher not found.']);
        exit;
    }

    $old_status = $teacher['users_status'];

    // Update teacher's status in the database
    $stmt = $conn->prepare("UPDATE users SET users_status = ? WHERE user_id = ?");
    $stmt->bind_param("ii", $status_action, $teacher_id);
    $stmt->execute();

    if ($stmt->error) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
        exit;
    }
    $stmt->close();

    // Log the status change in the teacher_status_reports table
    $stmt = $conn->prepare("INSERT INTO teacher_status_reports (teacher_id, changed_by, old_status, new_status, subject, body, warning) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiisss", $teacher_id, $changed_by, $old_status, $status_action, $subject, $body, $warning);
    $stmt->execute();
    $stmt->close();

    // Send email to Teacher (using the new email function for Teachers)
    $emailResult = sendTeacherStatusEmail($teacher_id, $changed_by, $status_action, $subject, $body, $warning, $attachments);

    if ($emailResult === "Status updated and email sent.") {
        echo json_encode(['success' => true, 'message' => 'Status has been updated and an email has been sent to the teacher.']);
    } else {
        echo json_encode(['success' => false, 'error' => $emailResult]);
    }

} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
}
?>
