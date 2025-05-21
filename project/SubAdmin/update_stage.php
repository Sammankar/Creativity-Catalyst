<?php
include "connection.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stage_id = intval($_POST['stage_id']);
    $schedule_id = intval($_POST['schedule_id']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $unlock_date = $_POST['unlock_date'];
    $no_of_files = intval($_POST['no_of_files']);

    // Basic validation
    if (!$stage_id || !$schedule_id || empty($title) || empty($unlock_date) || $no_of_files < 1 || $no_of_files > 2) {
        header("Location: edit_project_schedule.php?schedule_id=$schedule_id&status=error&message=Invalid+input+provided.");
        exit;
    }

    // Check project schedule bounds
    $stmt = $conn->prepare("SELECT start_date, end_date FROM project_submission_schedule WHERE id = ?");
    $stmt->bind_param("i", $schedule_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        header("Location: edit_project_schedule.php?schedule_id=$schedule_id&status=error&message=Schedule+not+found.");
        exit;
    }
    $schedule = $result->fetch_assoc();
    $stmt->close();

    $proj_start = $schedule['start_date'];
    $proj_end = $schedule['end_date'];

    if ($unlock_date < $proj_start || $unlock_date > $proj_end) {
        header("Location: edit_project_schedule.php?schedule_id=$schedule_id&status=error&message=Unlock+date+must+be+within+project+schedule.");
        exit;
    }

    // Update the stage
    $stmt = $conn->prepare("UPDATE project_submission_stages 
                            SET title = ?, description = ?, unlock_date = ?, no_of_files = ?, updated_at = NOW()
                            WHERE id = ?");
    $stmt->bind_param("sssii", $title, $description, $unlock_date, $no_of_files, $stage_id);

    if ($stmt->execute()) {
        $stmt->close();
        header("Location: edit_project_schedule.php?schedule_id=$schedule_id&status=success&message=Stage+updated+successfully.");
        exit;
    } else {
        $stmt->close();
        header("Location: edit_project_schedule.php?schedule_id=$schedule_id&status=error&message=Failed+to+update+stage.");
        exit;
    }
} else {
    echo "Invalid request method.";
}
