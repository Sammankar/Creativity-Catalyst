<?php
session_start();
include('connection.php');
require 'mail.php'; // PHPMailer script for email sending

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $college_id = mysqli_real_escape_string($conn, $_POST['college_id']);
    $course_id = mysqli_real_escape_string($conn, $_POST['course_id']);
    $guide_permission = isset($_POST['guide_permission']) ? 1 : 0; // 1 if guide, 0 if teacher
    $role = 4; // Role for Teacher/Guide

    // Validate college and course
    $college_check = mysqli_query($conn, "SELECT * FROM colleges WHERE college_id = '$college_id'");
    $course_check = mysqli_query($conn, "SELECT * FROM college_courses WHERE college_id = '$college_id' AND course_id = '$course_id'");
    if (mysqli_num_rows($college_check) == 0 || mysqli_num_rows($course_check) == 0) {
        header("Location: registerdesign_teacher.php?message=Invalid college or course selection.&type=error");
        exit;
    }

    // Check if email already exists
    $check_user = mysqli_query($conn, "SELECT * FROM users WHERE email = '$email'");
    if (mysqli_num_rows($check_user) > 0) {
        header("Location: registerdesign_teacher.php?message=Email already registered.&type=error");
        exit;
    }

    // Generate verification token
    $verification_token = bin2hex(random_bytes(32)); // Generate a unique token

    // Insert into users table
    $query = "INSERT INTO users (full_name, email, password, college_id, course_id, role, guide_permission, users_status, email_verified, verification_token) 
              VALUES ('$name', '$email', '$hashed_password', '$college_id', '$course_id', '$role', '$guide_permission', 0, 0, '$verification_token')";

    if (mysqli_query($conn, $query)) {
        $user_id = mysqli_insert_id($conn);

        // Send verification email
        $verification_link = "http://localhost/project/Teacher/verify.php?token=$verification_token";
        if (sendVerificationEmail($email, $verification_link)) {
            header("Location: registerdesign_teacher.php?message=Registration successful! Please check your email to verify your account.&type=success");
            exit;
        } else {
            header("Location: registerdesign_teacher.php?message=Error sending verification email!&type=error");
            exit;
        }
    } else {
        header("Location: registerdesign_teacher.php?message=Error: " . mysqli_error($conn) . "&type=error");
        exit;
    }
} else {
    header("Location: registerdesign_teacher.php?message=Invalid request.&type=error");
    exit;
}
?>