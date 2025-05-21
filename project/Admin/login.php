<?php
session_start();
require 'connection.php';
require 'mail_otp.php';

// Check if user is already logged in using Remember Me cookie
if (isset($_COOKIE['user_id']) && !isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = $_COOKIE['user_id'];
    // After session is set, recheck if college_id is NULL
    $stmt = $conn->prepare("SELECT college_id FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user['college_id'] === NULL) {
        header("Location: college_registration.php"); // Redirect to college registration
        exit();
    } else {
        header("Location: dashboard.php"); // Redirect to dashboard if college_id is not NULL
        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email_or_phone = $_POST['lemailphone'];
    $password = $_POST['lpassword'];
    $remember_me = isset($_POST['remember_me']) ? true : false;

    // Check if user exists with the given email or phone number
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR phone_number = ?");
    $stmt->bind_param("ss", $email_or_phone, $email_or_phone);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if user exists
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Validate password
        if (!password_verify($password, $user['password'])) {
            $_SESSION['message'] = "Incorrect password.";
            $_SESSION['message_type'] = "error";
            header("Location: index.php");
            exit();
        }

        // Check if role is 2 (Admin)
        if ($user['role'] != 2) {
            $_SESSION['message'] = "You are not an admin.";
            $_SESSION['message_type'] = "error";
            header("Location: index.php");
            exit();
        }

        // Check if email is verified
        if ($user['email_verified'] != 1) {
            $_SESSION['message'] = "Please complete email verification.";
            $_SESSION['message_type'] = "error";
            header("Location: index.php");
            exit();
        }

        // Check if user is active (users_status = 1)
        if ($user['users_status'] == 0) {
            $_SESSION['message'] = "Account not active. Please wait for Super-Admin activation.";
            $_SESSION['message_type'] = "error";
            header("Location: index.php");
            exit();
        }

        // If everything is good, proceed to OTP
        $_SESSION['user_id'] = $user['user_id']; // Store user ID in session

        // Set Remember Me cookie if checked
        if ($remember_me) {
            setcookie('user_id', $user['user_id'], time() + (30 * 24 * 60 * 60), "/");
        }

        // Send OTP if it's not already verified
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
            // After OTP is verified, check if college_id is NULL
            $stmt = $conn->prepare("SELECT college_id FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            // Check if college_id is NULL after OTP verification
            if ($user['college_id'] === NULL) {
                header("Location: college_registration.php"); // Redirect to college registration
                exit();
            } else {
                header("Location: dashboard.php"); // Redirect to dashboard if college_id is not NULL
                exit();
            }
        }
    } else {
        $_SESSION['message'] = "No account found.";
        $_SESSION['message_type'] = "error";
        header("Location: index.php");
        exit();
    }
}
?>
