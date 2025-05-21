<?php
session_start();
require 'connection.php';
require 'mail_otp.php';

// Check if user is already logged in using Remember Me cookie
if (isset($_COOKIE['user_id']) && !isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = $_COOKIE['user_id'];
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email_or_phone = $_POST['lemailphone'];
    $password = $_POST['lpassword'];
    $remember_me = isset($_POST['remember_me']) ? true : false;

    // First check if account exists with role = 6
    $stmt = $conn->prepare("SELECT * FROM users WHERE (email = ? OR phone_number = ?) AND role = 6");
    $stmt->bind_param("ss", $email_or_phone, $email_or_phone);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Now check user status
        if ($user['users_status'] != 1) {
            $_SESSION['message'] = "Your account is not active!";
            $_SESSION['message_type'] = "error";
            header("Location: index.php");
            exit();
        }

        // Check password
        if (password_verify($password, $user['password'])) {
            // Check email verification
            if ($user['email_verified'] == 1) {
                $_SESSION['user_id'] = $user['user_id'];

                if ($remember_me) {
                    setcookie('user_id', $user['user_id'], time() + (30 * 24 * 60 * 60), "/");
                }

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
                    header("Location: dashboard.php");
                    exit();
                }
            } else {
                $_SESSION['message'] = "Your email is not verified!";
                $_SESSION['message_type'] = "error";
            }
        } else {
            $_SESSION['message'] = "Invalid credentials.";
            $_SESSION['message_type'] = "error";
        }
    } else {
        $_SESSION['message'] = "No account found.";
        $_SESSION['message_type'] = "error";
    }

    header("Location: index.php");
    exit();
}
?>
