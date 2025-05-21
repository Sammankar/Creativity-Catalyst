<?php
include "connection.php";  // Database connection

// Start the session to access session variables
session_start();

// Ensure that the user is logged in
$student_user_id = $_SESSION['user_id'] ?? null;
if (!$student_user_id) {
    $_SESSION['message'] = "User not logged in.";
    $_SESSION['message_type'] = "error";
    header("Location: submit_competition.php");
    exit;
}

// Fetch user details from the database
$sql = "SELECT college_id, course_id, current_semester FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_user_id);
$stmt->execute();
$user_result = $stmt->get_result();

if ($user_result->num_rows === 0) {
    $_SESSION['message'] = "User not found.";
    $_SESSION['message_type'] = "error";
    header("Location: submit_competition.php");
    exit;
}

// Get user details from the result
$user = $user_result->fetch_assoc();
$college_id = $user['college_id'];
$course_id = $user['course_id'];
$current_semester = $user['current_semester'];

// Get competition_id from the form
$competition_id = $_POST['competition_id'] ?? null;
if (!$competition_id) {
    $_SESSION['message'] = "Competition ID is missing.";
    $_SESSION['message_type'] = "error";
    header("Location: submit_competition.php");
    exit;
}

// Get the project title from the form
$project_title = trim($_POST['project_title'] ?? '');
if (!$project_title) {
    $_SESSION['message'] = "Project title is required.";
    $_SESSION['message_type'] = "error";
    header("Location: submit_competition.php");
    exit;
}

// Initialize an array to hold the file paths
$submitted_files = [];

// Loop through each uploaded file
foreach ($_FILES as $file_key => $file) {
    preg_match('/file_(\d+)/', $file_key, $matches);
    $file_number = $matches[1] ?? null;

    if ($file_number) {
        $file_type = $_POST["file_type_$file_number"];
        $max_size = ($file_type === 'pdf') ? 20 * 1024 * 1024 : 50 * 1024 * 1024;  // 20MB for PDF, 50MB for video
        $allowed_extensions = ($file_type === 'pdf') ? ['pdf'] : ['mp4', 'mov', 'avi', 'mkv'];

        // Validate file type and size
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (!in_array(strtolower($file_extension), $allowed_extensions)) {
            $_SESSION['message'] = "Invalid file type for file $file_number.";
            $_SESSION['message_type'] = "error";
            header("Location: submit_competition.php");
            exit;
        }
        if ($file['size'] > $max_size) {
            $_SESSION['message'] = "File $file_number exceeds the maximum size limit.";
            $_SESSION['message_type'] = "error";
            header("Location: submit_competition.php");
            exit;
        }

        // Move the file to the server
        $upload_dir = 'images/competition_submissions/';
        $filename = $upload_dir . uniqid() . '.' . $file_extension;
        if (!move_uploaded_file($file['tmp_name'], $filename)) {
            $_SESSION['message'] = "Error uploading file $file_number.";
            $_SESSION['message_type'] = "error";
            header("Location: submit_competition.php");
            exit;
        }

        // Add the file path to the array
        $submitted_files[] = $filename;
    }
}

// If no files were uploaded, show an error
if (empty($submitted_files)) {
    $_SESSION['message'] = "Please upload at least one valid file.";
    $_SESSION['message_type'] = "error";
    header("Location: submit_competition.php");
    exit;
}

// Convert the array to a JSON string
$submitted_files_str = json_encode($submitted_files);

// Insert the data into the database
$sql = "INSERT INTO student_submissions (
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

$stmt = $conn->prepare($sql);
$submission_date = $created_at = $updated_at = date('Y-m-d H:i:s');

$stmt->bind_param(
    "iiiiisssss", 
    $competition_id, 
    $student_user_id, 
    $college_id, 
    $course_id, 
    $current_semester, 
    $project_title,
    $submitted_files_str, 
    $submission_date, 
    $created_at, 
    $updated_at
);

if ($stmt->execute()) {
    // Success: Set session message and redirect
    $_SESSION['message'] = "Files submitted successfully.";
    $_SESSION['message_type'] = "success";
    header("Location: view_competition.php?competition_id=" . $competition_id);
    exit;
} else {
    // Error: Set session message and redirect
    $_SESSION['message'] = "Error submitting files: " . $stmt->error;
    $_SESSION['message_type'] = "error";
    header("Location: submit_competition.php");
    exit;
}
?>
