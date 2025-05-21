<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = "Unauthorized access! Please log in as Super-Admin.";
    $_SESSION['message_type'] = "error";
    header("Location: index.php");
    exit();
}

include("connection.php");

$created_by = $_SESSION['user_id'];
$academic_id = $_POST['academic_id']; // The ID of the academic calendar to be updated
$start_date = $_POST['start_date'];
$end_date = $_POST['end_date'];

// Fetch the academic calendar details
$query_academic = "SELECT ac.*, c.name AS course_name, c.total_semesters, c.course_status, ac.is_editable, ac.end_date AS calendar_end_date, ac.academic_year
                   FROM academic_calendar ac 
                   JOIN courses c ON ac.course_id = c.course_id
                   WHERE ac.id = ?";
$stmt_academic = $conn->prepare($query_academic);
$stmt_academic->bind_param("i", $academic_id);
$stmt_academic->execute();
$result_academic = $stmt_academic->get_result()->fetch_assoc();
$stmt_academic->close();

if (!$result_academic) {
    $_SESSION['message'] = "Academic calendar not found.";
    $_SESSION['message_type'] = "error";
    header("Location: update_academics.php?id=$academic_id");
    exit();
}

// Check if the academic calendar is editable
$can_edit = $result_academic['is_editable'] == 1 && (strtotime($result_academic['calendar_end_date']) >= time());
if (!$can_edit) {
    $_SESSION['message'] = "This academic calendar is not editable because the editing window has closed.";
    $_SESSION['message_type'] = "error";
    header("Location: update_academics.php?id=$academic_id");
    exit();
}

// Check if the academic calendar's time has passed
$current_time = time();
$existing_start_date = strtotime($result_academic['start_date']);
$existing_end_date = strtotime($result_academic['end_date']);

if ($current_time > $existing_end_date) {
    $_SESSION['message'] = "The selected academic calendar period has already ended and cannot be updated. Please create a new academic calendar for future periods.";
    $_SESSION['message_type'] = "error";
    header("Location: update_academics.php?id=$academic_id");
    exit();
}

// Extract the academic year (e.g., 2025-26)
$academic_year = $result_academic['academic_year'];
list($start_year, $end_year) = explode("-", $academic_year);

// Check if the new start and end dates are within the academic year range
$start_date_timestamp = strtotime($start_date);
$end_date_timestamp = strtotime($end_date);
$year_start = strtotime($start_year . '-01-01');
$year_end = strtotime($end_year . '-12-31');

if ($start_date_timestamp < $year_start || $start_date_timestamp > $year_end || $end_date_timestamp < $year_start || $end_date_timestamp > $year_end) {
    $_SESSION['message'] = "The new start and end dates must be within the $academic_year academic year.";
    $_SESSION['message_type'] = "error";
    header("Location: update_academics.php?id=$academic_id");
    exit();
}

// Check if the new start and end dates overlap with any existing calendar entries for the same course and semester
$query_check = "SELECT id FROM academic_calendar WHERE course_id = ? AND semester = ? AND academic_year = ? AND id != ?";
$stmt_check = $conn->prepare($query_check);
$stmt_check->bind_param("isis", $result_academic['course_id'], $result_academic['semester'], $result_academic['academic_year'], $academic_id);
$stmt_check->execute();
$stmt_check->store_result();

if ($stmt_check->num_rows > 0) {
    $_SESSION['message'] = "Another academic calendar entry exists for this course, semester, and academic year.";
    $_SESSION['message_type'] = "error";
    header("Location: update_academics.php?id=$academic_id");
    exit();
}

$stmt_check->close();

// Proceed with the update
$stmt_update = $conn->prepare("UPDATE academic_calendar 
                               SET start_date = ?, end_date = ? 
                               WHERE id = ?");
$stmt_update->bind_param("ssi", $start_date, $end_date, $academic_id);

if ($stmt_update->execute()) {
    $_SESSION['message'] = "Academic calendar updated successfully!";
    $_SESSION['message_type'] = "success";
    header("Location: academic_calendar.php"); // Redirect to academic_calendar.php after success
    exit();
} else {
    $_SESSION['message'] = "Error updating academic calendar.";
    $_SESSION['message_type'] = "error";
    header("Location: update_academics.php?id=$academic_id"); // Stay on the update page in case of error
    exit();
}

$stmt_update->close();
?>
