<?php
session_start();
require 'connection.php';
require 'mail_otp.php';

// Check if user is already logged in using Remember Me cookie
if (isset($_COOKIE['user_id']) && !isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = $_COOKIE['user_id'];

    // Check role
    $stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Check if the role is 4 (Teacher/Guide)
        if ($user['role'] == 4) {
            header("Location: guide_dashboard.php");
            exit();
        }
    }
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email_or_phone = $_POST['lemailphone'];
    $password = $_POST['lpassword'];
    $remember_me = isset($_POST['remember_me']) ? true : false;

    // Modify role check to 'role = 4' for Teacher/Guide
    $stmt = $conn->prepare("SELECT * FROM users WHERE (email = ? OR phone_number = ?) AND role = 4");
    $stmt->bind_param("ss", $email_or_phone, $email_or_phone);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Check if account is activated (users_status = 1)
        if ($user['users_status'] == 0) {
            $_SESSION['message'] = "Account not activated. Please contact your course Sub-Admin.";
            $_SESSION['message_type'] = "error";
            header("Location: index.php");
            exit();
        }

        // Check if password is correct
        if (password_verify($password, $user['password'])) {
            if ($user['email_verified'] == 1) {
                $_SESSION['user_id'] = $user['user_id']; // Store user ID in session

                // Set Remember Me cookie if checked
                if ($remember_me) {
                    setcookie('user_id', $user['user_id'], time() + (30 * 24 * 60 * 60), "/");
                }

                // OTP should be sent only if it's a fresh login (not from "Remember Me")
                if (!isset($_SESSION['otp_verified'])) {
                    $otp = rand(100000, 999999);
                    $_SESSION['otp'] = $otp;

                    if (sendOtpEmail($user['email'], $otp)) {
                        $_SESSION['message'] = "OTP sent to your email!";
                        $_SESSION['message_type'] = "success";
                        header("Location: verify_otp.php");
                        exit();
                    } else {
                        $_SESSION['message'] = "Failed to send OTP.";
                        $_SESSION['message_type'] = "error";
                    }
                } else {
                    // Redirect directly if role is 4
                    $stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
                    $stmt->bind_param("i", $user['user_id']);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        $user = $result->fetch_assoc();

                        if ($user['role'] == 4) {
                            header("Location: guide_dashboard.php");
                            exit();
                        }
                    }
                }
            } else {
                $_SESSION['message'] = "Your email is not verified!";
                $_SESSION['message_type'] = "error";
            }
        } else {
            $_SESSION['message'] = "Invalid credentials. Please check your password.";
            $_SESSION['message_type'] = "error";
        }
    } else {
        $_SESSION['message'] = "No account found with the provided credentials.";
        $_SESSION['message_type'] = "error";
    }

    header("Location: index.php");
    exit();
}
?>
