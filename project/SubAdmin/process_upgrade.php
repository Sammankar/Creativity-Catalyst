<?php
include "connection.php";
session_start();

// Get calendar_id
$calendar_id = isset($_GET['calendar_id']) ? intval($_GET['calendar_id']) : 0;

// Fetch original calendar data
$calendar_sql = "SELECT * FROM academic_calendar WHERE id = ?";
$stmt = $conn->prepare($calendar_sql);
$stmt->bind_param("i", $calendar_id);
$stmt->execute();
$calendar = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$calendar) {
    die("Invalid calendar ID");
}

$course_id = $calendar['course_id'];
$semester = $calendar['semester'];
$academic_year = $calendar['academic_year'];
$start_date = $calendar['start_date'];
$end_date = $calendar['end_date'];

$new_semester = $semester + 1;

// Find next semester's calendar (if available)
$next_calendar_sql = "SELECT * FROM academic_calendar 
                      WHERE course_id = ? AND semester = ? AND start_date > ?
                      ORDER BY start_date ASC LIMIT 1";
$stmt = $conn->prepare($next_calendar_sql);
$stmt->bind_param("iis", $course_id, $new_semester, $end_date);
$stmt->execute();
$next_calendar = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get students eligible for upgrade
$students_sql = "SELECT sa.*, u.full_name, u.current_semester 
                 FROM student_academics sa
                 JOIN users u ON u.user_id = sa.user_id
                 WHERE sa.course_id = ? 
                 AND sa.current_semester = ? 
                 AND sa.current_academic_year = ?";
$stmt = $conn->prepare($students_sql);
$stmt->bind_param("iis", $course_id, $semester, $academic_year);
$stmt->execute();
$students = $stmt->get_result();
$stmt->close();

$success_count = 0;
$failed_count = 0;
$updated_by = $_SESSION['user_id'];
$previous_course_id = $course_id;


while ($student = $students->fetch_assoc()) {
    $user_id = $student['user_id'];
    $college_id = $student['college_id'];
    $prev_academic_year = $student['current_academic_year'];
    $prev_calendar_id = $student['academic_calendar_id'];
    $prev_semester = $student['current_semester'];

    $new_academic_year = null;
    $new_calendar_id = null;
    $needs_manual_assignment = 1;
    $current_calendar_start = null;
    $current_calendar_end = null;

    if ($next_calendar) {
        $new_academic_year = $next_calendar['academic_year'];
        $new_calendar_id = $next_calendar['id'];
        $needs_manual_assignment = 0;
        $current_calendar_start = $next_calendar['start_date'];
        $current_calendar_end = $next_calendar['end_date'];
    }

    
    // 1. Update student_academics
    $update_sa = "UPDATE student_academics 
              SET current_semester = ?, 
                  current_academic_year = ?, 
                  academic_calendar_id = ?, 
                  needs_manual_assignment = ?, 
                  changed_by = ? 
              WHERE user_id = ?";
    $stmt = $conn->prepare($update_sa);
    $stmt->bind_param("sssisi", $new_semester, $new_academic_year, $new_calendar_id, $needs_manual_assignment, $updated_by, $user_id);
    $success_sa = $stmt->execute();
    $stmt->close();

    // 2. Update current_semester in users table
    $update_user = "UPDATE users SET current_semester = ? WHERE user_id = ?";
    $stmt = $conn->prepare($update_user);
    $stmt->bind_param("ii", $new_semester, $user_id);    
    $success_user = $stmt->execute();
    $stmt->close();

    // 3. Insert into student_semester_result
    $insert_ssr = "INSERT INTO student_semester_result 
    (user_id, course_id, college_id, semester, academic_calendar_id, academic_year, status, remarks, updated_by, needs_manual_assignment) 
    VALUES (?, ?, ?, ?, ?, ?, 0, '', ?, ?)";
    $stmt = $conn->prepare($insert_ssr);
    $stmt->bind_param("iiisissi", $user_id, $course_id, $college_id, $new_semester, $new_calendar_id, $new_academic_year, $updated_by, $needs_manual_assignment);
    $success_ssr = $stmt->execute();
    $stmt->close();

    // 4. Insert into academic_semester_upgrade_logs
    $college_query = mysqli_query($conn, "SELECT name FROM colleges WHERE college_id = '$college_id'");
    $college_row = mysqli_fetch_assoc($college_query);
    $college_name = $college_row['name'] ?? '';

    $course_query = mysqli_query($conn, "SELECT name FROM courses WHERE course_id = '$course_id'");
    $course_row = mysqli_fetch_assoc($course_query);
    $course_name = $course_row['name'] ?? '';

    $prev_cal_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT start_date, end_date FROM academic_calendar WHERE id = '$prev_calendar_id'"));
    $prev_start = $prev_cal_data['start_date'] ?? null;
    $prev_end = $prev_cal_data['end_date'] ?? null;

    $log_insert = "INSERT INTO academic_semester_upgrade_logs (
        user_id, college_id, college_name, 
        course_id, course_name, 
        previous_course_id,
        previous_semester, current_semester, 
        previous_academic_year, previous_calendar_id, current_academic_year,
        previous_calendar_start_date, previous_calendar_end_date,
        current_calendar_start_date, current_calendar_end_date,
        academic_calendar_id, upgraded_by
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($log_insert);
    $stmt->bind_param(
        "iisssiiisiissssis",
        $user_id, $college_id, $college_name,
        $course_id, $course_name,
        $previous_course_id,
        $prev_semester, $new_semester,
        $prev_academic_year, $prev_calendar_id, $new_academic_year,
        $prev_start, $prev_end, $current_calendar_start, $current_calendar_end,
        $new_calendar_id, $updated_by
    );
    
    $stmt->execute();
    $stmt->close();

    if ($success_sa && $success_ssr && $success_user) {
        $success_count++;
    } else {
        $failed_count++;
    }
}

// Redirect to preview
header("Location: upgrade_preview.php?id=" . $calendar_id . "&success=$success_count&failed=$failed_count");
exit;
?>
