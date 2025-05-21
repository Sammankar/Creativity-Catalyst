<?php
require 'connection.php'; // Database connection file
require 'mail.php'; // PHPMailer script

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['rfullname'];
    $email = $_POST['remail'];
    $password = password_hash($_POST['rpassword'], PASSWORD_BCRYPT); // Secure password
    $verification_token = bin2hex(random_bytes(32)); // Generate unique token
    $role = 6; // Set role value as 1
    $users_status = 0;
    $access_status = 0;

    // Check if email already exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result(); 

    if ($stmt->num_rows > 0) {
        $stmt->close();
        header("Location: registerdesign.php?message=Email already registered!&type=error");
        exit;
    }
    $stmt->close();

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role, users_status, email_verified,access_status, verification_token) VALUES (?, ?, ?, ?, ?,?, 0, ?)");
    $stmt->bind_param("sssiiis", $full_name, $email, $password, $role, $users_status,$access_status, $verification_token); 

    if ($stmt->execute()) {
        $verification_link = "http://localhost/project/Evaluator/verify.php?token=$verification_token";
        if (sendVerificationEmail($email, $verification_link)) {
            header("Location: registerdesign.php?message=Registration successful! Please check your email to verify your account.&type=success");
            exit;
        } else {
            header("Location: registerdesign.php?message=Error sending verification email!&type=error");
            exit;
        }
    } else {
        header("Location: registerdesign.php?message=Registration failed!&type=error");
        exit;
    }
    $stmt->close();
    $conn->close();
}
?>
