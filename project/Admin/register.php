<?php
require 'connection.php'; // Database connection file
require 'mail.php'; // PHPMailer script

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['rfullname'];
    $email = $_POST['remail'];
    $password = password_hash($_POST['rpassword'], PASSWORD_BCRYPT); // Secure password
    $verification_token = bin2hex(random_bytes(32)); // Generate unique token
    $role = 2; // Role 2 for Admin
    $users_status = 0; // Default inactive, requires Super-Admin approval

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

    // Insert into database (college_id is NOT included here)
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role, users_status, email_verified, verification_token) VALUES (?, ?, ?, ?, ?, 0, ?)");
    $stmt->bind_param("sssiis", $full_name, $email, $password, $role, $users_status, $verification_token);

    if ($stmt->execute()) {
        $verification_link = "http://localhost/project/SuperAdmin/verify.php?token=$verification_token";
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
