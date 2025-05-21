<?php
include "connection.php";
session_start();

function redirectWithMessage($success, $message, $competition_id, $redirect_page) {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $success ? 'success' : 'error';
    header("Location: {$redirect_page}?competition_id=" . urlencode($competition_id));
    exit;
}

$student_user_id = $_SESSION['user_id'] ?? null;
if (!$student_user_id) {
    redirectWithMessage(false, "User not logged in.", 0, "submit_again.php");
}

// Fetch student details
$sql = "SELECT college_id, course_id, current_semester FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_user_id);
$stmt->execute();
$user_result = $stmt->get_result();

if ($user_result->num_rows === 0) {
    redirectWithMessage(false, "User not found.", 0, "submit_again.php");
}

$user = $user_result->fetch_assoc();
$college_id = $user['college_id'];
$course_id = $user['course_id'];
$current_semester = $user['current_semester'];

// Get competition_id and title
$competition_id = $_POST['competition_id'] ?? null;
$title = $_POST['title'] ?? null;

if (!$competition_id) {
    redirectWithMessage(false, "Competition ID is missing.", 0, "submit_again.php");
}

if (!$title) {
    redirectWithMessage(false, "Title is required.", $competition_id, "submit_again.php");
}

// Directory to save uploads
$upload_dir = 'images/competition_submissions/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Store submitted file paths
$submitted_files = [];

// Process uploaded files
foreach ($_FILES as $key => $file) {
    if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) continue;

    preg_match('/file_(\d+)/', $key, $matches);
    $file_number = $matches[1] ?? null;
    if (!$file_number) continue;

    $file_type = $_POST["file_type_$file_number"] ?? '';
    $max_size = ($file_type === 'pdf') ? 20 * 1024 * 1024 : 50 * 1024 * 1024;
    $allowed_extensions = ($file_type === 'pdf') ? ['pdf'] : ['mp4', 'mov', 'avi', 'mkv'];

    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, $allowed_extensions)) {
        redirectWithMessage(false, "Invalid file type for File $file_number.", $competition_id, "submit_again.php");
    }

    if ($file['size'] > $max_size) {
        redirectWithMessage(false, "File $file_number exceeds max allowed size.", $competition_id, "submit_again.php");
    }

    $unique_filename = uniqid("file_{$student_user_id}_") . '.' . $file_extension;
    $destination = $upload_dir . $unique_filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        redirectWithMessage(false, "Failed to upload File $file_number.", $competition_id, "submit_again.php");
    }

    $submitted_files[$file_number] = $destination;
}

// No file uploaded
if (empty($submitted_files)) {
    redirectWithMessage(false, "Please upload at least one file.", $competition_id, "submit_again.php");
}

// JSON encode for storing
$submitted_files_json = json_encode($submitted_files);
$now = date('Y-m-d H:i:s');

// Check if a submission already exists
$check_sql = "SELECT submission_id FROM student_submissions WHERE competition_id = ? AND student_user_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $competition_id, $student_user_id);
$check_stmt->execute();
$check_stmt->store_result();

if ($check_stmt->num_rows > 0) {
    // Update existing submission
    $update_sql = "UPDATE student_submissions SET 
        title = ?, 
        submitted_files = ?, 
        submission_date = ?, 
        updated_at = ?, 
        is_verified_by_project_head = 0 
        WHERE competition_id = ? AND student_user_id = ?";

    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssssii", $title, $submitted_files_json, $now, $now, $competition_id, $student_user_id);

    if ($update_stmt->execute()) {
        redirectWithMessage(true, "✅ Submission updated successfully.", $competition_id, "view_submission.php");
    } else {
        redirectWithMessage(false, "Error updating submission: " . $update_stmt->error, $competition_id, "submit_again.php");
    }
} else {
    // Insert new submission
    $insert_sql = "INSERT INTO student_submissions (
        competition_id, 
        student_user_id, 
        college_id, 
        course_id, 
        current_semester, 
        title, 
        submitted_files, 
        submission_date, 
        created_at, 
        updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param(
        "iiiiisssss", 
        $competition_id, 
        $student_user_id, 
        $college_id, 
        $course_id, 
        $current_semester, 
        $title, 
        $submitted_files_json, 
        $now, $now, $now
    );

    if ($stmt->execute()) {
        redirectWithMessage(true, "✅ Files submitted successfully.", $competition_id, "view_submission.php");
    } else {
        redirectWithMessage(false, "Error submitting files: " . $stmt->error, $competition_id, "submit_again.php");
    }
}
?>
