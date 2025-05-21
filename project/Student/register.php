<?php
session_start();
include('connection.php');
require 'mail.php'; // PHPMailer script for email sending

// Check if the form is submitted via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Collect and sanitize form data
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $college_id = mysqli_real_escape_string($conn, $_POST['college_id']);
    $course_id = mysqli_real_escape_string($conn, $_POST['course_id']);
    $semester = mysqli_real_escape_string($conn, $_POST['semester']);

    // Password hashing
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Define role (you can adjust based on your application)
    $role = 5; // Assuming "5" for student role

    // Validation - Check if college and course exist
    $college_check = mysqli_query($conn, "SELECT * FROM colleges WHERE college_id = '$college_id'");
    $course_check = mysqli_query($conn, "SELECT * FROM college_courses WHERE college_id = '$college_id' AND course_id = '$course_id'");

    if (mysqli_num_rows($college_check) == 0 || mysqli_num_rows($course_check) == 0) {
        header("Location: registerdesign.php?message=Invalid college or course selection.&type=error");
        exit;
    }

    // Check if email is already registered
    $check_user = mysqli_query($conn, "SELECT * FROM users WHERE email = '$email'");
    if (mysqli_num_rows($check_user) > 0) {
        header("Location: registerdesign.php?message=Email already registered.&type=error");
        exit;
    }

    // Generate verification token
    $verification_token = bin2hex(random_bytes(32));

    // Insert the user into the database
    $query = "INSERT INTO users (full_name, email, password, college_id, course_id, current_semester, role, users_status, email_verified, verification_token) 
              VALUES ('$name', '$email', '$hashed_password', '$college_id', '$course_id', '$semester', '$role', 0, 0, '$verification_token')";

    if (mysqli_query($conn, $query)) {
        $user_id = mysqli_insert_id($conn);

        // Calculate academic year name (1st Year, 2nd Year, etc.)
        if ($semester == 1 || $semester == 2) {
            $academic_year = "1st Year";
        } elseif ($semester == 3 || $semester == 4) {
            $academic_year = "2nd Year";
        } elseif ($semester == 5 || $semester == 6) {
            $academic_year = "3rd Year";
        } elseif ($semester == 7 || $semester == 8) {
            $academic_year = "4th Year";
        } elseif ($semester == 9 || $semester == 10) {
            $academic_year = "5th Year";
        } else {
            $academic_year = "Unknown";
        }

        // Calculate batch year
        $current_year = date('Y');
        $batch_year = $current_year - floor(($semester - 1) / 2);

        // Determine current_academic_year from academic_calendar if available
        $reg_date = date('Y-m-d');
        $academicYearQuery = "
            SELECT id,academic_year 
            FROM academic_calendar 
            WHERE course_id = '$course_id' 
              AND semester = '$semester'
              AND '$reg_date' BETWEEN start_date AND end_date 
            LIMIT 1
        ";
        $result = mysqli_query($conn, $academicYearQuery);
        $current_academic_year = null;
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $current_academic_year = $row['academic_year'];
            $academic_calendar_id = $row['id'];
        }

        // Insert into student_academics table
        $insertAcademic = "INSERT INTO student_academics 
        (user_id, college_id, course_id, admission_semester, current_semester, academic_year, batch_year, status, current_academic_year, academic_calendar_id) 
        VALUES (
            '$user_id', '$college_id', '$course_id', '$semester', '$semester', '$academic_year', '$batch_year', 0, " .
            ($current_academic_year ? "'$current_academic_year'" : "NULL") . ", " .
            ($academic_calendar_id !== "NULL" ? "'$academic_calendar_id'" : "NULL") . "
        )
    ";
        mysqli_query($conn, $insertAcademic);

        // Insert initial record in student_semester_result with status = 0 (Ongoing)
        $insertResult = "
            INSERT INTO student_semester_result 
            (user_id, course_id, college_id, semester, academic_year, academic_calendar_id, status) 
            VALUES (
                '$user_id', '$course_id', '$college_id', '$semester', " .
                ($current_academic_year ? "'$current_academic_year'" : "NULL") . ", " .
                ($academic_calendar_id !== "NULL" ? "'$academic_calendar_id'" : "NULL") . ", 0
            )
        ";
        mysqli_query($conn, $insertResult);

        // Send verification email
        $verification_link = "http://localhost/project/Student/verify.php?token=$verification_token";
        if (sendVerificationEmail($email, $verification_link)) {
            header("Location: registerdesign.php?message=Registration successful! Please check your email to verify your account.&type=success");
            exit;
        } else {
            header("Location: registerdesign.php?message=Error sending verification email!&type=error");
            exit;
        }
    } else {
        header("Location: registerdesign.php?message=Error: " . mysqli_error($conn) . "&type=error");
        exit;
    }
} else {
    header("Location: registerdesign.php?message=Invalid request.&type=error");
    exit;
}
?>
